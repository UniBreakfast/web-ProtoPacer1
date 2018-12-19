<?php

# whitelisted tables/fields

# guests accessible tables/fields
$freeAccess = array('test_news' =>
                              array('title', 'post', 'dt_create', 'dt_modify'));

# any user accessible tables/fields
$userAccess = array('test_users' => array('login', 'dt_create'));

# privately accessible tables/fields
$privAccess = array('test_users' => array('id', 'confidence', 'dt_modify'),
                    'test_sessions' => array('dt_create', 'dt_modify'));


?>
