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

header("Content-Type:application/json;charset=utf-8");
$DB = include_once "../../inc/pdo.php";
include_once "../../inc/Game.class.php";
include_once "../../inc/Article.class.php";
$game = new Game($DB);

$url = $_SERVER['PATH_INFO'];
$id = substr($url, 1);
$info = $game->get_info($id);

$result = array();

echo json_encode(json_encode($info));