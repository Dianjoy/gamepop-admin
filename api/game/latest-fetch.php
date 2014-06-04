<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14-6-3
 * Time: 下午4:04
 */

require_once '../../inc/API.class.php';
require_once '../../inc/Spokesman.class.php';

// 将所有关键游戏放在这个用户id下
define('VIP', 33);

$api = new API('game', array(
  'fetch' => fetch,
  'update' => update
));

function fetch($args) {
  require_once '../../inc/Game.class.php';
  require_once '../../inc/Article.class.php';
  require_once '../../inc/Admin.class.php';
  $game = new Game();
  $article = new Article();
  $admin = new Admin();

  $pagesize = empty($args['pagesize']) ? 20 : (int)$args['pagesize'];
  $page = isset($args['page']) ? (int)$args['page'] : 0;
  $keyword = empty($args['keyword']) ? '' : trim(addslashes(strip_tags($args['keyword'])));
  $conditions = array(
    'status' => Game::NORMAL,
  );

  // 只看有更新的游戏
  $article_number = $article->select(Game::ID, $article->count())
    ->where(array('status' => Article::FETCHED))
    ->group(Game::ID)
    ->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_UNIQUE);
  $guide_names = array_keys($article_number);

  // 取对应的用户
  $outsiders = $game->select(Game::$OUTSIDE)
    ->where(array(Game::ID => $guide_names), '', \gamepop\Base::R_IN)
    ->fetchAll(PDO::FETCH_ASSOC);
  $game_outsiders = array();
  foreach ($outsiders as $row) {
    $game_outsiders[$row['guide_name']] = $row['user_id'];
  }
  $outsiders = array_values($game_outsiders);
  $users = $admin->select(Admin::$BASE)
    ->where(array('id' => $outsiders), '', \gamepop\Base::R_IN)
    ->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_UNIQUE);

  // 取游戏
  $games = $game->select(Game::$ALL)
    ->where($conditions)
    ->where(array(Game::ID => $guide_names), '', \gamepop\Base::R_IN)
    ->search($keyword)
    ->fetchAll(PDO::FETCH_ASSOC);
  foreach ($games as &$row) {
    $row['os_android'] = (int)$row['os_android'];
    $row['os_ios'] = (int)$row['os_ios'];
    $row['user_id'] = $game_outsiders[$row['guide_name']];
    $row['fullname'] = $users[$row['user_id']];
    $row['article_number'] = $article_number[$row[Game::ID]];
    $row['vig'] = (int)($row['user_id'] == VIP);
  }
  usort($games, compare);
  $total = count($games);
  $games = array_slice($games, $page * $pagesize, $pagesize);

  $result = array(
    'total' => $total,
    'list' => $games
  );

  Spokesman::say($result);
}

function update($args, $attr) {
  require_once '../../inc/Game.class.php';
  $game = new Game();

  $conditions = Spokesman::extract(true);
  if (isset($args['icon_path'])) {
    $args['icon_path'] = str_replace('http://r.yxpopo.com/', '', $args['icon_path']);
  }
  unset($attr['fullname']);
  $result = $game->update($attr)
    ->where($conditions)
    ->execute();
  Spokesman::judge($result, '修改成功', '修改失败', $attr);
}

function compare($a, $b) {
  $vig = $b['vig'] - $a['vig'];
  if ($vig !== 0) {
    return $vig;
  }
  return $b['article_number'] - $a['article_number'];
}