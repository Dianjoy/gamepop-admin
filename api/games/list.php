<?php
define('OPTIONS', 'root');
include_once '../../inc/session.php';
?>
<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14-3-7
 * Time: 下午6:10
 */

$DB = include_once "../../inc/pdo.php";
$apk = include_once "../../inc/apk.php";
include_once "../../inc/Template.class.php";
include_once "../../inc/Game.class.php";

$pagesize = isset($_REQUEST['pagesize']) ? (int)$_REQUEST['pagesize'] : 20;
$page = isset($_REQUEST['page']) ? (int)$_REQUEST['page'] : 0;

$game = new Game($DB);
$total = $game->get_game_number();
$games = $game->get_all_games($pagesize, $page);

$result = array(
  'total' => $total,
  'list' => $games
);

header("Content-Type:application/json;charset=utf-8");
echo json_encode($result);