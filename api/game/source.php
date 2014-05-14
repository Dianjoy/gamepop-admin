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

  Spokesman::say(array(
    'total' => count($sources),
    'list' => $sources,
  ));
}
function update($args) {
  $fetcher = new Fetcher();

  $conditions = Spokesman::extract(true);
  $result = $fetcher->update($args, Fetcher::SOURCE)
    ->where($conditions)
    ->execute();

  Spokesman::judge($result, '修改成功', '修改失败');
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