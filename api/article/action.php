<?php
define('OPTIONS', 'article');
include_once '../../inc/session.php';
?>
<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14-3-18
 * Time: 下午3:05
 */

header("Content-Type:application/json;charset=utf-8");
$m = $_REQUEST['m'];
$all = array('edit');
if (!in_array($m, $all)) {
  header("HTTP/1.1 406 Not Acceptable");
  exit(json_encode(array(
    'code' => 1,
    'msg' => '指令错误',
  )));
}
$DB = include(dirname(__FILE__).'/../../inc/pdo.php');
include_once(dirname(__FILE__).'/../../inc/Article.class.php');
$article = new Article($DB);
$m($article);

function edit($article) {
  $id = (int)$_REQUEST['id'];
  $topic = $_REQUEST['topic'];
  $content = $_REQUEST['content'];
  $result = $article->update_article_by_id($id, $topic, $content);
  $result = $result ? array('code' => 0, 'msg' => '修改成功') : array('code' => 1, 'msg' => '修改失败');
  echo json_encode($result);
}