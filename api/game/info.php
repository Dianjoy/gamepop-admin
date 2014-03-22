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
include_once "../../inc/Game.class.php";
$game = new Game();

$url = $_SERVER['PATH_INFO'];
$id = substr($url, 1);

$result = $game->get_info($id);
$result['icon_path'] = (substr($result['icon_path'], 0, 7) === 'upload/' ? 'http://admin.yxpopo.com/' : 'http://r.yxpopo.com/yxpopo/') . $result['icon_path'];
echo json_encode($result);