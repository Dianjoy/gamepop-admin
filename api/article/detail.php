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
include_once "../../inc/utils.php";
$article = new Article();

$methods = array(
  'GET' => 'fetch',
  'PATCH' => 'update',
  'POST' => 'create',
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

  $conditions = Spokesman::extract();
  $result = $article->select(Article::$DETAIL)
    ->where($conditions, Article::TABLE)
    ->fetch(PDO::FETCH_ASSOC);
  if (get_magic_quotes_gpc()) {
    $result['content'] = stripslashes($result['content']);
  }
  $markdown = new HTML_To_Markdown($result['content']);
  $result['content'] = preg_replace('/]\(\/?(?!http)([a-z]+)/', '](http://r.yxpopo.com/$1', $markdown);
  $result['status'] = (int)$result['status'];
  $result['is_top'] = (int)$result['is_top'];

  // 取相关游戏
  if ($result['guide_name']) {
    require_once(dirname(__FILE__) . '/../../inc/Game.class.php');
    $game = new Game();
    $game = $game->select(Game::$ALL)
      ->where(array(Game::ID => $result[Game::ID]))
      ->fetch(PDO::FETCH_ASSOC);
  }

  // 如果不是抓取的话，还要取作者
  if (!$result['source'] || $result['source'] === 'gamepopo') {
    require_once "../../inc/Admin.class.php";
    $admin = new Admin();
    $author = $admin->select(Admin::$BASE)
      ->where(array('id' => $result['author']))
      ->fetch(PDO::FETCH_ASSOC);
    $result['author'] = $author['fullname'];
  }

  Spokesman::say($game ? array_merge($game, $result) : $result);
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
  $args['update_editor'] = (int)$_SESSION['id'];
  unset($args['msg']);
  unset($args['label']);
  unset($args['game_name']);
  if (isset($args['content'])) {
    require_once(dirname(__FILE__) . '/../../inc/Markdown.inc.php');
    $args['content'] = str_replace('http://r.yxpopo.com/', '', $args['content']); // 把资源替换成相对路径
    $args['content'] = strip_tags($args['content'], '<table><tr><td><span><video><audio>'); // 只保留特定标签
    $args['content'] = \Michelf\Markdown::defaultTransform($args['content']);
  }
  if (isset($args['icon_path_article'])) {
    $args['icon_path'] = str_replace('http://r.yxpopo.com/', '', $args['icon_path_article']);
    unset($args['icon_path_article']);
  }
  $conditions = Spokesman::extract();
  $result = $article->update($args)
    ->where($conditions)
    ->execute();

  if ($args['icon_path']) {
    $args['icon_path_article'] = $args['icon_path'];
  }
  Spokesman::judge($result, '修改成功', '修改失败', $args);

  if (Admin::is_outsider()) {
    Admin::log_outsider_action($conditions['id'], 'edit');
  }
}

function create($article, $args) {
  require_once "../../inc/Admin.class.php";
  if (Admin::is_outsider()) {
    header('HTTP/1.1 401 Unauthorized');
    Spokesman::say(array(
      'code' => 1,
      'msg' => '请勿越权操作',
    ));
    exit();
  }
  $args = array_omit($args, 'label', 'cate', 'sub', 'path', 'game_name');
  $args['author'] = $_SESSION['id'];
  $args['status'] = Article::DRAFT;
  $args['pub_date'] = empty($args['pub_date']) ? date('Y-m-d H:i:s') : $args['pub_date'];
  if (isset($args['icon_path_article'])) {
    $args['icon_path'] = str_replace('http://r.yxpopo.com/', '', $args['icon_path_article']);
    unset($args['icon_path_article']);
  }
  $id = (int)$article->insert($args)
    ->execute()
    ->lastInsertId();
  if ($id) {
    $args['id'] = $id;
  }
  $args['author'] = $_SESSION['fullname'];
  $args['icon_path_article'] = $args['icon_path'];

  Spokesman::judge($id, '创建成功', '创建失败', $args);

}