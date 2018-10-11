<?php
require_once $_SERVER['DOCUMENT_ROOT'].'sandbox.php';
require_once 'authvars.php';
require_once 'seq.php';
if (isset($_REQUEST['login'])      and isset($_REQUEST['password']) and
     trim($_REQUEST['login'])!=='' and  trim($_REQUEST['password'])!=='') {
  $login    = trim($_REQUEST['login']);
  $password = trim($_REQUEST['password']);

  $query = "SELECT id FROM $userTable
            WHERE login='$login'";
  $result = mysqli_query($db, $query) or exit ('SELECT id Query failed!');

  if (list($userid)=mysqli_fetch_row($result)) {
    setcookie('login',   $login,   time()+5, '/');
    setcookie('check', 'occupied', time()+5, '/');
    header('Location: ../../register.htm');
  }
  else {
    $hash = hashGen($password);
    $query = "INSERT $userTable (login, passhash) VALUE ('$login', '$hash')";
    mysqli_query($db, $query)
      or exit ("INSERT $userTable... Query failed!");

    setcookie('login', $login, time()+5, '/');
    header('Location: ../../login.htm');
  }
}
else echo 'no login or password provided';
?>
