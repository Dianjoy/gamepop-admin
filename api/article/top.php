<?php
include_once "../../inc/Spokesman.class.php";
include_once "../../inc/Game.class.php";
require_once "../../inc/Article.class.php";
include_once "../../inc/API.class.php";

$api = new API('game|article_wb', array(
  'fetch' => fetch
));

function fetch() {
  $article = new Article();
  $conditions = Spokesman::extract(true);
  $status = array('status' => 0);

  $articles = $article->select(Article::$TOP)
    ->where($conditions)
    ->where($status, Article::$TOP)
    ->where($status, Article::TABLE)
    ->fetchAll(PDO::FETCH_ASSOC);

  Spokesman::say(array(
    'list' => $articles,
  ));
}