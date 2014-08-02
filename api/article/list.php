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
  $total = $article->select($article->count())
    ->where($status, Article::TABLE)
    ->where($conditions)
    ->search($keyword)
    ->fetch(PDO::FETCH_COLUMN);

  if (!isset($args['category']) && !$seq) {
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
  } elseif (isset($args['category'])) {
    $articles = $article->select(Article::$ALL)
      ->where($status, Article::TABLE)
      ->where($conditions)
      ->search($keyword)
      ->order('pub_date')
      ->limit($pagesize * $page, $pagesize)
      ->fetchAll(PDO::FETCH_ASSOC);
  }

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
  $article = new Article();
  $conditions = Spokesman::extract();

  // label 不能在文章列表修改
  unset($args['label']);

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
  $conditions = array_diff_key($conditions, $args);
  if (isset($args['icon_path_article'])) {
    $args['icon_path'] = str_replace('http://r.yxpopo.com/', '', $args['icon_path_article']);
    unset($args['icon_path_article']);
  }
  $args['update_editor'] = (int)$_SESSION['id'];
  $result = $article->update($args)
    ->where($conditions)
    ->execute();

  if ($args['icon_path']) {
    $args['icon_path_article'] = $args['icon_path'];
  }
  Spokesman::judge($result, $success, $error, $args);

  if (Admin::is_outsider()) {
    Admin::log_outsider_action($conditions['id'], 'update', implode(',', array_keys($args)));
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