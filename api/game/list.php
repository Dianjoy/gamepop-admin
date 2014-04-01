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
  $pagesize = isset($args['pagesize']) ? (int)$args['pagesize'] : 20;
  $page = isset($args['page']) ? (int)$args['page'] : 0;
  $keyword = empty($args['keyword']) ? '' : trim(addslashes(strip_tags($args['keyword'])));
  $conditions = array(
    'status' => Game::NORMAL,
  );

  $games = $game->select(Game::$ALL)
    ->where($conditions)
    ->search($keyword)
    ->order(Game::$ORDER_HOT, 'DESC')
    ->execute()
    ->fetchAll(PDO::FETCH_ASSOC);
  $total = count($games);
  $games = array_slice($games, $page * $pagesize, $pagesize);

  // 取每个游戏的文章数量
  $guide_names = array();
  foreach ($games as $row) {
    $guide_names[] = $row[Game::ID];
  }
  include_once "../../inc/Article.class.php";
  $article = new Article();

  $article_number = $article->select($article->count(Game::ID))
    ->where(array(Game::ID => $guide_names), true)
    ->where($conditions)
    ->group(Game::ID)
    ->execute()
    ->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_UNIQUE);
  foreach ($games as &$row) {
    $row['article_number'] = $article_number[$row[Game::ID]];
  }

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
  update($game, $args, '删除成功', '删除失败');
}

function update($game, $args, $success = '更新成功', $error = '更新失败') {
  $game->init_write();
  $url = $_SERVER['PATH_INFO'];
  $id = substr($url, 1);
  $result = $game->update($args)
    ->where(array('guide_name' => $id))
    ->execute();
  Spokesman::judge($result, $success, $error, $args);
}