<?php
define('OPTIONS', 'game|article_wb');
include_once '../../inc/session.php';
?>
<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14-4-28
 * Time: 下午6:34
 */
include_once "../../inc/Spokesman.class.php";
include_once "../../inc/Article.class.php";
$article = new Article();

$args = $_REQUEST;
$request = file_get_contents('php://input');
if ($request) {
  $args = array_merge($_POST, json_decode($request, true));
}
switch ($_SERVER['REQUEST_METHOD']) {
  case 'GET':
    fetch($article, $args);
    break;

  case 'PATCH':
    update($article, $args);
    break;

  default:
    header("HTTP/1.1 406 Not Acceptable");
    break;
}

function fetch($article, $args) {
  $conditions = Spokesman::extract(true);
  $conditions['is_top'] = 1;
  $conditions['status'] = Article::NORMAL;

  $articles = $article->select(Article::$TOP)
    ->where($conditions)
    ->fetchAll(PDO::FETCH_ASSOC);
  foreach ($articles as $article) {
    $article['is_top'] = (int)$article['is_top'];
  }

  usort($articles, compare);

  $result = array(
    'total' => count($articles),
    'list' => array_values($articles),
  );

  Spokesman::say($result);
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
  $conditions = Spokesman::extract(true);
  $result = $article->update($args)
    ->where($conditions)
    ->execute();
  Spokesman::judge($result, $success, $error, $args);

  if (Admin::is_outsider()) {
    Admin::log_outsider_action($conditions['id'], 'update', implode(',', array_keys($args)));
  }
}

function compare($a, $b) {
  return (int)$a['seq'] - (int)$b['seq'];
}