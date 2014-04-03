<?php
define('OPTIONS', 'article');
include_once '../../inc/session.php';
?>
<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14-3-17
 * Time: 上午10:11
 */

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
  $pagesize = isset($args['pagesize']) ? (int)$args['pagesize'] : 20;
  $page = isset($args['page']) ? (int)$args['page'] : 0;
  $status = array(
    'status' => 0,
  );
  $conditions = Spokesman::extract(true);
  $articles = $article->select(Article::$ALL)
    ->where($status, false, Article::TABLE)
    ->where($conditions)
    ->search($args['keyword'])
    ->fetchAll(PDO::FETCH_ASSOC);
  $total = count($articles);
  $articles = array_slice($articles, $page * $pagesize, $pagesize);

  require_once "../../inc/Admin.class.php";
  $admin = new Admin();
  $editors = $admin->select(Admin::$BASE)
    ->where(array('role' => Admin::EDITOR))
    ->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_UNIQUE);
  foreach ($articles as &$article) {
    $article['update_editor_label'] = $editors[$article['update_editor']];
  }

  require_once "../../inc/Game.class.php";
  $game = new Game();
  $guide_names = array();
  foreach ($articles as $item) {
    $guide_names[] = $item['guide_name'];
  }
  $games = $game->select(Game::$INFO)
    ->where(array('guide_name' => $guide_names), true)
    ->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_UNIQUE);
  foreach ($articles as $key => $item) {
    $item['game_name'] = $games[$item['guide_name']]['game_name'];
    $articles[$key] = $item;
  }

  echo json_encode(array(
    'total' => $total,
    'list' => $articles,
  ));
}

function update($article, $args, $success = '更新成功', $error = '更新失败') {
  $conditions = Spokesman::extract();

  $result = $article->update($args)
    ->where($conditions)
    ->execute();
  Spokesman::judge($result, $success, $error, $args);
}

function delete($article) {
  $args = array(
    'status' => 1,
  );
  update($article, $args, '删除成功', '删除失败');
}