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
    if ($login and $pass) {
      $q = "SELECT login FROM $tblUsers WHERE login = ?";
      if (f::getValue($db, $q, qp($login,'s')))
        echo json_encode(Response(101, 'F', "Login $login already occupied"));
      else {
        $hash = hashStr($pass);
        $q = "INSERT $tblUsers (login, passhash) VALUES (?, '$hash')";
        f::execute($db, $q, qp($login,'s'));
        echo json_encode(Response(100, 'S', "User $login is registered!"));
      }
    }
    else echo json_encode(Response(102, 'E',
                                   "Not enough credentials to register!"));
  } break;

  case 'login': {
    list ($login, $pass) = f::request('login', 'pass');
    if ($login and $pass) {
      $q = "SELECT id, passhash FROM $tblUsers WHERE login = ?";
      if (list ($userid, $hash) = f::getRecord($db, $q, qp($login,'s'))) {

        if (hashCheck($pass, $hash)) {
          $token = randStr();
          $q = "INSERT $tblSessions (user_id, token, bfp_hash)
                VALUES ($userid, '$token', '".hashStr(bfp())."')";
          f::execute($db, $q);

          $q = "DELETE FROM $tblSessions WHERE user_id = $userid AND dt_modify <
                  (SELECT min(dt_modify) FROM
                    (SELECT dt_modify FROM $tblSessions WHERE user_id = $userid
                      ORDER BY dt_modify DESC LIMIT $sessNum) AS tmp)";
          f::execute($db, $q);

          echo json_encode(Response(103,'S',"You are signed in now as $login!",
            array('userid'=>$userid, 'token'=>$token, 'expire'=>$sessExpire)));
        }
        else echo json_encode(Response(104, 'F',
                                       "Can't sign in. Incorrect password!"));
      }
      else echo json_encode(Response(105, 'F',
                                     "Can't sign in. User $login not found!"));
    }
    else echo json_encode(Response(106, 'E',
                                   "Not enough credentials to sign in!"));
  } break;

  case 'check': {
    list ($userid, $token) = f::request('userid', 'token');
    if ($userid and $token) {
      $q = "SELECT id, bfp_hash FROM $tblSessions WHERE user_id = ?
      AND token = ? AND dt_modify > NOW() - INTERVAL $sessExpire DAY";
      $p = qp($userid,'i', $token,'s');
      if (list ($id, $bfp) = f::getRecord($db, $q, $p) and bfpCheck($bfp)) {
        $token = randStr();
        $q = "UPDATE $tblSessions SET token = '$token' WHERE id = $id";
        f::execute($db, $q);
        echo json_encode(Response(107, 'I', "Session confirmed, "
          ."you are signed in", array('token'=>$token, 'expire'=>$sessExpire)));
      }
      else echo json_encode(Response(108, 'I', "No such session in act"));
    }
    else echo json_encode(Response(110, 'E',
                                   "No complete session cookie provided"));
  } break;

  case 'logout': {
    list ($userid, $token) = f::request('userid', 'token');
    if ($userid and $token) {
      $q = "SELECT id, bfp_hash FROM $tblSessions WHERE user_id = ?
      AND token = ?";
      $p = qp($userid,'i', $token,'s');
      if (list ($id, $bfp) = f::getRecord($db, $q, $p) and bfpCheck($bfp))
        f::execute($db, "DELETE FROM $tblSessions WHERE id = $id");
    }
    else echo json_encode(Response(112, 'E',
                                   "No complete session cookie provided"));
  } break;

  case 'newpass': {
    list (       $login,  $oldpass,  $newpass ) =
      f::request('login', 'oldpass', 'newpass');
    if ($login and $oldpass and $newpass) {
      $q = "SELECT id, passhash FROM $tblUsers WHERE login = ?";
      if (list ($userid, $hash) = f::getRecord($db, $q, qp($login,'s'))) {

        if (hashCheck($oldpass, $hash)) {
          $hash = hashStr($newpass);
          $q = "UPDATE $tblUsers SET passhash = '$hash' WHERE id = $userid";
          f::execute($db, $q);
          echo json_encode(Response(114, 'S',
                                    "Password changed for user $login"));
        }
        else echo json_encode(Response(115, 'F',
                                 "Can't change password. Incorrect password!"));
      }
      else echo json_encode(Response(116, 'F',
                               "Can't change password. No user $login found!"));
    }
    else echo json_encode(Response(117, 'E',
                                 "Not enough credentials to change password!"));
  } break;

  case 'rename': {
    list (       $oldlogin,  $pass,  $newlogin ) =
      f::request('oldlogin', 'pass', 'newlogin');
    if ($oldlogin and $pass and $newlogin) {
      $q = "SELECT id, passhash FROM $tblUsers WHERE login = ?";
      if (list ($userid, $hash) = f::getRecord($db, $q, qp($oldlogin, 's'))) {

        if (hashCheck($pass, $hash)) {
          $q = "UPDATE $tblUsers SET login = ? WHERE id = $userid";
          f::execute($db, $q, qp($newlogin,'s'));
          echo json_encode(Response(118, 'S',
                                  "Login changed from $oldlogin to $newlogin"));
        }
        else echo json_encode(Response(119, 'F',
                                    "Can't change login. Incorrect password!"));
      }
      else echo json_encode(Response(120, 'F',
                               "Can't change login. No user $oldlogin found!"));
    }
    else echo json_encode(Response(121, 'E',
                                   "Not enough credentials to change login!"));
  } break;

  case 'unreg': {
    list ($login, $pass) = f::request('login', 'pass');
    if ($login and $pass) {
      $q = "SELECT id, passhash FROM $tblUsers WHERE login = ?";
      if (list ($userid, $hash) = f::getRecord($db, $q, qp($login,'s'))) {

        if (hashCheck($pass, $hash)) {
          $q = "DELETE FROM $tblUsers WHERE id = $userid";
          f::execute($db, $q);
          echo json_encode(Response(122,'S',"User $login removed!"));
        }
        else echo json_encode(Response(123, 'F',
                                      "Can't unregister. Incorrect password!"));
      }
      else echo json_encode(Response(124, 'F',
                         "Can't unregister. No user with login $login found!"));
    }
    else echo json_encode(Response(125, 'E',
                                   "Not enough credentials to unregister!"));
  } break;

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
      echo json_encode(Response(127, 'S',
                          "$rows records of $columns fields delivered", $data));
    else echo json_encode(Response(128, 'S', "Query returned no data", $data));
  } break;

  case 'list': {
    list ($userid, $data) = creds_check();

    list ($ownOnly, $table) = f::request('own', 'table');
    $ownOnly = $userid ? $ownOnly : 0;

////////////////////////////////////////////////////////////////////////////////

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
          (json_encode(Response(129, 'F', "No table $table available")));
        if ($wrong = implode(array_diff($fields, $privAccess[$table]), ', '))
          exit (json_encode(Response(130, 'F',
                     "Field(s) $wrong are not available in the $table table")));
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
      echo json_encode(Response(127, 'S',
                          "$rows records of $columns fields delivered", $data));
    else echo json_encode(Response(128, 'S', "Query returned no data", $data));
  } break;

  default: {}
}

?>
