<?php
/**
 * 热门游戏列表
 * Created by PhpStorm.
 * User: meathill
 * Date: 14-9-1
 * Time: 下午3:20
 */

require_once '../../inc/Spokesman.class.php';
require_once '../../inc/Game.class.php';
require_once '../../inc/API.class.php';
require_once '../../inc/utils.php';

$api = new API('game', array(
  'fetch' => fetch,
  'update' => update,
  'delete' => delete,
));

function fetch($args) {
  $pagesize = empty($args['pagesize']) ? 20 : (int)$args['pagesize'];
  $page = isset($args['page']) ? (int)$args['page'] : 0;
  $keyword = empty($args['keyword']) ? '' : trim(addslashes(strip_tags($args['keyword'])));

  $game = new Game(false, false);
  $result = $game->get_hot_games($keyword, $page * $pagesize, $pagesize);

  Spokesman::say($result);
}

function update($args, $attr) {
  $game = new Game(true);
  $args = Spokesman::extract(true);

  $key = $game->select("'x'")
    ->from(Game::POSTER)
    ->where($args)
    ->execute()
    ->fetch(PDO::FETCH_COLUMN);
  if ($key) {
    $attr = array_pick($attr, 'poster');
    $result = $game->update($attr, Game::POSTER)
      ->where($args)
      ->execute()
      ->getResult();
  } else {
    $attr = array_merge($attr, $args);
    $result = $game->insert($attr, Game::POSTER)
      ->execute()
      ->lastInsertId();
  }

  Spokesman::judge($result, '修改成功', '修改失败', $attr);
}

function delete($args, $attr) {
  // 其实是把这个游戏关联到我的账号上
  $game = new Game(true);
  $args = Spokesman::extract(true);
  $attr = array('user_id' => 1);
  $result = $game->update($attr, Game::OUTSIDE)
    ->where($args)
    ->execute(true)
    ->getResult();

  Spokesman::judge($result, '删除成功', '删除失败');
}