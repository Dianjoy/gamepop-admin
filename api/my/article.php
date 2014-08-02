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
    'status' => 0,
  );

  $total = $article->select($article->count())
    ->where($status)
    ->search($keyword)
    ->fetch(PDO::FETCH_COLUMN);

  $articles = $article->select(Article::$ALL)
    ->where($status, Article::TABLE)
    ->search($keyword)
    ->order('id')
    ->limit($page * $pagesize, $pagesize)
    ->fetchAll(PDO::FETCH_ASSOC);

  $articles = $article->fetch_meta_data($articles);

  Spokesman::say(array(
    'total' => $total,
    'list' => $articles,
  ));
}

function update($args, $attr, $success = '更新成功', $error = '更新失败') {
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
  $article = new Article();

  // 分类单独存到t_category里
  if (array_key_exists('category', $attr)) {
    $new = explode('|', $attr['category']);
    $article->delete(Article::ARTICLE_CATEGORY)
      ->where(array('aid' => $conditions['id']))
      ->execute();
    $categories = array();
    foreach ($new as $category) {
      $categories[] = array(
        'aid' => $conditions['id'],
        'cid' => $category,
      );
    }
    $article->insert($categories, Article::ARTICLE_CATEGORY)
      ->execute()
      ->getResult();
    unset($attr['category']);
  }

  // 更新置顶信息
  if (array_key_exists('top', $attr)) {
    $pub_date = $article->select('pub_date')
      ->where($conditions)
      ->fetch(PDO::FETCH_COLUMN);
    // 以上线时间和当前时间较晚者为准
    $now = date('Y-m-d H:i:s');
    $start_date = $pub_date > $now ? $pub_date : $now;
    $end_date = date('Y-m-d H:i:s', strtotime($start_date) + 86400 * 7);
    if ($attr['top']) {
      $array = array(
        'aid' => $conditions['id'],
        'start_time' => $start_date,
        'end_time' => $end_date,
      );
      $result = $article->insert($array, Article::TOP)
        ->execute()
        ->getResult();
    } else {
      $result = $article->update(array('status' => 1), Article::TOP)
        ->where(array('aid' => $conditions['id']))
        ->where(array('end_time' => $now), '', \gamepop\Base::R_MORE_EQUAL)
        ->execute();
    }
    $attr['top'] = (int)$attr['top'];
    Spokesman::judge($result, '修改成功', '修改失败', $attr);
    exit();
  }

  // 去掉条件中和更新中重复的键
  $conditions = array_diff_key($conditions, $attr);
  if (array_key_exists('icon_path_article', $attr)) {
    $attr['icon_path'] = str_replace('http://r.yxpopo.com/', '', $attr['icon_path_article']);
    unset($attr['icon_path_article']);
  }

  $result = $article->update($attr)
    ->where($conditions)
    ->execute();

  if ($attr['icon_path']) {
    $attr['icon_path_article'] = $attr['icon_path'];
  }
  if (is_array($categories)) {
    $categories = $article->select(Article::$ALL_CATEGORY)
      ->where(array('id' => $new), '', \gamepop\Base::R_IN)
      ->fetchAll(PDO::FETCH_ASSOC);
    $attr['category'] = $categories;
  }
  Spokesman::judge($result, $success, $error, $attr);

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