<?php
define('OPTIONS', 'game');
include_once '../../inc/session.php';
?>
<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14-5-21
 * Time: 下午4:39
 */

require_once "../../inc/Spokesman.class.php";
require_once "../../inc/Game.class.php";
$game = new Game();

$args = $_REQUEST;
$args['os_android'] = (int)in_array(1, $args['platform']);
$args['os_ios'] = (int)in_array(2, $args['platform']);
unset($args['platform']);

$check = $game->insert($args)
  ->execute()
  ->getResult();

$args = array(
  'go_to_url' => '#/game/profile/' . $args['guide_name']
);

Spokesman::judge($check, '创建成功', '创建失败', $args);