<?php
define('OPTIONS', 'game');
include_once '../../inc/session.php';
?>
<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14-3-31
 * Time: 下午1:44
 */
include_once "../../inc/Spokesman.class.php";
include_once "../../inc/Game.class.php";
$game = new Game();

$args = $_REQUEST;
$request = file_get_contents('php://input');
if ($request) {
  $args = array_merge($_POST, json_decode($request, true));
}
header("Content-Type:application/json;charset=utf-8");
switch ($_SERVER['REQUEST_METHOD']) {
  case 'GET':
    fetch($game, $args);
    break;

  case 'PATCH':
    update($game, $args);
    break;

  case 'DELETE':
    delete($game);
    break;

  case 'POST':
    create($game, $args);
    break;

  default:
    header("HTTP/1.1 406 Not Acceptable");
    break;
}

function fetch($game, $args) {
  $conditions = array(
    'status' => Game::NORMAL,
    'guide_name' => $args['id'],
  );
  $games = $game->select(Game::$SLIDE)
    ->where($conditions)
    ->order('`seq`')
    ->execute()
    ->fetchAll(PDO::FETCH_ASSOC);
  $total = count($games);

  $result = array(
    'total' => $total,
    'list' => $games
  );

  Spokesman::say($result);
}

function delete($game) {
  $args = array(
    'status' => 1,
  );
  update($game, $args, Game::SLIDE, '删除成功', '删除失败');
}

function update($game, $args, $table = '', $success = '更新成功', $error = '更新失败') {
  $url = $_SERVER['PATH_INFO'];
  $id = substr($url, 1);
  $result = $game->update($args, $table)
    ->where(array('id' => $id))
    ->execute();
  Spokesman::judge($result, $success, $error);
}

function create($game, $args) {
  $result = $game->insert($args)
    ->execute()
    ->lastInsertId();
  $args = array_merge(array('id' => $result), $args);
  Spokesman::judge($result, '创建成功', '创建失败', $args);
}