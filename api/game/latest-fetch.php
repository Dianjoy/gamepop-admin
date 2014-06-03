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
define('OWNER', 33);

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

  // 取用户信息
  $user = $admin->select(Admin::$ALL)
    ->where(array('id' => OWNER))
    ->fetch(PDO::FETCH_ASSOC);

  // 只看“外包账户”这个人的游戏
  $my_games = $game->select(Game::$OUTSIDE)
    ->where(array('user_id' => OWNER))
    ->fetchAll(PDO::FETCH_ASSOC);

  $guide_names = array();
  foreach ($my_games as $item) {
    $guide_names[] = $item['guide_name'];
  }

  // 只看有更新的游戏
  $article_number = $article->select(Game::ID, $article->count())
    ->where(array('status' => Article::FETCHED))
    ->group(Game::ID)
    ->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_UNIQUE);
  $guide_names_article = array_keys($article_number);

  // 去重合并
  $guide_names = array_intersect($guide_names, array_filter($guide_names_article));

  // 取游戏
  $games = $game->select(Game::$ALL)
    ->where($conditions)
    ->where(array(Game::ID => $guide_names), '', \gamepop\Base::R_IN)
    ->search($keyword)
    ->fetchAll(PDO::FETCH_ASSOC);
  $total = count($games);
  $games = array_slice($games, $page * $pagesize, $pagesize);
  foreach ($games as &$row) {
    $row['os_android'] = (int)$row['os_android'];
    $row['os_ios'] = (int)$row['os_ios'];
    $row['user_id'] = OWNER;
    $row['fullname'] = $user['fullname'];
    $row['article_number'] = $article_number[$row[Game::ID]];
  }
  usort($games, compare);

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
  return $b['article_number'] - $a['article_number'];
}