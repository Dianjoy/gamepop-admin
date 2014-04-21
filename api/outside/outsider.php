<?php
define('OPTIONS', 'game');
include_once '../../inc/session.php';
?>
<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14-3-7
 * Time: 下午6:10
 */

include_once "../../inc/Spokesman.class.php";
include_once "../../inc/Admin.class.php";
$admin = new Admin();

$args = $_REQUEST;
$request = file_get_contents('php://input');
if ($request) {
  $args = array_merge($_POST, json_decode($request, true));
}
header("Content-Type:application/json;charset=utf-8");
switch ($_SERVER['REQUEST_METHOD']) {
  case 'GET':
    fetch($admin, $args);
    break;

  case 'PATCH':
    update($admin, $args);
    break;

  case 'DELETE':
    delete($admin);
    break;

  default:
    header("HTTP/1.1 406 Not Acceptable");
    break;
}

function fetch($admin) {
  $admins = $admin->select(Admin::$ALL, '`fullname` AS `label`')
    ->where(array('role' => Admin::OUTSIDER))
    ->fetchAll(PDO::FETCH_ASSOC);

  Spokesman::say(array('list' => $admins));
}

function delete($admin) {
  $args = array(
    'status' => 1,
  );
  update($admin, $args, '删除成功', '删除失败');
}

function update($admin, $args, $success = '更新成功', $error = '更新失败') {
  $admin->init_write();
  $url = $_SERVER['PATH_INFO'];
  $id = substr($url, 1);
  $result = $admin->update($args)
    ->where(array('guide_name' => $id))
    ->execute();
  Spokesman::judge($result, $success, $error, $args);
}