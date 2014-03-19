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

function fetch($article) {
  $result = $article->get_all_categories();
  echo json_encode($result);
}