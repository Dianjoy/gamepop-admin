<?php
define('OPTIONS', 'article');
include_once '../../inc/session.php';
?>
<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14-3-18
 * Time: 下午6:25
 * 操作文章分类
 */

include_once "../../inc/Article.class.php";
$article = new Article();

$methods = array(
  'GET' => 'fetch',
  'PATCH' => 'update',
  'POST' => 'create'
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

function fetch($article) {
  $result = $article->get_all_categories();
  echo json_encode(array(
    'total' => count($result),
    'list' => $result,
  ));
}
function create($article, $args) {
  // 新建分类
  $category = (int)$article->add_category($args['label']);

  if ($category) {
    exit(json_encode(array(
      'code' => 0,
      'category' => $category,
      'id' => $category,
    )));
  }

  header("HTTP/1.1 400 Bad Request");
  echo json_encode(array(
    'code' => 1,
    'msg' => '创建分类失败',
  ));
}
function update($article, $args) {
  $url = $_SERVER['PATH_INFO'];
  $id = (int)substr($url, 1);

  $result = $article->update_category($id, $args);
  if ($result) {
    $result = array('code' => 0, 'msg' => '修改成功');
  } else {
    header('HTTP/1.1 400 Bad Request');
    $result = array('code' => 1, 'msg' => '修改失败');
  }
  echo json_encode($result);
}