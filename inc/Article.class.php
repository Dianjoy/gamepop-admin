<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14-3-14
 * Time: 下午2:41
 */

class Article {
  const TABLE = '`t_article`';

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

  public function get_articles_by_game($guide_name, $pagesize, $page, $keyword) {
    $start = $pagesize * $page;
    $sql = "SELECT `id`, `guide_name`, `type_name`, `source`, `topic`, `author`, `icon_path`,
              `pub_date`, `src_url`, `seq`, `update_time`
            FROM " . self::TABLE . " a JOIN t_guide_type_name t ON a.`guide_type`=t.`guide_type`
            WHERE `guide_name`='$guide_name'
            ORDER BY `id` DESC
            LIMIT $start, $pagesize";
    return $this->DB->query($sql)->fetchAll(PDO::FETCH_ASSOC);
  }

  public function get_articles($pagesize, $page, $keyword) {

  }
} 