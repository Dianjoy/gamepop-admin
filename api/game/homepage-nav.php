<?php
define('OPTIONS', 'game|article_wb');
include_once '../../inc/session.php';
?>
<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14-3-31
 * Time: 下午1:44
 */
include_once "../../inc/Spokesman.class.php";
include_once "../../inc/Game.class.php";
$game = new Game();

$args = $_REQUEST;
$request = file_get_contents('php://input');
if ($request) {
  $args = array_merge($_POST, json_decode($request, true));
}
switch ($_SERVER['REQUEST_METHOD']) {
  case 'GET':
    fetch($game, $args);
    break;

  case 'PATCH':
    update($game, $args);
    break;

  case 'DELETE':
    delete($game);
    break;

  case 'POST':
    create($game, $args);
    break;

  default:
    header("HTTP/1.1 406 Not Acceptable");
    break;
}

function fetch($game, $args) {
  require_once "../../inc/Article.class.php";
  $article = new Article();
  $conditions = array(
    'guide_name' => $args['id'],
  );
  $status = array(
    '`' . Article::CATEGORY . '`.`status`' => Article::NORMAL,
    '`category`' => 0,
  );

  $categories = $article->select(Article::$ALL_CATEGORY, $article->count())
    ->where($conditions)
    ->where(array('status' => Article::NORMAL), Article::TABLE)
    ->where($status, '', false, true)
    ->group('id', Article::CATEGORY)
    ->fetchAll(PDO::FETCH_ASSOC);

  $nav = $game->select(Game::$HOMEPAGE_NAV)
    ->where($conditions)
    ->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_UNIQUE);

  foreach ($categories as $key => $value) {
    $value['category'] = $value['id'];
    $value['guide_name'] = $args['id'];
    $nav[$value['id']]['status'] = (int)$nav[$value['id']]['status'];
    unset($value['id']);
    $categories[$key] = array_merge($value, (array)$nav[$value['category']]);
  }
  usort($categories, compare);

  $result = array(
    'total' => count($categories),
    'list' => array_values($categories),
  );

  Spokesman::say($result);
}

function create($game, $args) {
  unset($args['NUM']);
  unset($args['cate']);
  unset($args['label']);
  $args = array_merge($args, Spokesman::extract(true));
  $result = $game->insert($args, Game::HOMEPAGE_NAV)
    ->execute()
    ->lastInsertId();
  $args = array_merge(array('id' => $result), $args);
  Spokesman::judge($result, '创建成功', '创建失败', $args);
}

function delete($game) {
  $args = array(
    'status' => 1,
  );
  update($game, $args);
}

function update($game, $args, $success = '更新成功', $error = '更新失败') {
  $game->init_write();
  $conditions = Spokesman::extract(true);
  $result = $game->update($args, Game::HOMEPAGE_NAV)
    ->where($conditions)
    ->execute();
  Spokesman::judge($result, $success, $error, $args);
}

function compare($a, $b) {
  return (int)$a['seq'] - (int)$b['seq'];
}