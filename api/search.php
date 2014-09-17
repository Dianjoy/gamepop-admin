<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14-9-16
 * Time: 下午2:09
 */
require_once '../inc/Spokesman.class.php';
require_once '../inc/Game.class.php';

$query = $_REQUEST['query'];
$result = array(
  'group' => array(),
);

// 搜索游戏
$game = new Game();
$one = $game->select(Game::$BASE)
  ->where(array('status' => 0))
  ->where(array('game_name' => $query), '', \gamepop\Base::R_EQUAL)
  ->where(array('guide_name' => $query), '', \gamepop\Base::R_EQUAL, true)
  ->fetch(PDO::FETCH_ASSOC);
$games = $game->select(Game::$BASE)
  ->where(array('status' => 0))
  ->search($query)
  ->limit(10)
  ->order('hot')
  ->fetchAll(PDO::FETCH_ASSOC);
if ($one) {
  array_unshift($games, $one);
}
if ($games) {
  foreach ($games as $key => $item) {
    $item['name'] = $item['game_name'];
    $item['key'] = '#/game/profile/' . $item['guide_name'];
    $games[$key] = $item;
  }
  $result['group'][] = array(
    'title' => '游戏',
    'list' => $games,
  );
}

// 没有搜索结果要显示无结果


Spokesman::say($result);