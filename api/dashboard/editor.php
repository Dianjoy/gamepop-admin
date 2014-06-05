<?php
define('OPTIONS', 'article');
include_once '../../inc/session.php';
?>
<?php
require_once '../../inc/Spokesman.class.php';
require_once '../../inc/Base.class.php';
require_once '../../inc/Article.class.php';

$article = new Article();

$result = array();

// 取新抓取的文章
$result['latest-fetch'] = $article->select($article->count())
  ->where(array('status' => Article::FETCHED))
  ->fetch(PDO::FETCH_COLUMN);

// 取未分类的文章
$result['no-category'] = $article->select($article->count())
  ->where(array(
    'category' => 0,
    'status' => 0,
  ))
  ->fetch(PDO::FETCH_COLUMN);

// 取未编辑的文章
$result['not-edited'] = $article->select($article->count())
  ->where(array(
    'update_editor' => 0,
    'status' => 0,
  ))
  ->fetch(PDO::FETCH_COLUMN);

// 取“可删除文章”的数量
$result['trash'] = $article->select($article->count())
  ->where(array(
    'category' => 112, // 可删除文章
    'status' => 0,
  ))
  ->fetch(PDO::FETCH_COLUMN);

foreach ($result as $key => $value) {
  $result[$key] = number_format($value);
}


Spokesman::toHTML($result, 'template/editor.html');