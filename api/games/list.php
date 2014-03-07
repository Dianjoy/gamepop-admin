<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14-3-7
 * Time: 下午6:10
 */

$pdo = include_once "../../inc/pdo.php";
include_once "../../Template.class.php";

$result = array();

$tpl = new Template('template/list.html');
echo $tpl->render($result);