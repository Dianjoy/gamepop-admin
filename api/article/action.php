<?php
define('OPTIONS', 'article');
include_once '../../inc/session.php';
?>
<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14-6-5
 * Time: 下午2:44
 */

$method = $_REQUEST['m'];
require_once '../../inc/Spokesman.class.php';
require_once '../../inc/Article.class.php';
$article = new Article();

switch ($method) {
  case 'delete-all-deletable':
    delete_all_deletable($article);
    break;

  default:
    header("HTTP/1.1 406 Not Acceptable");
    break;
}

function delete_all_deletable($article) {
  $category = 112; // 可删除文章
  $result = $article->update(array('status' => 1), Article::TABLE)
    ->where(array('category' => $category))
    ->execute();
  Spokesman::judge($result, '删除成功', '删除失败');
}