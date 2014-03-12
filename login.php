<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14-3-10
 * Time: 下午1:05
 */

session_start();
if(isset($_SESSION['user_id']) and $_SESSION['user_id']!=''){
  header("location:./");
  exit();
}

$result = array(
  'msg' => $_SESSION['msg'],
);

require_once('./inc/Template.class.php');
$tpl = new Template('web/login.html');
$config = json_decode(file_get_contents('config/config.json'), true);
echo $tpl->render(array_merge($config, $result));