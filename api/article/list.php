<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14-3-17
 * Time: 上午10:11
 */

include_once "../../inc/utils.php";
include_once "../../inc/Spokesman.class.php";
include_once "../../inc/Article.class.php";
include_once "../../inc/API.class.php";

$api = new API('article|article_wb', array(
  'fetch' => fetch,
  'delete' => delete,
  'update' => update,
));

function fetch($args) {
  $article = new Article();
  $pagesize = empty($args['pagesize']) ? 20 : (int)$args['pagesize'];
  $page = isset($args['page']) ? (int)$args['page'] : 0;
  $keyword = $args['keyword'];
  $seq = isset($args['seq']) ? $args['seq'] : '';
  $status = array(
    'status' => (int)$args['status'],
  );
  $args = array_omit($args, 'page', 'pagesize', 'keyword', 'id', 'path', 'seq', 'status');
  $conditions = Spokesman::extract(true);
  $conditions = array_merge($conditions, $args);
  if (isset($args['update_editor'])) {
    $status['update_editor'] = $args['update_editor'];
    unset($args['update_editor']);
  }
  if (isset($conditions['category'])) {
    $category = $conditions['category'];
    unset($conditions['category']);

    $total = $article->select($article->count())
      ->join(Article::ARTICLE_CATEGORY, 'id', 'aid')
      ->where(array('cid' => $category))
      ->where($status, Article::TABLE)
      ->where($conditions)
      ->search($keyword)
      ->fetch(PDO::FETCH_COLUMN);

    $articles = $article->select(Article::$ALL)
      ->join(Article::ARTICLE_CATEGORY, 'id', 'aid')
      ->where($status, Article::TABLE)
      ->where(array('cid' => $category))
      ->where($conditions)
      ->search($keyword)
      ->order('pub_date')
      ->limit($pagesize * $page, $pagesize)
      ->fetchAll(PDO::FETCH_ASSOC);
  } else {
    $total = $article->select($article->count())
      ->where($status, Article::TABLE)
      ->where($conditions)
      ->search($keyword)
      ->fetch(PDO::FETCH_COLUMN);

    if (!$seq) {
      $articles = $article->select(Article::$ALL, 'CEIL(`category`/1000) AS num')
        ->where($status, Article::TABLE)
        ->where($conditions)
        ->search($keyword)
        ->order('num', 'ASC')
        ->order('pub_date')
        ->limit($pagesize * $page, $pagesize)
        ->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($seq == 'seq') {
      $articles = $article->select(Article::$ALL)
        ->where($status, Article::TABLE)
        ->where($conditions)
        ->search($keyword)
        ->order('seq', 'ASC')
        ->limit($pagesize * $page, $pagesize)
        ->fetchAll(PDO::FETCH_ASSOC);
    }
  }

  $articles = $article->fetch_meta_data($articles);

  Spokesman::say(array(
    'total' => $total,
    'list' => $articles,
  ));
}

function update($args, $attr, $success = '更新成功', $error = '更新失败') {
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

  // 分类单独存到t_category里
  if (array_key_exists('category', $attr)) {
    $category = $article->update_category($conditions['id'], $attr['category']);
    unset($attr['category']);
  }

  // label 不能在文章列表修改
  unset($attr['label']);

  // 更新置顶信息
  if (array_key_exists('top', $attr)) {
    return $article->set_article_top($conditions['id'], $attr['top']);
  }

  // 去掉条件中和更新中重复的键
  $conditions = array_diff_key($conditions, $attr);
  if (isset($attr['icon_path_article'])) {
    $attr['icon_path'] = str_replace('http://r.yxpopo.com/', '', $attr['icon_path_article']);
    unset($attr['icon_path_article']);
  }
  $attr['update_editor'] = (int)$_SESSION['id'];
  $result = $article->update($attr)
    ->where($conditions)
    ->execute();

  if ($attr['icon_path']) {
    $attr['icon_path_article'] = $attr['icon_path'];
  }
  if ($category) {
    $attr['category'] = $category;
  }
  Spokesman::judge($result, $success, $error, $attr);

  if (Admin::is_outsider()) {
    Admin::log_outsider_action($conditions['id'], 'update', implode(',', array_keys($attr)));
  }
}

function delete($args) {
  $attr = array(
    'status' => 1,
    'update_time' => date('Y-m-d H:i:s'),
    'update_editor' => (int)$_SESSION['id'],
  );
  update($args, $attr, '删除成功', '删除失败');
}

function compare($a, $b) {
  if ($a['category'] == 0 && $b['category'] == 0 || $a['category'] != 0 && $b['category'] != 0) {
    return strtotime($b['pub_date']) - strtotime($a['pub_date']);
  } else if ($a['category'] == 0) {
    return -1;
  } else {
    return 1;
  }
}
function compare_seq($a, $b) {
  return (int)$a['seq'] - (int)$b['seq'];
}