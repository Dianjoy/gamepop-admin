<?php
define('OPTIONS', 'article');
include_once '../../inc/session.php';
?>
<?php
require_once '../../inc/Spokesman.class.php';
require_once '../../inc/Base.class.php';
require_once '../../inc/Article.class.php';
require_once '../../inc/Log.class.php';

function compare($a, $b) {
  return (int)$b['num'] - (int)$a['num'];
}

$article = new Article();
$log = new Log();

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

// 昨日搜索top10
$yesterday = date('Y-m-d', time() - 86400);
$today = date('Y-m-d');
$corp_ip = '218.247.145.70';
$top10 = $log->select(Log::$SEARCH)
  ->where(array('insert_time' => $yesterday), '', \gamepop\Base::R_MORE_EQUAL)
  ->where(array('insert_time' => $today), '', \gamepop\Base::R_LESS)
  ->where(array('ip' => $corp_ip), '', \gamepop\Base::R_NOT_EQUAL)
  ->group('skey')
  ->fetchAll(PDO::FETCH_ASSOC);
usort($top10, compare);
$total = $count = $other = 0;
foreach ($top10 as $item) {
  $total += $item['num'];
}
$result['search-top10'] = array();
foreach ($top10 as $key => $item) {
  $result['search-top10'][] = array(
    'label' => $item['skey'],
    'value' => $item['num'],
    'percent' => round($item['num'] / $total * 100, 2),
  );
  $other += $item['num'];
  $count++;
  if ($count > 19) {
    break;
  }
}
$result['search-top10'][] = array(
  'label' => '其它',
  'value' => $total - $other,
  'percent' => round(($total - $other) / $total * 100, 2),
);
$result['search-top10'] = json_encode($result['search-top10']);

Spokesman::toHTML($result, 'template/editor.html');