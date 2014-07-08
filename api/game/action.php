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
require_once "../../inc/utils.php";
$game = new Game();

switch ($_GET['m']) {
  case 'add':
    $attr = array_pick($_REQUEST, 'guide_name', 'game_name', 'game_desc');
    $attr['os_android'] = (int)in_array(1, $_REQUEST['platform']);
    $attr['os_ios'] = (int)in_array(2, $_REQUEST['platform']);
    $attr['icon_path'] = str_replace('http://r.yxpopo.com/', '', $_REQUEST['icon_path']);

    $result = $game->insert($attr)
      ->execute()
      ->getResult();

    $args = array(
      'go_to_url' => '#/game/profile/' . $args['guide_name'],
    );
    $success = '创建成功';
    $error = '创建失败';
    break;

  case 'merge':
    $from = $_REQUEST['from'];
    $to = $_REQUEST['to'];
    // 建立关联
    require_once "../../inc/Source.class.php";
    $source = new Source();
    $result = $source->insert(array(
        '4399id' => $to,
        'ptbusid' => $from,
      ), Source::VS)
      ->execute()
      ->getResult();
    // 导出文章
    if ($result) {
      require_once "../../inc/Article.class.php";
      $article = new Article();
      $result = $article->update(array(Game::ID => $to), Article::TABLE)
        ->where(array(Game::ID => $from))
        ->execute();
    }
    // 删掉来源游戏
    if ($result) {
      $result = $game->update(array('status' => Game::DELETED), Game::TABLE)
        ->where(array(Game::ID => $from))
        ->execute();
    }
    $success = '合并成功';
    $error = '合并失败';
    break;
}


Spokesman::judge($result, $success, $error, $args);