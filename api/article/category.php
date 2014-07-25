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
  $pagesize = isset($args['pagesize']) && $args['pagesize'] != '' ? (int)$args['pagesize'] : 20;
  $page = isset($args['page']) ? (int)$args['page'] : 0;
  $keyword = $args['keyword'];

  $article = new Article();
  $conditions = Spokesman::extract(true);
  $status = array('status' => Article::NORMAL);

  $total = $article->select($article->count())
    ->from(Article::CATEGORY)
    ->search(array('label' => $keyword))
    ->where($status)
    ->fetch(PDO::FETCH_COLUMN);

  $result = $article->select(Article::$ALL_CATEGORY, $article->count())
    ->where($conditions)
    ->where($status, Article::TABLE)
    ->where($status, Article::CATEGORY)
    ->search(array('label' => $keyword))
    ->group('id', Article::CATEGORY)
    ->order(Article::CATEGORY . '.`id`')
    ->limit($pagesize * $page, $pagesize)
    ->fetchAll(PDO::FETCH_ASSOC);

  Spokesman::say(array(
    'total' => $total,
    'list' => $result,
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