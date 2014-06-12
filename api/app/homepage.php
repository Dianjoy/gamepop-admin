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

function fetch() {
  require_once "../../inc/App.class.php";
  $app = new App();

  $list = $app->select(App::$HOMEPAGE)
    ->where(array('status' => App::NORMAL))
    ->fetchAll(PDO::FETCH_ASSOC);
  usort($list, compare);

  Spokesman::say(array(
    'total' => count($list),
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