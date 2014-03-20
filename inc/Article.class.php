<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14-3-14
 * Time: 下午2:41
 */

class Article {
  const TABLE = '`t_article` a';
  const CATEGORY = '`t_category` c';

  private $DB = null;

  public function __construct(PDO $DB) {
    $this->DB = $DB;
  }

  public function get_article_number_by_id($guide_names, $keyword = '') {
    if (is_array($guide_names)) {
      $guide_names = implode("','", $guide_names);
    }
    if ($keyword) {
      $keyword = "AND `topic` LIKE '%$keyword%'";
    }
    $sql = "SELECT `guide_name`, COUNT('X') AS `num`
            FROM " . self::TABLE . "
            WHERE `guide_name` in ('$guide_names') $keyword
            GROUP BY `guide_name`";
    return $this->DB->query($sql)->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_UNIQUE);
  }

  public function get_article_by_id($id) {
    require_once(dirname(__FILE__) . '../../inc/HTML_To_Markdown.php');
    $sql = "SELECT `guide_name`, `label`, `content`, `source`, `topic`, `author`, `icon_path`
            FROM " . self::TABLE . " JOIN " . self::CATEGORY . " ON a.`category`=c.`id`
            WHERE a.`id`='$id'";
    $article = $this->DB->query($sql)->fetch(PDO::FETCH_ASSOC);
    if (get_magic_quotes_gpc()) {
      $article['content'] = stripslashes($article['content']);
    }
    $markdown = new HTML_To_Markdown($article['content'], array('strip_tags' => true));
    $article['content'] = str_replace('](/', '](http://r.yxpopo.com/yxpopo/', htmlspecialchars($markdown));
    return $article;
  }

  public function get_articles_by_game($guide_name, $pagesize, $page, $keyword) {
    $start = $pagesize * $page;
    $sql = "SELECT a.`id`, `guide_name`, `label`, `source`, `topic`, `author`, `icon_path`,
              `pub_date`, `src_url`, `seq`, `update_time`
            FROM " . self::TABLE . " JOIN " . self::CATEGORY . " ON a.`category`=c.`id`
            WHERE `guide_name`='$guide_name'
            ORDER BY a.`id` DESC
            LIMIT $start, $pagesize";
    return $this->DB->query($sql)->fetchAll(PDO::FETCH_ASSOC);
  }

  public function get_articles($pagesize, $page, $keyword) {

  }

  public function get_all_categories($id) {
    $sql = "SELECT `category`
            FROM " . self::TABLE . "
            WHERE `id`=$id";
    $cate = $this->DB->query($sql)->fetchColumn();
    $sql = "SELECT `id`, `cate`, label
            FROM " . self::CATEGORY . "
            WHERE 1";
    $categories = $this->DB->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    foreach ($categories as &$category) {
      if ($category['id'] === $cate) {
        $category['selected'] = 'selected';
      }
    }

    return $categories;
  }

  public function update_article_by_id($id, $topic, $content) {
    require_once(dirname(__FILE__) . '../../inc/Markdown.inc.php');
    $now = date('Y-m-d H:i:s');
    $content = \Michelf\Markdown::defaultTransform($content);
    $sql = "UPDATE " . self::TABLE . "
            SET `topic`=:topic, `content`=:content, `update_time`='$now'
            WHERE `id`=:id";
    $sth = $this->DB->prepare($sql);
    return $sth->execute(array(
      ':topic' => $topic,
      ':content' => $content,
      ':id' => $id,
    ));
  }

  public function update($id, $args) {
    $params = '';
    foreach ($args as $key => $value) {
      $params .= "`$key`=\"$value\",";
    }

    $sql = "UPDATE " . self::TABLE . "
            SET " . substr($params, 0, -1) . "
            WHERE `id`=:id";
    $sth = $this->DB->prepare($sql);
    return $sth->execute(array(
      ':id' => $id,
    ));
  }
} 