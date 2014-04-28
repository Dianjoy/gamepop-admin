<?php
define('OPTIONS', 'article|article_wb');
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
  require_once(dirname(__FILE__) . '/../../inc/Game.class.php');

  $conditions = Spokesman::extract();
  $result = $article->select(Article::$DETAIL)
    ->where($conditions, Article::TABLE)
    ->fetch(PDO::FETCH_ASSOC);
  if (get_magic_quotes_gpc()) {
    $result['content'] = stripslashes($result['content']);
  }
  $markdown = new HTML_To_Markdown($result['content']);
  $result['content'] = preg_replace('/]\(\/?([a-z|^(http)]+)/', '](http://r.yxpopo.com/$1', $markdown);

  $game = new Game();
  $game = $game->select(Game::$ALL)
    ->where(array(Game::ID => $result[Game::ID]))
    ->fetch(PDO::FETCH_ASSOC);

  Spokesman::say(array_merge($game, $result));
}

function update($article, $args) {
  require_once "../../inc/Admin.class.php";
  if (Admin::is_outsider() && isset($args['status'])) {
    header('HTTP/1.1 401 Unauthorized');
    Spokesman::say(array(
      'code' => 1,
      'msg' => '请勿越权操作',
    ));
    exit();
  }
  $args['update_time'] = date('Y-m-d H:i:s');
  $args['update_editor'] = (int)$_SESSION['id'];
  if (isset($args['content'])) {
    require_once(dirname(__FILE__) . '/../../inc/Markdown.inc.php');
    $args['content'] = str_replace('http://r.yxpopo.com/', '', $args['content']); // 把资源替换成相对路径
    $args['content'] = strip_tags($args['content'], '<table><tr><td><span><video><audio>'); // 过滤掉所有script标签
    $args['content'] = \Michelf\Markdown::defaultTransform($args['content']);
  }
  $conditions = Spokesman::extract();
  $result = $article->update($args)
    ->where($conditions)
    ->execute();
  Spokesman::judge($result, '修改成功', '修改失败');

  if (Admin::is_outsider()) {
    Admin::log_outsider_action($conditions['id'], 'edit');
  }
}