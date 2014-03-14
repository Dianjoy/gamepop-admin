<?php
define('OPTIONS', 'root');
include_once '../../inc/session.php';
?>
<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14-3-7
 * Time: 下午6:10
 */

$DB = include_once "../../inc/pdo.php";
$apk = include_once "../../inc/apk.php";
include_once "../../inc/Template.class.php";
include_once "../../inc/Game.class.php";
$game = new Game($DB);

$methods = array(
  'GET' => 'fetch',
  'PATCH' => 'update',
);
$args = $_REQUEST;
$request = file_get_contents('php://input');
if ($request) {
  $args = array_merge($_POST, json_decode($request, true));
}
$method = $methods[$_SERVER['REQUEST_METHOD']];
header("Content-Type:application/json;charset=utf-8");
if ($method) {
  $method($game, $args);
}

function fetch($game, $args) {
  $pagesize = isset($args['pagesize']) ? (int)$args['pagesize'] : 20;
  $page = isset($args['page']) ? (int)$args['page'] : 0;
  $keyword = empty($args['keyword']) ? '' : trim(addslashes(strip_tags($args['keyword'])));

  $total = $game->get_game_number($keyword);
  $games = $game->get_all_games($pagesize, $page, $keyword);

  $result = array(
    'total' => $total,
    'list' => $games
  );

  echo json_encode($result);
}

function update($game, $args) {
  $url = $_SERVER['PATH_INFO'];
  $id = substr($url, 1);
  $result = $game->update($id, $args) ? array(
    'code' => 0,
    'msg' => '更新成功',
  ) : array(
    'code' => 1,
    'msg' => '更新失败',
  );
  echo json_encode($result);
}