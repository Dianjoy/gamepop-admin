<?php
define('OPTIONS', 'article');
include_once '../../inc/session.php';
?>
<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14-3-17
 * Time: 上午11:38
 */
include_once "../../inc/Spokesman.class.php";
include_once "../../inc/Game.class.php";
$game = new Game();

$url = $_SERVER['PATH_INFO'];
$id = substr($url, 1);
$conditions = array(
  'guide_name' => $id,
);

$result = $game->select(Game::$ALL)
  ->where($conditions)
  ->fetch(PDO::FETCH_ASSOC);

Spokesman::say($result);