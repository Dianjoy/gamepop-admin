<?php
$db = array(
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

return new PDO($db['db'] . ':host=' . $db['host']
  . ';port=' . $db['port']
  . ';dbname=' . $db['name'],
  $db_init['user'], $db_init['password'], $db_init['options']);