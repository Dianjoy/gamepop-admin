<?php
define('OPTIONS', 'game');
include_once '../../inc/session.php';
?>
<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14-4-21
 * Time: 下午4:54
 */
include_once "../../inc/Spokesman.class.php";
include_once "../../inc/Game.class.php";
$game = new Game();

$args = $_REQUEST;
$request = file_get_contents('php://input');
if ($request) {
  $args = array_merge($_POST, json_decode($request, true));
}

header("Content-Type:application/json;charset=utf-8");
switch ($_SERVER['REQUEST_METHOD']) {
  case 'GET':
    fetch($game);
    break;

  case 'PATCH':
    update($game, $args);
    break;

  default:
    header("HTTP/1.1 406 Not Acceptable");
    break;
}

function fetch($game) {
  require_once "../../inc/Admin.class.php";
  $admin = new Admin();

  $conditions = Spokesman::extract(true);

  $outsider = $game->select(Game::$OUTSIDE)
    ->where($conditions)
    ->fetch(PDO::FETCH_ASSOC);

  if (!$outsider) {
    $result = array(
      'fullname' => '（未分配）',
    );
    Spokesman::say($result);
    exit();
  }

  $info = $admin->select(Admin::$BASE)
    ->where(array(
      'id' => $outsider['user_id']
    ))
    ->fetch(PDO::FETCH_ASSOC);
  unset($info['id']);

  Spokesman::say(array_merge($outsider, $info));
}

function update($game, $args) {
  $fullname = $args['fullname'];
  unset($args['fullname']);
  $conditions = Spokesman::extract(true);

  $outsider = $game->select(Game::$OUTSIDE)
    ->where($conditions)
    ->fetch(PDO::FETCH_ASSOC);

  if ($outsider) {
    $result = $game->update($args, Game::OUTSIDE)
      ->where($conditions)
      ->execute();
  } else {
    $result = $game->insert(array_merge($args, $conditions), Game::OUTSIDE)
      ->execute();
  }

  $args['fullname'] = $fullname;
  Spokesman::judge($result, '修改成功', '修改失败', $args);
}