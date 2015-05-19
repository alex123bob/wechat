<?php
include_once 'libs/mysql.class.php';
$mysql = new mysql(SAE_MYSQL_HOST_M.':'.SAE_MYSQL_PORT, SAE_MYSQL_USER, SAE_MYSQL_PASS, SAE_MYSQL_DB, utf8);