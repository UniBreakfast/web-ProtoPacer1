<?php

require_once $_SERVER['DOCUMENT_ROOT'].'sandbox.php';
require_once 'clerkvars.php';
require_once 'datascopes.php';

require_once $commonsPath.'krumo.php';
require_once $commonsPath.'f.php';

require_once 'Auth/checks.php';

function Response($code, $type, $text, $data=null) {
  switch ($type) {
    case 'S': $type = 'SUCCESS';  break;
    case 'F': $type = 'FAIL';     break;
    case 'E': $type = 'ERROR';    break;
    case 'I': $type = 'INFO';     break;
  }
  $response = array('msg' => array('code'=>$code,'type'=>$type,'text'=>$text));
  if ($data) $response['data'] = $data;
  return $response;
}

function creds_check() {
  list ($userid, $token) = f::request('userid', 'token');
  if ($userid and $token) {
    global $db, $tblSessions, $sessExpire;
    $q = "SELECT id, bfp_hash FROM $tblSessions WHERE user_id = ?
      AND token = ? AND dt_modify > NOW() - INTERVAL $sessExpire DAY";
    $p = qp($userid,'i', $token,'s');
    if (list ($id, $bfp) = f::getRecord($db, $q, $p) and bfpCheck($bfp)) {
      $token = randStr();
      $q = "UPDATE $tblSessions SET token = '$token' WHERE id = $id";
      f::execute($db, $q);
      $data['token' ] = $token;
      $data['expire'] = $sessExpire;
    } else $userid = false;
  }
  else $data = array();
  return array($userid, $data);
}

switch ($_REQUEST['task']) {

  case 'reg': {
    list ($login, $pass) = f::request('login', 'pass');

    if ($check = f::strCheck($login, 2, 64, "ERU-1"))
      exit (json_encode(Response(135, 'E', "Invalid login input: $check")));

    if (!$login or !$pass) exit (json_encode(Response(102, 'E',
                                      "Not enough credentials to register!")));

    $q = "SELECT login FROM $tblUsers WHERE login = ?";
    if (f::getValue($db, $q, qp($login,'s')))
      exit (json_encode(Response(101, 'F', "Login $login already occupied")));

    $hash = hashStr($pass);
    $q = "INSERT $tblUsers (login, passhash) VALUES (?, '$hash')";
    f::execute($db, $q, qp($login,'s'));
    exit (json_encode(Response(100, 'S', "User $login is registered!")));
  }

  case 'login': {
    list ($login, $pass) = f::request('login', 'pass');
    if (!$login or !$pass) exit
      (json_encode(Response(106, 'E', "Not enough credentials to sign in!")));

    $q = "SELECT id, passhash FROM $tblUsers WHERE login = ?";
    if (!(list ($userid, $hash) = f::getRecord($db, $q, qp($login,'s')))) exit
      (json_encode(Response(105,'F', "Can't sign in. User $login not found!")));

    if (!hashCheck($pass, $hash)) exit
      (json_encode(Response(104, 'F', "Can't sign in. Incorrect password!")));

    $token = randStr();   $bfp = hashStr(bfp());
    $q = "INSERT $tblSessions (user_id, token, bfp_hash)
                       VALUES ($userid, '$token', '$bfp')";
    f::execute($db, $q);

    $q = "DELETE FROM $tblSessions WHERE user_id = $userid AND dt_modify <
            (SELECT min(dt_modify) FROM
              (SELECT dt_modify FROM $tblSessions WHERE user_id = $userid
                ORDER BY dt_modify DESC LIMIT $sessNum) AS tmp)";
    f::execute($db, $q);

    $data = array('userid'=>$userid, 'token'=>$token, 'expire'=>$sessExpire);
    exit (json_encode(Response(103, 'S',
                               "You are signed in now as $login!", $data)));
  }

  case 'check': {
    list ($userid, $token) = f::request('userid', 'token');
    if (!$userid or !$token) exit
      (json_encode(Response(110, 'E', "No complete session cookie provided")));

    $q = "SELECT id, bfp_hash FROM $tblSessions WHERE user_id = ?
          AND token = ? AND dt_modify > NOW() - INTERVAL $sessExpire DAY";
    $p = qp($userid,'i', $token,'s');
    if (!(list ($id, $bfp) = f::getRecord($db, $q, $p) and bfpCheck($bfp)))
      exit (json_encode(Response(108, 'I', "No such session in act")));

    $token = randStr();
    $q = "UPDATE $tblSessions SET token = '$token' WHERE id = $id";
    f::execute($db, $q);
    $data = array('token'=>$token, 'expire'=>$sessExpire);
    exit (json_encode(Response(107, 'I',
                               "Session confirmed, you are signed in", $data)));
  }

  case 'logout': {
    list ($userid, $token) = f::request('userid', 'token');
    if (!$userid or !$token) exit
      (json_encode(Response(112, 'E', "No complete session cookie provided")));

    $q = "SELECT id, bfp_hash FROM $tblSessions
          WHERE user_id = ? AND token = ?";
    $p = qp($userid,'i', $token,'s');
    if (list ($id, $bfp) = f::getRecord($db, $q, $p) and bfpCheck($bfp))
      f::execute($db, "DELETE FROM $tblSessions WHERE id = $id");
    exit;
  }

  case 'newpass': {
    list (       $login,  $oldpass,  $newpass ) =
      f::request('login', 'oldpass', 'newpass');
    if (!$login or !$oldpass or !$newpass) exit (json_encode(Response(117, 'E',
                               "Not enough credentials to change password!")));

    $q = "SELECT id, passhash FROM $tblUsers WHERE login = ?";
    if (!(list ($userid, $hash) = f::getRecord($db, $q, qp($login,'s'))))
      exit (json_encode(Response(116, 'F',
                             "Can't change password. No user $login found!")));

    if (!hashCheck($oldpass, $hash)) exit (json_encode(Response(115, 'F',
                                "Can't change password. Incorrect password!")));

    $hash = hashStr($newpass);
    $q = "UPDATE $tblUsers SET passhash = '$hash' WHERE id = $userid";
    f::execute($db, $q);
    exit (json_encode(Response(114, 'S', "Password changed for user $login")));
  }

  case 'rename': {
    list (       $oldlogin,  $pass,  $newlogin ) =
      f::request('oldlogin', 'pass', 'newlogin');
    if (!$oldlogin or !$pass or !$newlogin) exit (json_encode(Response(121, 'E',
                                   "Not enough credentials to change login!")));

    $q = "SELECT id, passhash FROM $tblUsers WHERE login = ?";
    if (!(list ($userid, $hash) = f::getRecord($db, $q, qp($oldlogin, 's')))) exit (json_encode(Response(120, 'F',
                              "Can't change login. No user $oldlogin found!")));

    if (!hashCheck($pass, $hash)) exit (json_encode(Response(119, 'F',
                                   "Can't change login. Incorrect password!")));

    $q = "UPDATE $tblUsers SET login = ? WHERE id = $userid";
    f::execute($db, $q, qp($newlogin,'s'));
    exit (json_encode(Response(118, 'S',
                                 "Login changed from $oldlogin to $newlogin")));
  }

  case 'unreg': {
    list ($login, $pass) = f::request('login', 'pass');
    if (!$login or !$pass) exit
      (json_encode(Response(125,'E', "Not enough credentials to unregister!")));

    $q = "SELECT id, passhash FROM $tblUsers WHERE login = ?";
    if (!(list ($userid, $hash) = f::getRecord($db, $q, qp($login,'s'))))
      exit (json_encode(Response(124, 'F',
                        "Can't unregister. No user with login $login found!")));

    if (!hashCheck($pass, $hash)) exit (json_encode(Response(123, 'F',
                                     "Can't unregister. Incorrect password!")));

    $q = "DELETE FROM $tblUsers WHERE id = $userid";
    f::execute($db, $q);
    exit (json_encode(Response(122,'S',"User $login removed!")));
  }

  case 'get': {
    list ($userid, $data) = creds_check();

    list ($table, $fields, $ownOnly) = f::request('table', 'fields', 'own');
    if ($fields) $fields = json_decode($fields);

    if (!$userid) {
      if (!isset($freeAccess[$table])) exit
        (json_encode(Response(129, 'F', "No table $table available")));
      if ($wrong = implode(array_diff($fields, $freeAccess[$table]), ', '))
        exit (json_encode(Response(130, 'F',
                     "Field(s) $wrong are not available in the $table table")));
      $ownOnly = false;
    }
    else {
      $freeAccess = array_merge_recursive($freeAccess, $userAccess);
      $privAccess = array_merge_recursive($freeAccess, $privAccess);
      if ($ownOnly  or !isset($freeAccess[$table]) or
          array_diff($fields, $freeAccess[$table])) {
        if (!isset($privAccess[$table])) exit
          (json_encode(Response(129, 'F', "No table $table available", $data)));
        if ($wrong = implode(array_diff($fields, $privAccess[$table]), ', '))
          exit (json_encode(Response(130, 'F',
              "Field(s) $wrong are not available in the $table table", $data)));
        $ownOnly = true;
      }
    }

    if (!$fields) $fields = $ownOnly? $privAccess[$table] : $freeAccess[$table];
    $data['headers'] = $fields;
    $fields = implode($fields, ', ');

    $q = "SELECT $fields FROM $table WHERE 1";
    if ($ownOnly) {
      if ($table != $tblUsers) $q .= " AND user_id = $userid";
      else                     $q .= " AND      id = $userid";
    }
    $data['rows'] = f::getRecords($db, $q);
    if ($rows = sizeof($data['rows']) and $columns = sizeof($data['headers']))
      exit (json_encode(Response(127, 'S',
                         "$rows records of $columns fields delivered", $data)));
    exit (json_encode(Response(128, 'S', "Query returned no data", $data)));
  }

  case 'list': {
    list ($userid, $data) = creds_check();

    list ($own, $table) = f::request('own', 'table');
    $own = $userid ? $own : 0;

    if (!$userid) {
      if (!$table) {
        $data['list']  = $freeAccess;
        $tables = sizeof($freeAccess);
        exit
          (json_encode(Response(132,'S', "$tables table(s) available", $data)));
      }
      else {
        if (!isset($freeAccess[$table]))
          exit (json_encode(Response(133, 'F', "No table $table available")));

        $data['list']  = $freeAccess[$table];
        $fields = sizeof($freeAccess[$table]);
        exit (json_encode(Response(131, 'S',
                     "$fields field(s) available in the table $table", $data)));
      }
    }

    $freeAccess = array_merge_recursive($freeAccess, $userAccess);
    $privAccess = array_merge_recursive($freeAccess, $privAccess);

    if (!$own) {
      if (!$table) {
        $data['list'] = $freeAccess;
        $tables = sizeof($freeAccess);
        exit
          (json_encode(Response(132,'S', "$tables table(s) available", $data)));
      }
      else {
        if (!isset($freeAccess[$table])) exit
          (json_encode(Response(133, 'F', "No table $table available", $data)));

        $data['list'] = $freeAccess[$table];
        $fields = sizeof($freeAccess[$table]);
        exit (json_encode(Response(131, 'S',
                     "$fields field(s) available in the table $table", $data)));
      }
    }

    if ($own == 1) {
      if (!$table) {
        $data['list']  = $privAccess;
        $tables = sizeof($privAccess);
        exit
          (json_encode(Response(132,'S', "$tables table(s) available", $data)));
      }
      else {
        if (!isset($privAccess[$table])) exit
          (json_encode(Response(133,'F',"No table $table available", $data)));

        $data['list']  = $privAccess[$table];
        $fields = sizeof($privAccess[$table]);
        exit (json_encode(Response(131, 'S',
                     "$fields field(s) available in the table $table", $data)));
      }
    }

    if (!$table) {
      $data['list']['user'] = $freeAccess;
      $data['list']['own' ] = $privAccess;
      $tables = sizeof($privAccess);
      exit
        (json_encode(Response(132,'S', "$tables table(s) available", $data)));
    }
    else {
      if (isset($freeAccess[$table]))
        $data['list']['user'] = $freeAccess[$table];
      if (!isset($privAccess[$table])) exit
        (json_encode(Response(133,'F',"No table $table available", $data)));

      $data['list']['own' ] = $privAccess[$table];
      $fields = sizeof($privAccess[$table]);
      exit (json_encode(Response(131, 'S',
                     "$fields field(s) available in the table $table", $data)));
    }
  }

  default: {}
}


?>
