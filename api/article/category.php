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

  // 取某游戏下所有分类的数据，多用与game/profile等页面
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
      ->where(array('id' => $ids), '', \gamepop\Base::R_IN)
      ->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_UNIQUE);
    foreach ($category as $key => $item) {
      $item['label'] = $labels[$item['cid']]['label'];
      $item['id'] = $item['cid'];
      // 为了profile中的分类列表
      $item['guide_name'] = $conditions['guide_name'];
      $category[$key] = $item;
    }

    Spokesman::say(array('list' => $category));
  }

  // 不分页的取全部，多用于文章关联类型
  if ($pagesize === 0) {
    $category = $article->select(Article::$ALL_CATEGORY)
      ->where($status)
      ->search(array('label' => $keyword))
      ->fetchAll(PDO::FETCH_ASSOC);
    Spokesman::say(array('list' => $category));
  }

  // 详情列表，包括文章数量多用于列表页
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

  // 取父分类
  $parent = array();
  foreach ($category as $item) {
    $parent[] = $item['parent'];
  }
  $parent = array_unique(array_filter($parent));
  $parent = $article->select(Article::$ALL_CATEGORY)
    ->where(array('id' => $parent), '', \gamepop\Base::R_IN)
    ->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_UNIQUE);

  // 取文章数
  $count = $article->select('cid', $article->count())
    ->join(Article::ARTICLE_CATEGORY, 'id', 'aid')
    ->where($status)
    ->where(array('cid' => array_keys($category)), '', \gamepop\Base::R_IN)
    ->group('cid')
    ->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_UNIQUE);

  foreach ($category as $key => $value) {
    $value['NUM'] = (int)$count[$key];
    $value['id'] = $key;
    $value['parent_label'] = $parent[$value['parent']]['label'];
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

  if (isset($attr['parent'])) {
    $label = $attr['parent_label'];
    unset($attr['parent_label']);
  }

  $result = $article->update($attr, Article::CATEGORY)
    ->where($conditions)
    ->execute();

  if (isset($attr['parent'])) {
    $attr['parent_label'] = $label;
  }

  Spokesman::judge($result, $success, $error, $attr);
}