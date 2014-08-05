<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14-3-18
 * Time: 下午6:25
 * 操作文章分类
 */

include_once "../../inc/Spokesman.class.php";
include_once "../../inc/Article.class.php";
require_once "../../inc/Admin.class.php";
require_once "../../inc/API.class.php";

$api = new API('article|article_wb', array(
  'fetch' => fetch,
  'update' => update,
  'create' => create,
  'delete' => delete,
));

function create($args, $attr) {
  // 只允许外包用户看分类
  if (Admin::is_outsider()) {
    header('HTTP/1.1 401 Unauthorized');
    exit(json_encode(array(
      'code' => 1,
      'msg' => '没有权限',
    )));
  }

  $article = new Article();
  $label = $attr['label'] ? $attr['label'] : $attr['tag'];
  // 新建分类
  $category = (int)$article->add_category($label);

  Spokesman::judge($category, '创建成功', '创建失败', array(
    'category' => $category,
    'id' => $category,
  ));
}
function delete($article) {
  $args = array(
    'status' => 1,
  );
  update($article, $args, '删除成功', '删除失败');
}
function fetch($args) {
  $pagesize = isset($args['pagesize']) ? (int)$args['pagesize'] : 20;
  $page = (int)$args['page'];
  $keyword = $args['keyword'];

  $article = new Article();
  $conditions = Spokesman::extract(true);
  $status = array('status' => Article::NORMAL);

  if (isset($conditions['guide_name'])) {
    $category = $article->select('cid', $article->count())
      ->join(Article::ARTICLE_CATEGORY, 'id', 'aid')
      ->where($conditions)
      ->where($status)
      ->group('cid')
      ->fetchAll(PDO::FETCH_ASSOC);

    $ids = array();
    foreach ($category as $item) {
      $ids[] = $item['cid'];
    }
    $labels = $article->select(Article::$ALL_CATEGORY)
      ->where($status)
      ->where(array('id' => $ids), '', \gamepop\Base::R_IN)
      ->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_UNIQUE);
    foreach ($category as $key => $item) {
      $item['label'] = $labels[$item['cid']]['label'];
      $item['id'] = $item['cid'];
      $category[$key] = $item;
    }

    Spokesman::say(array('list' => $category));
  }

  $total = $article->select($article->count())
    ->from(Article::CATEGORY)
    ->where($conditions)
    ->where($status)
    ->search(array('label' => $keyword))
    ->fetch(PDO::FETCH_COLUMN);

  $category = $article->select(Article::$ALL_CATEGORY)
    ->where($status)
    ->search(array('label' => $keyword))
    ->order('id', 'DESC')
    ->limit($pagesize * $page, $pagesize)
    ->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_UNIQUE);

  $count = $article->select('cid', $article->count())
    ->join(Article::ARTICLE_CATEGORY, 'id', 'aid')
    ->where($status)
    ->where(array('cid' => array_keys($category)), '', \gamepop\Base::R_IN)
    ->group('cid')
    ->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_UNIQUE);
  foreach ($category as $key => $value) {
    $value['NUM'] = (int)$count[$key];
    $value['id'] = $key;
    // 为了profile中的分类列表
    if (array_key_exists('guide_name', $conditions)) {
      $value['guide_name'] = $conditions['guide_name'];
    }
    $category[$key] = $value;
  }

  Spokesman::say(array(
    'total' => $total,
    'list' => array_values($category),
  ));
}
function update($args, $attr, $success = '修改成功', $error = '修改失败') {
  // 只允许外包用户看分类
  if (Admin::is_outsider()) {
    header('HTTP/1.1 401 Unauthorized');
    exit(json_encode(array(
      'code' => 1,
      'msg' => '没有权限',
    )));
  }
  $article = new Article();
  $conditions = Spokesman::extract();

  $result = $article->update($attr, Article::CATEGORY)
    ->where($conditions)
    ->execute();

  Spokesman::judge($result, $success, $error, $args);
}