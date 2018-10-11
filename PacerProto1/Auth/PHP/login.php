<?php
require_once $_SERVER['DOCUMENT_ROOT'].'sandbox.php';
require_once 'authvars.php';
require_once 'seq.php';
if (isset($_REQUEST['login'])      and isset($_REQUEST['password']) and
     trim($_REQUEST['login'])!=='' and  trim($_REQUEST['password'])!=='') {
  $login    = trim($_REQUEST['login']);
  $password = trim($_REQUEST['password']);

  $query = "SELECT id, passhash FROM $userTable
            WHERE login='$login'";
  $result = mysqli_query($db, $query) or exit ('SELECT id Query failed!');

  if (list($userid, $hash)=mysqli_fetch_row($result)) {
    $query = "DELETE FROM $sessionTable
              WHERE dt_modify < NOW() - INTERVAL 60 HOUR";
    mysqli_query($db, $query)
      or exit ("DELETE FROM $sessionTable WHERE dt_modify... Query failed!");

    if (hashCheck($password, $hash)) {
      $query = "SELECT id FROM $sessionTable
                WHERE user_id = $userid ORDER BY dt_create";
      $result = mysqli_query($db, $query)
        or exit ('SELECT id ORDER BY dt_create... Query failed!');
      if (mysqli_num_rows($result)>2) {
        list($id) = mysqli_fetch_row($result);
        $query = "DELETE FROM $sessionTable WHERE id = $id";
        mysqli_query($db, $query)
          or exit ("DELETE FROM $sessionTable WHERE id... Query failed!");
      }

      $addr     = $_SERVER['REMOTE_ADDR'];
      $addrpart = substr($addr, 0, strrpos($addr, '.'));
      $agent    = $_SERVER['HTTP_USER_AGENT'];
      $hash     = hashGen("$addrpart $agent");
      $token    = tokenGen();
      $query = "INSERT $sessionTable (user_id, token, bfp_hash)
                VALUE ($userid, '$token', '$hash')";
      mysqli_query($db, $query)
        or exit ('INSERT user_id, token... Query failed!');
      setcookie('user', "$userid|$token", time()+216000, '/');
      header("Location: ../../$insidePage");
    }
    else {
      setcookie('login',    $login,    time()+5, '/');
      setcookie('check', 'wrong_pass', time()+5, '/');
      header('Location: ../../login.htm');
    }
  }
  else {
    setcookie('login',    $login,     time()+5, '/');
    setcookie('check', 'wrong_login', time()+5, '/');
    header('Location: ../../login.htm');
  }
}
else echo 'no login or password provided';

?>
