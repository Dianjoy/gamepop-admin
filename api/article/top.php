<?php
include_once "../../inc/Spokesman.class.php";
require_once "../../inc/Article.class.php";
include_once "../../inc/API.class.php";

$api = new API('game|article_wb', array(
  'fetch' => fetch,
  'delete' => delete,
  'update' => update,
));

function fetch() {
  $article = new Article();
  $conditions = Spokesman::extract(true);
  $status = array('status' => 0);
  $now = date('Y-m-d H:i:s');

  $articles = $article->select(Article::$TOP)
    ->where($conditions)
    ->where($status, Article::TOP)
    ->where($status, Article::TABLE)
    ->where(array('end_time' => $now), '', \gamepop\Base::R_MORE_EQUAL)
    ->fetchAll(PDO::FETCH_ASSOC);

  Spokesman::say(array(
    'list' => $articles,
  ));
}

function delete($args) {
  $attr = array(
    'status' => 1,
  );
  update($args, $attr, '删除成功', '删除失败');
}

function update($args, $attr, $success = '修改成功', $error = '修改失败') {
  $article = new Article();
  $conditions = Spokesman::extract(true);
  unset($conditions['guide_name']);

  $result = $article->update($attr, Article::TOP)
    ->where($conditions)
    ->execute();

  Spokesman::judge($result, $success, $error, $attr);
}