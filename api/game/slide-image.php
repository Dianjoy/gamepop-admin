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
  $conditions = Spokesman::extract(true);
  $conditions['status'] = Game::NORMAL;

  $games = $game->select(Game::$SLIDE)
    ->where($conditions)
    ->fetchAll(PDO::FETCH_ASSOC);
  $total = count($games);
  $games = array_slice($games, 0, 10);
  usort($games, compare);

  $result = array(
    'total' => $total,
    'list' => $games
  );

  Spokesman::say($result);
}

function delete($game) {
  $args = array(
    'status' => 1,
  );
  update($game, $args, '删除成功', '删除失败');
}

function update($game, $args, $success = '更新成功', $error = '更新失败') {
  $conditions = Spokesman::extract(true);
  $result = $game->update($args, Game::SLIDE)
    ->where($conditions)
    ->execute();
  Spokesman::judge($result, $success, $error, $args);
}

function create($game, $args) {
  $args = array_merge($args, Spokesman::extract(true));
  $result = $game->insert($args)
    ->execute()
    ->lastInsertId();
  $args = array_merge(array('id' => $result), $args);
  Spokesman::judge($result, '创建成功', '创建失败', $args);
}

function compare($a, $b) {
  return (int)$a['seq'] - (int)$b['seq'];
}