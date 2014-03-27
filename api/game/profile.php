<?php
define('OPTIONS', 'game');
include_once '../../inc/session.php';
?>
<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14-3-14
 * Time: 下午4:10
 */

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
    fetch($game, $args);
    break;

  default:
    header("HTTP/1.1 406 Not Acceptable");
    break;
}

function fetch($game, $args) {
  $status = array(
    'status' => 0,
  );
  $conditions = array();
  foreach (array('game', 'category', 'author', 'id') as $row) {
    if (isset($args[$row])) {
      $conditions[$row === 'game' || $row === 'id' ? 'guide_name' : $row] = $args[$row];
    }
  }
  $result = $game->select(Game::$SLIDE)
    ->where($status)
    ->where($conditions)
    ->execute()
    ->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode(array(
    'total' => count($result),
    'list' => $result,
  ));
}
