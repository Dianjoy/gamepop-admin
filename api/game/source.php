<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14-5-14
 * Time: 下午5:59
 */
require_once "../../inc/API.class.php";
require_once "../../inc/Spokesman.class.php";
require_once "../../inc/Fetcher.class.php";

$api = new API('article', array(
  'fetch' => fetch,
  'create' => create,
  'update' => update,
));

function fetch($args) {
  $fetcher = new Fetcher();

  $conditions = Spokesman::extract(true);
  $sources = $fetcher->select(Fetcher::$ALL)
    ->where($conditions)
    ->fetchAll(PDO::FETCH_ASSOC);
  foreach ($sources as $key => $item) {
    $sources[$key]['label'] = getLabel($item['min_catch_time']);
  }


  Spokesman::say(array(
    'total' => count($sources),
    'list' => $sources,
  ));
}
function update($args, $attr) {
  $fetcher = new Fetcher();

  unset($attr['label']);

  $conditions = Spokesman::extract(true);
  $result = $fetcher->update($attr, Fetcher::SOURCE)
    ->where($conditions)
    ->execute();

  if (isset($attr['min_catch_time'])) {
    $attr['label'] = getLabel($attr['min_catch_time']);
  }
  Spokesman::judge($result, '修改成功', '修改失败', $attr);
}
function create($args, $attr) {
  $fetcher = new Fetcher();

  $args = array_merge($attr, Spokesman::extract(true));
  $result = $fetcher->insert($args, Fetcher::SOURCE)
    ->execute()
    ->lastInsertId();

  $args = array_merge(array('id' => $result), $args);
  Spokesman::judge($result, '创建成功', '创建失败', $args);
}

function getLabel($time) {
  $hours = $time / 3600;
  switch ($hours) {
    case 2:
      return '默认';
      break;

    case 12:
      return '半天';
      break;

    case 24:
      return '一天';
      break;

    case 168:
      return '一周';
      break;

    case 720:
      return '一月';
      break;

    default:
      return '未知';
      break;
  }
}