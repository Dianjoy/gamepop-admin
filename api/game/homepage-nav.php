<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14-3-31
 * Time: 下午1:44
 */
include_once "../../inc/Spokesman.class.php";
include_once "../../inc/Game.class.php";
require_once "../../inc/Article.class.php";
include_once "../../inc/API.class.php";

$api = new API('game|article_wb', array(
  'fetch' => fetch,
  'update' => update,
  'delete' => delete,
  'create' => create,
));

function fetch($args) {
  $game = new Game();
  $article = new Article();
  $conditions = Spokesman::extract(true);

  if (empty($conditions['guide_name'])) {
    exit(json_encode(array(
      'code' => 1,
      'msg' => '缺少参数：guide_name',
    )));
  }

  $nav = $game->select(Game::$HOMEPAGE_NAV)
    ->where($conditions)
    ->where(array('status' => 0))
    ->order('seq', 'ASC')
    ->fetchAll(PDO::FETCH_ASSOC);

  $categories = array();
  foreach ($nav as $nav_item) {
    $categories[] = $nav_item['category'];
  }

  // “编辑首页”用这个接口搜索未在列表中的文章分类
  if (array_key_exists('search', $args)) {
    $categories = $article->select('cid', $article->count())
      ->join(Article::ARTICLE_CATEGORY, 'id', 'aid')
      ->where($conditions)
      ->where(array('status' => Article::NORMAL))
      ->where(array('cid' => array_filter($categories)), '', \gamepop\Base::R_NOT_IN)
      ->group('cid')
      ->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_UNIQUE);

    $labels = $article->select(Article::$ALL_CATEGORY)
      ->where(array('id' => array_keys($categories)), '', \gamepop\Base::R_IN)
      ->where(array('status' => Article::NORMAL))
      ->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_UNIQUE);

    $options = array();
    foreach ($categories as $key => $num) {
      $options[] = array(
        'id' => $key,
        'label' => $labels[$key]['label'] . "（{$num}）",
      );
    }
    Spokesman::say($options);
  }

  $categories = $article->select(Article::$ALL_CATEGORY)
    ->where(array('id' => $categories), '', \gamepop\Base::R_IN)
    ->group('id', Article::CATEGORY)
    ->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_UNIQUE);

  foreach ($nav as $key => $value) {
    $value['guide_name'] = $conditions['guide_name'];
    $nav[$key] = array_merge($value, (array)$categories[$value['category']]);
  }

  $result = array(
    'total' => count($nav),
    'list' => $nav,
  );

  Spokesman::say($result);
}

function create($args, $attr, $success = '创建成功', $error = '创建失败') {
  $game = new Game();
  $article = new Article();
  if (isset($attr['label'])) {
    $attr['category'] = $attr['label'];
    unset($attr['label']);
  }
  $attr = array_merge($attr, Spokesman::extract(true));
  if (isset($attr['image'])) {
    $attr['image'] = str_replace('http://r.yxpopo.com/', '', $attr['image']);
  }
  // 如果以前有被删了，则恢复以前的
  $result = $game->select(Game::$HOMEPAGE_NAV)
    ->where($attr)
    ->fetch(PDO::FETCH_ASSOC);
  if ($result) {
    $attr = $result;
    $result = $game->update(array('status' => 0), Game::HOMEPAGE_NAV)
      ->where($attr)
      ->limit(1)
      ->execute()
      ->getResult();
    Spokesman::judge($result, $success, $error, $attr);
    exit();
  }

  $result = $game->insert($attr, Game::HOMEPAGE_NAV)
    ->execute()
    ->lastInsertId();
  $label = $article->select('label')
    ->where(array('id' => $attr['category']))
    ->fetch(PDO::FETCH_COLUMN);
  $attr = array_merge(array(
    'id' => $result,
    'label' => $label,
  ), $attr);
  Spokesman::judge($result, $success, $error, $attr);
}

function delete($args) {
  $attr = array(
    'status' => 1,
  );
  update($args, $attr);
}

function update($args, $attr, $success = '更新成功', $error = '更新失败') {
  $game = new Game();
  $conditions = Spokesman::extract(true);
  if (isset($attr['image'])) {
    $attr['image'] = str_replace('http://r.yxpopo.com/', '', $attr['image']);
  }
  if (array_key_exists('label', $attr)) {
    $label = preg_replace('/（\d+）/', '', $attr['label']);
    unset($attr['label']);
  }
  $result = $game->update($attr, Game::HOMEPAGE_NAV)
    ->where($conditions)
    ->execute();

  $attr['label'] = $label;

  Spokesman::judge($result, $success, $error, $attr);
}