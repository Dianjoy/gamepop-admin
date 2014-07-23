<?php
/**
 * Created by PhpStorm.
 * User: Meathill <lujia.zhai@dianjoy.com>
 */

include_once "../../inc/Spokesman.class.php";
include_once "../../inc/API.class.php";
$api = new API('game|article_wb', array(
  'fetch' => fetch,
  'update' => update,
  'delete' => delete,
));

function fetch($args) {
  require_once "../../inc/Article.class.php";
  $article = new Article();

  $pagesize = empty($args['pagesize']) ? 20 : (int)$args['pagesize'];
  $page = isset($args['page']) ? (int)$args['page'] : 0;
  $keyword = $args['keyword'];

  $games = $article->get_unknown_games($keyword);
  usort($games, compare);

  // 各取5篇文章以便查阅
  foreach ($games as $key => $item) {
    $games[$key]['articles'] = $article->select(Article::$ALL)
      ->where(array(Game::ID => $item['guide_name']))
      ->where(array('status' => Article::FETCHED), Article::TABLE)
      ->limit(0, 5)
      ->fetchAll(PDO::FETCH_ASSOC);
  }

  $total = count($games);
  $games = array_slice($games, $pagesize * $page, $pagesize);

  $result = array(
    'total' => $total,
    'list' => $games
  );

  Spokesman::say($result);
}

function delete() {
  $attr = array(
    'status' => 1,
  );
  update(null, $attr);
}

function update($args, $attr) {
  require_once "../../inc/Game.class.php";
  $game = new Game();

  $conditions = Spokesman::extract(true);
  // 处理图标路径
  if (isset($attr['icon_path'])) {
    $attr['icon_path'] = str_replace('http://r.yxpopo.com/', '', $attr['icon_path']);
  }

  // 这个比较复杂，关于是否要在对应表里建立关联
  if (isset($attr['link'])) {
    // 建立关联
    require_once "../../inc/Source.class.php";
    $source = new Source();
    $result = $source->insert(array(
        '4399id' => $attr['link'],
        '4399name' => $attr['game_name'],
        'ptbusid' => $conditions['guide_name'],
        'ptbusname' => $attr['game_name'],
      ), Source::VS)
      ->execute()
      ->getResult();
    // 导出文章
    if ($result) {
      require_once "../../inc/Article.class.php";
      $article = new Article();
      $result = $article->update(array(Game::ID => $attr['link']), Article::TABLE)
        ->where($conditions)
        ->execute();
    }
    // 删掉游戏
    if ($result) {
      $game->update(array('status' => 1))
        ->where($conditions)
        ->execute();
    }
    unset($attr['link']);

    if (!$result) {
      Spokesman::judge($result, '', '关联失败');
      exit();
    }
  }

  $check = $game->select('x')
    ->where($conditions)
    ->fetch(PDO::FETCH_COLUMN);
  if ($check) { // 已有游戏
    $result = $game->update($attr, Game::TABLE)
      ->where($conditions)
      ->execute();
    $success = '修改成功';
    $error = '修改失败';
  } else {
    $attr = array_merge($attr, $conditions);
    $result = $game->insert($attr, Game::TABLE)
      ->execute()
      ->getResult();
    $success = '创建成功';
    $error = '创建失败';
  }
  Spokesman::judge($result, $success, $error, $attr);
}

function compare($a, $b) {
  return (int)$b['NUM'] - (int)$a['NUM'];
}