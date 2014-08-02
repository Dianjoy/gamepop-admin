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
include_once "../../inc/API.class.php";

$api = new API('article|article_wb', array(
  'fetch' => fetch,
  'update' => update,
  'create' => create,
));

function fetch($args) {
  require_once(dirname(__FILE__) . '/../../inc/HTML_To_Markdown.php');
  $article = new Article();

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

  // 取分类
  $category = $article->select(Article::$CATEGORY)
    ->where(array('aid' => $conditions['id']))
    ->fetchAll(PDO::FETCH_ASSOC);
  foreach ($category as $key => $item) {
    $category[$key]['id'] = $item['cid'];
  }
  $result['category'] = $category;

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

function update($args, $attr) {
  require_once "../../inc/Admin.class.php";
  if (Admin::is_outsider() && isset($attr['status'])) {
    header('HTTP/1.1 401 Unauthorized');
    Spokesman::say(array(
      'code' => 1,
      'msg' => '请勿越权操作',
    ));
    exit();
  }
  $article = new Article();
  $conditions = Spokesman::extract();

  // 置顶文章
  if (array_key_exists('top', $attr)) {
    return $article->set_article_top($conditions['id'], $attr['top']);
  }

  $attr['update_editor'] = (int)$_SESSION['id'];
  unset($attr['msg']);
  unset($attr['label']);
  unset($attr['game_name']);
  if (isset($attr['content'])) {
    require_once(dirname(__FILE__) . '/../../inc/MarkdownExtra.inc.php');
    $attr['content'] = str_replace('http://r.yxpopo.com/', '', $attr['content']); // 把资源替换成相对路径
    $attr['content'] = strip_tags($attr['content'], '<table><tr><td><span><video><audio>'); // 只保留特定标签
    $attr['content'] = preg_replace('/<td(.*?)?>(.*?!\[.*?\]\(.*?\).*?)<\/td>/', "<td\$1 markdown=\"1\">\$2</td>", $attr['content']);
    $attr['content'] = \Michelf\MarkdownExtra::defaultTransform($attr['content']);
  }
  if (isset($attr['icon_path_article'])) {
    $attr['icon_path'] = str_replace('http://r.yxpopo.com/', '', $attr['icon_path_article']);
    unset($attr['icon_path_article']);
  }
  $result = $article->update($attr)
    ->where($conditions)
    ->execute();

  if ($attr['icon_path']) {
    $attr['icon_path_article'] = $attr['icon_path'];
  }
  Spokesman::judge($result, '修改成功', '修改失败', $attr);

  if (Admin::is_outsider()) {
    Admin::log_outsider_action($conditions['id'], 'edit');
  }
}

function create($args, $attr) {
  require_once "../../inc/Admin.class.php";
  if (Admin::is_outsider()) {
    header('HTTP/1.1 401 Unauthorized');
    Spokesman::say(array(
      'code' => 1,
      'msg' => '请勿越权操作',
    ));
    exit();
  }
  $article = new Article();

  $attr = array_omit($attr, 'label', 'cate', 'sub', 'path', 'game_name');
  $attr['author'] = $_SESSION['id'];
  $attr['status'] = Article::DRAFT;
  $attr['pub_date'] = empty($attr['pub_date']) ? date('Y-m-d H:i:s') : $attr['pub_date'];
  if (isset($attr['icon_path_article'])) {
    $attr['icon_path'] = str_replace('http://r.yxpopo.com/', '', $attr['icon_path_article']);
    unset($attr['icon_path_article']);
  }
  $id = (int)$article->insert($attr)
    ->execute()
    ->lastInsertId();
  if ($id) {
    $attr['id'] = $id;
  }
  $attr['author'] = $_SESSION['fullname'];
  $attr['icon_path_article'] = $attr['icon_path'];

  Spokesman::judge($id, '创建成功', '创建失败', $attr);

}