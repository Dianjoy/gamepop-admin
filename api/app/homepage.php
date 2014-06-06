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
));

function create($args, $attr) {
  require_once "../../inc/App.class.php";
  $app = new App();

  $init = array(
    'guide_name' => '',
    'big_pic' => '',
    'logo' => '',
    'create_time' => date('Y-m-d H:i:s'),
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
    ->limit(0, 5)
    ->fetchAll(PDO::FETCH_ASSOC);

  Spokesman::say(array(
    'total' => count($list),
    'list' => $list,
  ));
}

function update($args, $attr) {
  require_once "../../inc/App.class.php";
  $app = new App();

  $conditions = Spokesman::extract();

  $result = $app->update($attr, App::$HOMEPAGE)
    ->execute();

  Spokesman::judge($result, '修改成功', '修改失败', $attr);
}