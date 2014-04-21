<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14-3-7
 * Time: 上午11:02
 * 后台首页
 */
session_start();

if(!isset($_SESSION['permission']) ){
  header("location:login.php");
  exit();
}

include "inc/Template.class.php";
$template = new Template('web/index.html');

$config = json_decode(file_get_contents('config/config.json'), true);

foreach ($_SESSION['permission'] as $auth) {
  $filename = "config/$auth.json";
  if (file_exists($filename)) {
    $config['category'][] = json_decode(file_get_contents($filename), true);
  }
}

echo $template->render($config);