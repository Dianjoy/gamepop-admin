<?php
define('OPTIONS', 'root');
include_once '../../inc/session.php';
?>
<?php
header("Content-Type:application/json;charset=utf-8");
$m = $_REQUEST['m'];
$all = array('add', 'update', 'del');
if (!in_array($m, $all)) {
  exit(json_encode(array(
    'code' => 1,
    'msg' => '指令错误',
  )));
}
$DB = include(dirname(__FILE__).'/../../inc/pdo.php');
include_once(dirname(__FILE__).'/../../inc/Admin.class.php');
$admin = new Admin($DB);
$m($admin);
$admin->destroy();

function add($admin){
	$username = trim($_POST['username']); 
	$password = trim($_POST['newpassword']);
  $fullname = trim($_POST['fullname']);
  $qq = trim($_POST['qq']);
	if ($admin->is_exist($username)) {
    $result = array(
      'code' => 1,
      'msg' => '账号已经存在'
    );
    exit(json_encode($result));
	}

  $permission = $_POST['role'];
  $admin->add($username, $fullname, $password, $permission, $qq);
  $result = array(
    'code' => 0,
    'msg' => '添加成功'
  );
	echo json_encode($result);
}
function update($admin) {
	$id = (int)$_POST['id'];
	$password = $_POST['password'];
  $fullname = $_POST['fullname'];
  $role = $_POST['role'];
  $result = $admin->update($id, $fullname, $password, $role) ?
    array('code' => 0, 'msg' => '修改成功') : array('code' => 1, 'msg' => '修改失败');
	echo json_encode($result);
}
function del($admin){
	$id = (int)$_REQUEST['id'];
	$result = $admin->delete($id) ?
    array('code' => 0, 'msg' => '修改成功') : array('code' => 1, 'msg' => '修改失败');
	echo json_encode($result);
}
?>