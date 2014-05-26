<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14-5-23
 * Time: 下午6:37
 */
require_once "../../inc/API.class.php";
require_once "../../inc/Spokesman.class.php";
require_once "../../inc/Game.class.php";

$api = new API('game', array(
  'create' => create,
));

function create($args, $attr) {
  $game = new Game();

  // 看看是否已有
  $tag = $game->select(Game::$TAGS)
    ->where(array('tag' => $attr['tag']))
    ->fetch(PDO::FETCH_ASSOC);

  if ($tag) {
    Spokesman::say($tag);
  }

  $attr['tag_type'] = 'tag';
  $result = $game->insert($attr, Game::TAGS)
    ->execute()
    ->lastInsertId();

  $args = array_merge(array('id' => $result), $args);
  Spokesman::judge($result, '创建成功', '创建失败', $args);
}