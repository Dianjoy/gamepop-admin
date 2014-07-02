<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14-6-6
 * Time: 下午5:37
 */
require_once "../../inc/API.class.php";
require_once "../../inc/Spokesman.class.php";

$api = new API('article', array(
  'create' => create,
  'fetch' => fetch,
  'update' => update,
  'delete' => delete,
));

function create($args, $attr) {
  require_once "../../inc/App.class.php";
  $app = new App();

  // 因为dpi的关系，前端显示的时候要缩小到50%，所以这里需要记录图片大小
  if (isset($attr['logo'])) {
    $size = getimagesize('../../' . (substr($attr['logo'], 0, 7) != 'upload/' ? 'upload/' : '' ) . $attr['logo']);
    $attr['logo_width'] = $size[0] >> 1;
  }

  $init = array(
    'guide_name' => '',
    'big_pic' => '',
    'logo' => '',
    'create_time' => date('Y-m-d H:i:s'),
    'online_time' => date('Y-m-d', time() + 86400) . ' 23:59:59', // 默认次日晚上更新
  );
  $attr = array_merge($init, $attr);
  $result = $app->insert($attr, App::HOMEPAGE)
    ->execute()
    ->lastInsertId();
  $attr['id'] = $result;

  Spokesman::judge($result, '创建成功', '创建失败', $attr);
}

function fetch($args) {
  require_once "../../inc/App.class.php";
  require_once "../../inc/Game.class.php";
  $app = new App();
  $game = new Game();

  $page = (int)$args['page'];
  $pagesize = empty($args['pagesize']) ? 20 : (int)$args['pagesize'];
  $condition = array(
    'status' => App::NORMAL,
  );

  $total = $app->select($app->count())
    ->where($condition)
    ->fetch(PDO::FETCH_COLUMN);

  $list = $app->select(App::$HOMEPAGE)
    ->where($condition)
    ->order('seq', 'ASC')
    ->limit($page * $pagesize, $pagesize)
    ->fetchAll(PDO::FETCH_ASSOC);

  $guide_names = array();
  foreach ($list as $item) {
    $guide_names[] = $item['guide_name'];
  }

  $games = $game->select(Game::$ALL)
    ->where(array(Game::ID => $guide_names), '', \gamepop\Base::R_IN)
    ->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_UNIQUE);

  foreach ($list as $key => $item) {
    $item['game_name'] = $games[$item['guide_name']]['game_name'];
    $list[$key] = $item;
  }
  
  Spokesman::say(array(
    'total' => $total,
    'list' => $list,
  ), array('logo', 'big_pic'));
}

function update($args, $attr) {
  require_once "../../inc/App.class.php";
  $app = new App();

  $conditions = Spokesman::extract();
  unset($attr['game_name']);

  // 因为dpi的关系，前端显示的时候要缩小到50%，所以这里需要记录图片大小
  if (isset($attr['logo'])) {
    $size = getimagesize('../../' . (substr($attr['logo'], 0, 7) != 'upload/' ? 'upload/' : '' ) . $attr['logo']);
    $attr['logo_width'] = $size[0] >> 1;
  }

  $result = $app->update($attr, App::HOMEPAGE)
    ->where($conditions)
    ->execute();

  Spokesman::judge($result, '修改成功', '修改失败', $attr, array('logo', 'big_pic'));
}

function delete($args) {
  update($args, array('status' => 1));
}

function compare($a, $b) {
  return (int)$a['seq'] - (int)$b['seq'];
}