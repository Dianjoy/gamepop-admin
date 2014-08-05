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
  $page = isset($args['page']) ? (int)$args['page'] : 0;
  $keyword = $args['keyword'];
  $seq = isset($args['seq']) ? $args['seq'] : 'cid';

  $article = new Article();
  $conditions = Spokesman::extract(true);
  $status = array('status' => Article::NORMAL);

  $total = $article->select($article->count())
    ->from(Article::CATEGORY)
    ->where($conditions)
    ->where($status)
    ->search(array('label' => $keyword))
    ->fetch(PDO::FETCH_COLUMN);

  $result = $article->select('cid', $article->count())
    ->join(Article::ARTICLE_CATEGORY, 'id', 'aid')
    ->where($conditions)
    ->where($status, Article::TABLE)
    ->search(array('label' => $keyword))
    ->group('cid')
    ->order($seq, 'DESC')
    ->limit($pagesize * $page, $pagesize)
    ->fetchAll(PDO::FETCH_ASSOC);

  // 取label
  $ids = array();
  foreach ($result as $category) {
    $ids[] = $category['cid'];
  }
  $labels = $article->select(Article::$ALL_CATEGORY)
    ->where(array('id' => $ids), '', \gamepop\Base::R_IN)
    ->where($status)
    ->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_UNIQUE);

  foreach ($result as $key => $item) {
    if (!$labels[$item['cid']]) {
      unset($result[$key]);
      continue;
    }
    $item['id'] = $item['cid'];
    $item['label'] = $labels[$item['cid']]['label'];
    if (array_key_exists('guide_name', $conditions)) {
      $item['guide_name'] = $conditions['guide_name'];
    }
    $result[$key] = $item;
  }

  // 为了profile中的分类列表

  Spokesman::say(array(
    'total' => $total,
    'list' => array_values($result),
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