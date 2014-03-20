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
$DB = include_once "../../inc/pdo.php";
include_once "../../inc/Article.class.php";
$article = new Article($DB);

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
  $method($article, $args);
}

function fetch($article, $args) {
  $path = $args['id'];
  $params = explode('/', $path);
  $id = $params[0];

  $pagesize = isset($args['pagesize']) ? (int)$args['pagesize'] : 20;
  $page = isset($args['page']) ? (int)$args['page'] : 0;
  $keyword = empty($args['keyword']) ? '' : trim(addslashes(strip_tags($args['keyword'])));

  $total = $article->get_article_number_by_id($id, $keyword);
  $total = (int)$total[$id];
  if ($id) {
    $articles = $article->get_articles_by_game($id, $pagesize, $page, $keyword);
  } else {
    $articles = $article->get_articles($pagesize, $page, $keyword);
  }

  echo json_encode(array(
    'total' => $total,
    'list' => $articles,
  ));
}

function update($article, $args) {
  $url = $_SERVER['PATH_INFO'];
  $id = substr($url, 1);

  // 如果要修改cate的话，则需要判断是否需要新建一个


  $result = $article->update($id, $args) ? array(
    'code' => 0,
    'msg' => '更新成功',
  ) : array(
    'code' => 1,
    'msg' => '更新失败',
  );
  echo json_encode($result);
}