<?php
$db_vars = array();
$db_vars['host'] = 'mariadb';
$db_vars['port'] = 3306;
$db_vars['name'] = 'img_db';
$db_vars['username'] = 'root';
$db_vars['password'] = 'root';
$DB = NULL;
try
{
	$DB = new PDO('mysql:host=' .  $db_vars['host'] . ';port=' . $db_vars['port'] . ';dbname=' .  $db_vars['name'], $db_vars['username'],  $db_vars['password']);
	$DB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$DB->setAttribute(PDO::ATTR_CASE, PDO::CASE_NATURAL);
}
catch (PDOException $e)
{
	echo 'Connection failed: ' . $e;
	die();
}
/* We don't need access data any more here */
unset($db_vars);
