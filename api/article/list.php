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
  $keyword = empty($args['keyword']) ? '' : trim(addslashes(strip_tags($args['keyword'])));
  $status = array(
    'status' => 0,
  );
  $conditions = array();
  foreach (array('game', 'category', 'author', 'id') as $row) {
    if (isset($args[$row])) {
      $conditions[$row === 'game' || $row === 'id' ? 'guide_name' : $row] = $args[$row];
    }
  }
  $articles = $article->select(Article::$ALL)
    ->where($status, false, Article::TABLE)
    ->where($conditions)
    ->search($keyword)
    ->execute()
    ->fetchAll(PDO::FETCH_ASSOC);
  $total = count($articles);
  $articles = array_slice($articles, $page * $pagesize, $pagesize);

  require_once "../../inc/Admin.class.php";
  $admin = new Admin();
  $editors = $admin->select(Admin::$BASE)
    ->where(array('role' => Admin::EDITOR))
    ->execute()
    ->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_UNIQUE);
  foreach ($articles as &$article) {
    $article['update_editor_label'] = $editors[$article['update_editor']];
  }

  echo json_encode(array(
    'total' => $total,
    'list' => $articles,
  ));
}

function update($article, $args, $success = '更新成功', $error = '更新失败') {
  $url = $_SERVER['PATH_INFO'];
  $id = substr($url, 1);
  $args['update_time'] = date('Y-m-d H:i:s');
  $args['update_editor'] = (int)$_SESSION['id'];
  if (isset($args['label'])) {
    unset($args['label']);
  }
  if (isset($args['content'])) {
    require_once('Markdown.inc.php');
    $args['content'] = \Michelf\Markdown::defaultTransform($args['content']);
  }

  $result = $article->update($args)
    ->where(array('id' => $id))
    ->execute();
  if ($result) {
    echo json_encode(array(
      'code' => 0,
      'msg' => $success,
    ));
  } else {
    header("HTTP/1.1 400 Bad Request");
    echo json_encode(array(
      'code' => 1,
      'msg' => $error,
    ));
  }
}

function delete($article) {
  $args = array(
    'status' => 1,
  );
  update($article, $args, '删除成功', '删除失败');
}