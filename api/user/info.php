<?php
define('OPTIONS', 'article|article_wb');
include_once '../../inc/session.php';
?>
<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14-4-21
 * Time: 下午4:54
 */
include_once "../../inc/Spokesman.class.php";
include_once "../../inc/Admin.class.php";
$admin = new Admin();

$args = $_REQUEST;
$request = file_get_contents('php://input');
if ($request) {
  parse_str($request, $args);
  $args = array_merge($_POST, $args);
}

switch ($_SERVER['REQUEST_METHOD']) {
  case 'PATCH':
    update($admin, $args);
    break;

  default:
    header("HTTP/1.1 406 Not Acceptable");
    break;
}

function update($admin, $args) {
  $me = $_SESSION['id'];
  // 修改密码
  if (isset($args['newpassword'])) {
    $pwd = $args['newpassword'];
    if (!$pwd || strlen($pwd) < 6 || strlen($pwd) > 16) {
      header("HTTP/1.1 400 Bad Request");
      Spokesman::say(array(
        'code' => 1,
        'msg' => '密码不符合要求',
      ));
      exit();
    }
    if ($pwd !== $args['repassword']) {
      header("HTTP/1.1 400 Bad Request");
      Spokesman::say(array(
        'code' => 1,
        'msg' => '两次输入的密码不一致',
      ));
      exit();
    }
    $info = $admin->select("'x'")
      ->where(array(
        'user' => $_SESSION['user'],
        'password' => $args['oldpassword'],
      ))
      ->fetch(PDO::FETCH_COLUMN);
    if (!$info) {
      header("HTTP/1.1 401 Unauthorized");
      Spokesman::say(array(
        'code' => 1,
        'msg' => '原密码错误',
      ));
      exit();
    }

    $args = array(
      'password' => $pwd
    );
    $conditions = array(
      'user' => $_SESSION['user'],
      'id' => $me
    );
  }

  $result = $admin->update($args)
    ->where($conditions)
    ->execute();

  Spokesman::judge($result, '修改成功', '修改失败');
}