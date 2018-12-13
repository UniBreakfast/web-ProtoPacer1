<?php

# SERVER-SIDE OPTIONS OF clerk module

# where to find f.php, krumo etc
$commonsPath = '../../_Commons/PHP/';

# name of the users-table in the database
$tblUsers = 'test_users';
//$tblUsers = 'users';

# name of the session-table in the database
$tblSessions = 'test_sessions';
//$tblSessions = 'sessions';

# number of sessions at the same time per user
$sessNum = 3;

# days until session expires
$sessExpire = 5;

# to use or not to use browser footprint (user agent string and/or IP) in check
$checkAgent = true;
//$checkAgent = false;
$checkIP = true;
//$checkIP = false;


?>
