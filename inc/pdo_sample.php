<?php
// 定义DEBUG常量可以自动开启DEBUG模式
//define('DEBUG', true);

$db_init = array(
  'db'=>'mysql',
  'host'=>'host',
  'port'=>'port',
  'name'=>'db',
  'user' => 'user',
  'password' => 'password',
  'options' => array(
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'"
  ),
);

return new PDO($db_init['db'] . ':host=' . $db_init['host']
  . ';port=' . $db_init['port']
  . ';dbname=' . $db_init['name'],
  $db_init['user'], $db_init['password'], $db_init['options']);