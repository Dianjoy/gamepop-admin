<?php
define('OPTIONS', 'article');
include_once '../../inc/session.php';
?>
<?php
require_once '../../inc/Spokesman.class.php';
require_once '../../inc/Base.class.php';
require_once '../../inc/Article.class.php';
require_once '../../inc/Game.class.php';
require_once '../../inc/Log.class.php';

function compare($a, $b) {
  return (int)$b['num'] - (int)$a['num'];
}

$article = new Article();
$game = new Game();
$log = new Log();

$result = array();

// 取新抓取的文章
$result['latest-fetch'] = $article->get_latest_fetched_article_number();

// 取未收录的游戏
$result['lost-game'] = count($article->get_unknown_games());

// 取新收录的游戏
$result['fetched'] = $game->select($game->count())
  ->where(array('status' => Game::FETCHED))
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

$corp_ip = '218.247.145.70';
// 一个月内的搜索统计
$month = date('Y-m-d', time() - 2592000);
$ips = $log->select('DATE(`insert_time`)', $log->count('ip', '', true))
  ->where(array('insert_time' => $month), '', Log::R_MORE_EQUAL)
  ->where(array('ip' => $corp_ip), '', Log::R_NOT_EQUAL)
  ->group('DATE(`insert_time`)')
  ->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_COLUMN);
$times = $log->select('DATE(`insert_time`)', $log->count())
  ->where(array('insert_time' => $month), '', Log::R_MORE_EQUAL)
  ->where(array('ip' => $corp_ip), '', Log::R_NOT_EQUAL)
  ->group('DATE(`insert_time`)')
  ->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_UNIQUE);
$times['2014-05-20'] = 1; // 这天做了压力测试，数据有问题
$result['search'] = array();
for ($i = time() - 2592000; $i < time(); $i += 86400) {
  $date = date('Y-m-d', $i);
  $result['search'][] = array(
    'date' => $date,
    'ip' => (int)$ips[$date],
    'num' => (int)$times[$date],
  );
}

// 七日搜索top10
$yesterday = date('Y-m-d', time() - 86400 * 7);
$today = date('Y-m-d');
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
  if ($count > 14) {
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