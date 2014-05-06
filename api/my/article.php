<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14-3-17
 * Time: 上午10:11
 */

include_once "../../inc/utils.php";
include_once "../../inc/API.class.php";
include_once "../../inc/Spokesman.class.php";
include_once "../../inc/Article.class.php";


$api = new API('article', array(
  'fetch' => fetch,
  'update' => update,
  'delete' => delete,
));

function fetch($args) {
  $article = new Article();
  $pagesize = isset($args['pagesize']) ? (int)$args['pagesize'] : 20;
  $page = isset($args['page']) ? (int)$args['page'] : 0;
  $keyword = $args['keyword'];
  $status = array(
    'author' => $_SESSION['id'],
  );
  $articles = $article->select(Article::$ALL)
    ->where($status, Article::TABLE)
    ->search($keyword)
    ->fetchAll(PDO::FETCH_ASSOC);
  usort($articles, compare);
  $total = count($articles);
  $articles = array_slice($articles, $page * $pagesize, $pagesize);

  // 读取作者，用作者名取代标记
  $editors = array();
  foreach ($articles as $item) {
    if ($item['update_editor']) {
      $editors[] = $item['update_editor'];
    }
  }
  $editors = array_unique($editors);
  if (count($editors)) {
    require_once "../../inc/Admin.class.php";
    $admin = new Admin();
    $editors = $admin->select(Admin::$BASE)
      ->where(array('id', $editors), '', true)
      ->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_UNIQUE);
    foreach ($articles as $key => $article) {
      $articles[$key]['update_editor'] = $editors[$article['update_editor']];
    }
  }

  require_once "../../inc/Game.class.php";
  $game = new Game();
  $guide_names = array();
  foreach ($articles as $item) {
    $guide_names[] = $item['guide_name'];
  }
  $games = $game->select(Game::$ALL)
    ->where(array('guide_name' => $guide_names), '', true)
    ->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_UNIQUE);
  foreach ($articles as $key => $item) {
    $item['game_name'] = $games[$item['guide_name']]['game_name'];
    $item['is_top'] = (int)$item['is_top'];
    $item['status'] = (int)$item['status'];
    $articles[$key] = $item;
  }

  Spokesman::say(array(
    'total' => $total,
    'list' => $articles,
  ));
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

  $conditions = Spokesman::extract();
  // label 不能在文章列表修改
  unset($args['label']);
  // 去掉条件中和更新中重复的键
  $conditions = array_diff_key($conditions, $args);

  $result = $article->update($args)
    ->where($conditions)
    ->execute();
  Spokesman::judge($result, $success, $error, $args);

  if (Admin::is_outsider()) {
    Admin::log_outsider_action($conditions['id'], 'update', implode(',', array_keys($args)));
  }
}

function delete($article) {
  $args = array(
    'status' => 1,
    'update_time' => date('Y-m-d H:i:s'),
    'update_editor' => (int)$_SESSION['id'],
  );
  update($article, $args, '删除成功', '删除失败');
}

function compare($a, $b) {
  return strtotime($b['update_time']) - strtotime($a['update_time']);
}