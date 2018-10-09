<?php
require_once $_SERVER['DOCUMENT_ROOT'].'sandbox.php';

$userid = ;

$query = "SELECT login, confidence FROM test_users
          WHERE id = $userid";
$result = mysqli_query($db, $query)
  or exit ('SELECT login, confidence Query failed!');

if ($result) {
  list($login, $confidence) = mysqli_fetch_row($result);
  echo "$login|$confidence";
}

?>
