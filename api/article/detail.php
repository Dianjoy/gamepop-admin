<?php
define('OPTIONS', 'article');
include_once '../../inc/session.php';
?>
<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14-3-17
 * Time: 下午5:13
 */

$DB = include_once "../../inc/pdo.php";
include_once "../../inc/Article.class.php";
$article = new Article($DB);

$url = $_SERVER['PATH_INFO'];
$id = (int)substr($url, 1);

$result = $article->get_article_by_id($id);

echo json_encode($result);