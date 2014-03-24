<?php
define('OPTIONS', 'game');
include_once '../../inc/session.php';
?>
<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14-3-12
 * Time: 下午5:15
 */
header("Content-Type:application/json;charset=utf-8");
$m = $_REQUEST['m'];
$all = array('add', 'update', 'del');
if (!in_array($m, $all)) {
  exit(json_encode(array(
    'code' => 1,
    'msg' => '指令错误',
  )));
}
include_once(dirname(__FILE__).'/../../inc/Game.class.php');
$game = new Game();
$m($game);

function del($game) {
  $id = $_REQUEST['id'];
  $result = $game->remove($id) ?
    array('code' => 0, 'msg' => '删除成功') : array('code' => 1, 'msg' => '删除失败');
  echo json_encode($result);
}