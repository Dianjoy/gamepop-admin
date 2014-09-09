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
  if (Admin::is_outsider() && !Admin::has_this_game($conditions['guide_name'])) {
    header('HTTP/1.1 401 Unauthorized');
    Spokesman::say(array(
      'code' => 1,
      'msg' => '您不能操作这个游戏',
    ));
    exit();
  }
  unset($conditions['guide_name']);
  
  if (isset($attr['icon_path']) || isset($attr['topic'])) {
    $aid = $article->select('aid')
      ->from(Article::TOP)
      ->where($conditions)
      ->fetch(PDO::FETCH_COLUMN);
    $result = $article->update($attr)
      ->where(array('id' => $aid))
      ->execute();
  } else {
    $result = $article->update($attr, Article::TOP)
      ->where($conditions)
      ->execute();
  }

  Spokesman::judge($result, $success, $error, $attr);
}