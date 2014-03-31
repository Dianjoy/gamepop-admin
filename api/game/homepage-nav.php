<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14-3-31
 * Time: 下午1:44
 */
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

  default:
    header("HTTP/1.1 406 Not Acceptable");
    break;
}

function fetch($game, $args) {
  /*$conditions = array(
    'status' => Game::NORMAL,
    'guide_name' => $args['id'],
  );

  $games = $game->select(Game::$SLIDE)
    ->where($conditions)
    ->order('`seq`')
    ->execute()
    ->fetchAll(PDO::FETCH_ASSOC);
  $total = count($games);

  if (DEBUG) {
    foreach ($games as $key => $row) {
      if (substr($row['image'], 0, 7) === 'upload/') {
        $games[$key]['image'] = 'http://admin.yxpopo.com/' . $row['image'];
      }
    }
  }*/

  $result = array(
    'total' => $total,
    'list' => $games
  );

  echo json_encode($result);
}

function delete($game) {
  $args = array(
    'status' => 1,
  );
  update($game, $args);
}

function update($game, $args, $success = '更新成功', $error = '更新失败') {
  $game->init_write();
  $url = $_SERVER['PATH_INFO'];
  $id = substr($url, 1);
  $result = $game->update($id, $args) ? array(
    'code' => 0,
    'msg' => $success,
  ) : array(
    'code' => 1,
    'msg' => $error,
  );
  echo json_encode($result);
}