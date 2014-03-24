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
$all = array('del_cate');
if (!in_array($m, $all)) {
  header("HTTP/1.1 406 Not Acceptable");
  exit(json_encode(array(
    'code' => 1,
    'msg' => '指令错误',
  )));
}
include_once(dirname(__FILE__).'/../../inc/Article.class.php');
$article = new Article(true);
$m($article);

function del_cate($article) {
  $id = (int)$_REQUEST['id'];
  $args = array(
    'status' => 1,
  );
  $result = $article->update_category($id, $args);
  if ($result) {
    $result = array('code' => 0, 'msg' => '删除成功');
  } else {
    header('HTTP/1.1 400 Bad Request');
    $result = array('code' => 1, 'msg' => '删除失败');
  }
  echo json_encode($result);
}