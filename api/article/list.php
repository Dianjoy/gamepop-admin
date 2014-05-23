<?php
define('OPTIONS', 'article|article_wb');
include_once '../../inc/session.php';
?>
<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14-3-17
 * Time: 上午10:11
 */

include_once "../../inc/utils.php";
include_once "../../inc/Spokesman.class.php";
include_once "../../inc/Article.class.php";
$article = new Article();

$args = $_REQUEST;
$request = file_get_contents('php://input');
if ($request) {
  $args = array_merge($_POST, json_decode($request, true));
}

header("Content-Type:application/json;charset=utf-8");
switch ($_SERVER['REQUEST_METHOD']) {
  case 'GET':
    fetch($article, $args);
    break;

  case 'PATCH':
    update($article, $args);
    break;

  case 'DELETE':
    delete($article);
    break;

  default:
    header("HTTP/1.1 406 Not Acceptable");
    break;
}

function fetch($article, $args) {
  $pagesize = empty($args['pagesize']) ? 20 : (int)$args['pagesize'];
  $page = isset($args['page']) ? (int)$args['page'] : 0;
  $keyword = $args['keyword'];
  $compare = 'compare' . (isset($args['seq']) ? '_' . $args['seq'] : '');
  $status = array(
    'status' => 0,
  );
  $args = array_omit($args, 'page', 'pagesize', 'keyword', 'id', 'path', 'seq');
  $conditions = Spokesman::extract(true);
  $articles = $article->select(Article::$ALL)
    ->where($status, Article::TABLE)
    ->where(array_merge($conditions, $args))
    ->search($keyword)
    ->fetchAll(PDO::FETCH_ASSOC);
  usort($articles, $compare);
  $total = count($articles);
  $articles = array_slice($articles, $page * $pagesize, $pagesize);

  // 读取作者，用作者名取代标记
  $editors = array();
  foreach ($articles as $item) {
    if (!$item['source']) {
      $editors[] = $item['author'];
    }
    if ($item['update_editor']) {
      $editors[] = $item['update_editor'];
    }
  }
  $editors = array_unique($editors);
  if (count($editors)) {
    require_once "../../inc/Admin.class.php";
    $admin = new Admin();
    $editors = $admin->select(Admin::$BASE)
      ->where(array('id', $editors), '', true)
      ->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_UNIQUE);
    foreach ($articles as $key => $article) {
      $articles[$key]['update_editor'] = $editors[$article['update_editor']];
      if (!$article['source']) {
        $articles[$key]['author'] = $editors[$article['source']];
      }
    }
  }

  require_once "../../inc/Game.class.php";
  $game = new Game();
  $guide_names = array();
  foreach ($articles as $item) {
    $guide_names[] = $item['guide_name'];
  }
  $games = $game->select(Game::$ALL)
    ->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_UNIQUE);
  foreach ($articles as $key => $item) {
    $item['game_name'] = $games[$item['guide_name']]['game_name'];
    $item['is_top'] = (int)$item['is_top'];
    $articles[$key] = $item;
  }

  Spokesman::say(array(
    'total' => $total,
    'list' => $articles,
  ));
}

function update($article, $args, $success = '更新成功', $error = '更新失败') {
  require_once "../../inc/Admin.class.php";
  if (Admin::is_outsider() && isset($args['status'])) {
    header('HTTP/1.1 401 Unauthorized');
    Spokesman::say(array(
      'code' => 1,
      'msg' => '请勿越权操作',
    ));
    exit();
  }

  $conditions = Spokesman::extract();
  // label 不能在文章列表修改
  unset($args['label']);
  // 去掉条件中和更新中重复的键
  $conditions = array_diff_key($conditions, $args);
  if (isset($args['icon_path'])) {
    $args['icon_path'] = str_replace('http://r.yxpopo.com/', '', $args['icon_path']);
  }
  $result = $article->update($args)
    ->where($conditions)
    ->execute();
  Spokesman::judge($result, $success, $error, $args);

  if (Admin::is_outsider()) {
    Admin::log_outsider_action($conditions['id'], 'update', implode(',', array_keys($args)));
  }
}

function delete($article) {
  $args = array(
    'status' => 1,
    'update_time' => date('Y-m-d H:i:s'),
    'update_editor' => (int)$_SESSION['id'],
  );
  update($article, $args, '删除成功', '删除失败');
}

function compare($a, $b) {
  if ($a['category'] == 0 && $b['category'] == 0 || $a['category'] != 0 && $b['category'] != 0) {
    return strtotime($b['pub_date']) - strtotime($a['pub_date']);
  } else if ($a['category'] == 0) {
    return -1;
  } else {
    return 1;
  }
}
function compare_seq($a, $b) {
  return (int)$a['seq'] - (int)$b['seq'];
}