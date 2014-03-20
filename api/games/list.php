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

include_once "../../inc/Game.class.php";
include_once "../../inc/Article.class.php";
$game = new Game();
$article = new Article();

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
  $method($game, $args, $article);
}

function fetch($game, $args, $article) {
  $pagesize = isset($args['pagesize']) ? (int)$args['pagesize'] : 20;
  $page = isset($args['page']) ? (int)$args['page'] : 0;
  $keyword = empty($args['keyword']) ? '' : trim(addslashes(strip_tags($args['keyword'])));

  $total = $game->get_game_number($keyword);
  $games = $game->get_all_games($pagesize, $page, $keyword);

  if (DEBUG) {
    foreach ($games as &$row) {
      if (substr($row['icon_path'], 0, 7) === 'upload/') {
        $row['icon_path'] = 'http://admin.yxpopo.com/' . $row['icon_path'];
      }
    }
  }

  // 取每个游戏的文章数量
  $guide_names = array();
  foreach ($games as $row) {
    $guide_names[] = $row['guide_name'];
  }
  $article_number = $article->get_article_number_by_id($guide_names);
  foreach ($games as &$row) {
    $row['article_number'] = $article_number[$row['guide_name']];
  }


  $result = array(
    'total' => $total,
    'list' => $games
  );

  echo json_encode($result);
}

function update($game, $args) {
  $game->initWrite();
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