<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14-3-7
 * Time: 上午11:02
 * 后台首页
 */
include "inc/Template.class.php";
$template = new Template('web/index.html');

$config = json_decode(file_get_contents('config.json'), true);

echo $template->render($config);