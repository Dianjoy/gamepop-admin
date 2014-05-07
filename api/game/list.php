<?php
define('OPTIONS', 'game|article_wb');
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
include_once "../../inc/Admin.class.php";
$game = new Game();

$args = $_REQUEST;
$request = file_get_contents('php://input');
if ($request) {
  $args = array_merge($_POST, json_decode($request, true));
}

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
  $pagesize = empty($args['pagesize']) ? 20 : (int)$args['pagesize'];
  $page = isset($args['page']) ? (int)$args['page'] : 0;
  $keyword = empty($args['keyword']) ? '' : trim(addslashes(strip_tags($args['keyword'])));
  $conditions = array(
    'status' => Game::NORMAL,
  );

  // 外包人员只能看到自己的游戏
  if (Admin::is_outsider()) {
    $my_games = $game->select(Game::$OUTSIDE)
      ->where(array('user_id' => $_SESSION['id']))
      ->fetchAll(PDO::FETCH_ASSOC);

    $guide_names = array();
    foreach ($my_games as $item) {
      $guide_names[] = $item['guide_name'];
    }
  }

  $games = $game->select(Game::$ALL)
    ->where($conditions)
    ->where(array(Game::ID => $guide_names), '', true)
    ->search($keyword)
    ->fetchAll(PDO::FETCH_ASSOC);
  $total = count($games);
  usort($games, compare_hot);
  $games = array_slice($games, $page * $pagesize, $pagesize);

  // 某些地方检索到这里就OK了，比如文章关联游戏的页面
  if (isset($args['from'])) {
    foreach ($games as $key => $single) {
      $games[$key]['id'] = $single['guide_name'];
      $games[$key]['label'] = $single['game_name'];
    }
    return Spokesman::say(array(
      'total' => $total,
      'list' => $games,
    ));
  }

  // 取每个游戏的文章数量
  $guide_names = array();
  foreach ($games as $row) {
    $guide_names[] = $row[Game::ID];
  }
  include_once "../../inc/Article.class.php";
  $article = new Article();

  $article_number = $article->select(Game::ID, $article->count())
    ->where(array(Game::ID => $guide_names), '', true)
    ->where($conditions)
    ->group(Game::ID)
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
  if (isset($args['status']) && Admin::is_outsider()) {
    Spokesman::judge(false, $success, $error);
    exit();
  }
  $conditions = Spokesman::extract(true);
  if (isset($args['icon_path'])) {
    $args['icon_path'] = str_replace('http://r.yxpopo.com/', '', $args['icon_path']);
  }
  $result = $game->update($args)
    ->where($conditions)
    ->execute();
  Spokesman::judge($result, $success, $error, $args);
}

function compare_hot($a, $b) {
  return (int)$b['hot'] - (int)$a['hot'];
}