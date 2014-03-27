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

  case 'POST':
    create($article, $args);
    break;

  case 'DELETE':
    delete($article, $args);
    break;

  default:
    header("HTTP/1.1 406 Not Acceptable");
    break;
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
function delete($article) {
  $args = array(
    'status' => 1,
  );
  update($article, $args, '删除成功', '删除失败');
}
function fetch($article, $args) {
  $status = array(
    'status' => Article::NORMAL,
  );
  $conditions = array();
  foreach (array('game', 'category', 'author', 'id') as $row) {
    if (isset($args[$row])) {
      $conditions[$row === 'game' || $row === 'id' ? 'guide_name' : $row] = $args[$row];
    }
  }
  $result = $article->select(Article::$ALL_CATEGORY, $article->count())
    ->where($conditions)
    ->where($status, false, Article::TABLE)
    ->group('id', Article::CATEGORY)
    ->execute()
    ->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode(array(
    'total' => count($result),
    'list' => $result,
  ));
}
function update($article, $args, $success = '修改成功', $error = '修改失败') {
  $url = $_SERVER['PATH_INFO'];
  $id = (int)substr($url, 1);

  $result = $article->update_category($id, $args);
  if ($result) {
    $result = array('code' => 0, 'msg' => $success);
  } else {
    header('HTTP/1.1 400 Bad Request');
    $result = array('code' => 1, 'msg' => $error);
  }
  echo json_encode($result);
}