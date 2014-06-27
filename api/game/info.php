<?php
define('OPTIONS', 'article|article_wb');
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
require_once "../../inc/Source.class.php";

$game = new Game();
$source = new Source();

$conditions = Spokesman::extract(true);

$result = $game->select(Game::$ALL)
  ->where($conditions)
  ->fetch(PDO::FETCH_ASSOC);
$result['ptbus'] = $source->select(Source::$VS_ptbus)
  ->where(array(Source::$VS_4399 => $conditions['guide_name']))
  ->fetch(PDO::FETCH_COLUMN);

Spokesman::say($result);