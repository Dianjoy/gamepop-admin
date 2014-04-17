<?php
define('OPTIONS', 'article');
include_once '../../inc/session.php';
?>
<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14-3-17
 * Time: 下午5:13
 */
include_once "../../inc/Spokesman.class.php";
include_once "../../inc/Article.class.php";
$article = new Article();

$methods = array(
  'GET' => 'fetch',
  'PATCH' => 'update',
);

$args = $_REQUEST;
$request = file_get_contents('php://input');
if ($request) {
  $args = array_merge($_POST, json_decode($request, true));
}

header("Content-Type:application/json;charset=utf-8");
if (!isset($_SERVER['REQUEST_METHOD'], $methods)) {
  header("HTTP/1.1 406 Not Acceptable");
  exit(json_encode(array(
    'code' => 1,
    'msg' => '指令错误',
  )));
}
$method = $methods[$_SERVER['REQUEST_METHOD']];
if ($method) {
  $method($article, $args);
}

function fetch($article, $args) {
  require_once(dirname(__FILE__) . '/../../inc/HTML_To_Markdown.php');
  $conditions = Spokesman::extract();
  $result = $article->select(Article::$DETAIL)
    ->where($conditions, false, Article::TABLE)
    ->execute()
    ->fetch(PDO::FETCH_ASSOC);
  if (get_magic_quotes_gpc()) {
    $result['content'] = stripslashes($result['content']);
  }
  $markdown = new HTML_To_Markdown($result['content']);
  $result['content'] = preg_replace('/]\(\/?([a-z|^(http)]+)/', '](http://r.yxpopo.com/$1', $markdown);

  Spokesman::say($result);
}

function update($article, $args) {
  $args['update_time'] = date('Y-m-d H:i:s');
  $args['update_editor'] = (int)$_SESSION['id'];
  if (isset($args['content'])) {
    require_once(dirname(__FILE__) . '/../../inc/Markdown.inc.php');
    $args['content'] = str_replace('http://r.yxpopo.com/', '', $args['content']);
    $args['content'] = \Michelf\Markdown::defaultTransform($args['content']);
  }
  $conditions = Spokesman::extract();
  $result = $article->update($args)
    ->where($conditions)
    ->execute();
  Spokesman::judge($result, '修改成功', '修改失败');
}