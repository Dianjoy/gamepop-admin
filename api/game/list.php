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

  $total = $game->select($game->count())
    ->where($conditions)
    ->where(array(Game::ID => $guide_names), '', \gamepop\Base::R_IN)
    ->search($keyword)
    ->fetch(PDO::FETCH_COLUMN);
  $games = $game->select(Game::$ALL)
    ->where($conditions)
    ->where(array(Game::ID => $guide_names), '', \gamepop\Base::R_IN)
    ->search($keyword)
    ->order('hot')
    ->limit($page * $pagesize, $pagesize)
    ->fetchAll(PDO::FETCH_ASSOC);

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

  // 取游戏tag
  $tags = array();
  // 取每个游戏的文章数量
  $guide_names = array();
  foreach ($games as $row) {
    $guide_names[] = $row[Game::ID];
    $tags = array_merge($tags, explode('|', $row['tags']));
  }
  $tags = array_unique($tags);
  include_once "../../inc/Article.class.php";
  $article = new Article();

  $article_number = $article->select(Game::ID, $article->count())
    ->where(array(Game::ID => $guide_names), '', \gamepop\Base::R_IN)
    ->where($conditions)
    ->group(Game::ID)
    ->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_UNIQUE);
  foreach ($games as &$row) {
    $row['os_android'] = (int)$row['os_android'];
    $row['os_ios'] = (int)$row['os_ios'];
    $row['article_number'] = $article_number[$row[Game::ID]];
  }

  $tags = $game->select(Game::$TAGS)
    ->where(array('id' => $tags), '', \gamepop\Base::R_IN)
    ->where(array('status' => 0))
    ->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_UNIQUE);
  foreach ($games as &$row) {
    $arr = array_filter(explode('|', $row['tags']));
    foreach ($arr as $key => $tag) {
      $item = $tags[$tag];
      $item['id'] = $tag;
      $arr[$key] = $item;
    }
    $row['tags'] = $arr;
  }


  $result = array(
    'total' => $total,
    'list' => $games
  );

  Spokesman::say($result);
}

function delete($game) {
  // 删掉m_pack_guide对应记录
  $conditions = Spokesman::extract(true);
  $game->delete(Game::PACK)
    ->where($conditions)
    ->execute();

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
  // 为了在上传的时候区分
  if (isset($args['icon_path_article'])) {
    $args['icon_path'] = str_replace('http://r.yxpopo.com/', '', $args['icon_path_article']);
    unset($args['icon_path_article']);
  }
  $result = $game->update($args)
    ->where($conditions)
    ->execute();
  if (isset($args['tags'])) {
    $tags = explode('|', $args['tags']);
    $tags = $game->select(Game::$TAGS)
      ->where(array('id' => $tags), '', \gamepop\Base::R_IN)
      ->where(array('status' => 0))
      ->fetchAll(PDO::FETCH_ASSOC);
    $args['tags'] = $tags;
  }
  // 为了显示
  if ($args['icon_path']) {
    $args['icon_path_article'] = $args['icon_path'];
  }
  Spokesman::judge($result, $success, $error, $args);
}

function compare_hot($a, $b) {
  return (int)$b['hot'] - (int)$a['hot'];
}