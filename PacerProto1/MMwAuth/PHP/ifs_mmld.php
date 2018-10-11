<?php
require_once $_SERVER['DOCUMENT_ROOT'].'sandbox.php';
if (isset($_REQUEST['userid']) and trim($_REQUEST['userid']!=='')) {
  $userid = trim($_REQUEST['userid']);

  $query = "SELECT login, confidence FROM test_users
          WHERE id = $userid";
  $result = mysqli_query($db, $query)
    or exit ('SELECT login, confidence Query failed!');

  if ($result) {
    list($login, $confidence) = mysqli_fetch_row($result);
    echo "$login|$confidence";
  }
  else echo "no user found with user_id $userid";
}
else echo "no user_id received to get data by";

?>
