<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14-3-14
 * Time: 下午2:41
 */
include_once 'Base.class.php';

class Article extends \gamepop\Base {
  const TABLE = '`t_article`';
  const CATEGORY = '`t_article_category`';

  public function __construct($need_write = false) {
    parent::__construct($need_write);
  }

  public function add_category($label) {
    self::init_write();
    $condition = array(
      ':label' => $label,
    );
    // 先判断是否存在
    $sql = "SELECT `id`
            FROM " . self::CATEGORY . "
            WHERE `label`=:label";
    $sth = self::$READ->prepare($sql);
    $sth->execute($condition);
    $id = $sth->fetchColumn();
    if ($id) {
      return $id;
    }
    // 不存在再创建
    $sql = "INSERT INTO " . self::CATEGORY . "
            (`label`)
            VALUES (:label)";
    $sth = self::$WRITE->prepare($sql);
    $check = $sth->execute($condition);
    if ($check) {
      return self::$WRITE->lastInsertId();
    }
    return $check;
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
    return self::$READ->query($sql)->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_UNIQUE);
  }

  public function get_article_by_id($id) {
    require_once(dirname(__FILE__) . '../../inc/HTML_To_Markdown.php');
    $sql = "SELECT `guide_name`, `label`, `content`, `source`, `topic`, `author`, a.`icon_path`
            FROM " . self::TABLE . " a JOIN " . self::CATEGORY . " c ON a.`category`=c.`id`
            WHERE a.`id`='$id'";
    $article = self::$READ->query($sql)->fetch(PDO::FETCH_ASSOC);
    if (get_magic_quotes_gpc()) {
      $article['content'] = stripslashes($article['content']);
    }
    $markdown = new HTML_To_Markdown($article['content']);
    $article['content'] = str_replace('](/', '](http://r.yxpopo.com/yxpopo/', htmlspecialchars($markdown));
    return $article;
  }

  public function get_articles_by_game($guide_name, $pagesize, $page, $keyword) {
    $start = $pagesize * $page;
    $sql = "SELECT a.`id`, `guide_name`, `category`, `label`, `source`, `topic`, `author`, a.`icon_path`,
              `pub_date`, `src_url`, `seq`, `update_time`
            FROM " . self::TABLE . " a JOIN " . self::CATEGORY . " c ON a.`category`=c.`id`
            WHERE `guide_name`='$guide_name'
            ORDER BY a.`id` DESC
            LIMIT $start, $pagesize";
    return self::$READ->query($sql)->fetchAll(PDO::FETCH_ASSOC);
  }

  public function get_articles($pagesize, $page, $keyword) {

  }

  public function get_all_categories() {
    $sql = "SELECT `id`, `cate`, label
            FROM " . self::CATEGORY . "
            WHERE 1";
    return self::$READ->query($sql)->fetchAll(PDO::FETCH_ASSOC);
  }

  public function update($id, $args) {
    self::init_write();
    $params = '';
    $now = date('Y-m-d H:i:s');
    $me = (int)$_SESSION['id'];
    foreach ($args as $key => $value) {
      if ($key === 'label') {
        unset($args['label']);
        continue;
      }
      $params .= "`$key`=:$key,";
    }

    $sql = "UPDATE " . self::TABLE . "
            SET " . $params . " `update_time`='$now', `update_editor`=$me
            WHERE `id`=:id" ;
    $sth = self::$WRITE->prepare($sql);
    $params = array(
      ':id' => $id,
    );
    foreach ($args as $key => $value) {
      if ($key === 'content') {
        require_once('Markdown.inc.php');
        $value = \Michelf\Markdown::defaultTransform($value);
      }
      $params[':' . $key] = $value;
    }
    $result = $sth->execute($params);
    return $result;
  }

  public function update_category($id, $args) {
    self::init_write();
    $params = '';
    foreach ($args as $key => $value) {
      $params .= "`$key`=:$key,";
    }

    $sql = "UPDATE " . self::CATEGORY . "
            SET " . $params . "
            WHERE `id`=:id" ;
    $sth = self::$WRITE->prepare($sql);
    $params = array(
      ':id' => $id,
    );
    foreach ($args as $key => $value) {
      $params[':' . $key] = $value;
    }
    $result = $sth->execute($params);
    return $result;
  }
} 