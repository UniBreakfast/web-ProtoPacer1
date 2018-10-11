<?php
require_once $_SERVER['DOCUMENT_ROOT'].'sandbox.php';
require_once 'authvars.php';
require_once 'seq.php';
if (isset($_REQUEST['cookie']) and trim($_REQUEST['cookie'])!=='') {
  $cookie = trim($_REQUEST['cookie']);
  list($userid, $token) = explode('|', $cookie);
  $addr     = $_SERVER['REMOTE_ADDR'];
  $addrpart = substr($addr, 0, strrpos($addr, '.'));
  $agent    = $_SERVER['HTTP_USER_AGENT'];
  $bfp      = "$addrpart $agent";

  $query = "SELECT id, bfp_hash, dt_modify FROM $sessionTable
            WHERE user_id = $userid AND token = '$token'";
  $result = mysqli_query($db, $query)
    or exit ("SELECT token FROM $sessionTable Query failed!");

  if (list($id, $hash, $dtModify) = mysqli_fetch_row($result))
    if (hashCheck($bfp, $hash) and
        strtotime($dtModify)+216000 /*2.5days*/ - time() > 0) {
      require_once $_SERVER['DOCUMENT_ROOT'].'StackTech1/Auth/PHP/seq.php';
      $newToken = tokenGen();
      $query = "UPDATE $sessionTable SET token='$newToken' WHERE id=$id";
      mysqli_query($db, $query)
        or exit ("UPDATE $sessionTable SET token Query failed!");
      echo "valid|$newToken";
    } else { echo $bfp;
      $query = "DELETE FROM $sessionTable WHERE id=$id";
      mysqli_query($db, $query)
        or exit ("DELETE FROM $sessionTable Query failed!");
      echo "invalid";
    }
  else echo 'invalid';
}
else echo 'no cookie provided for checking';
?>
