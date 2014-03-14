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

  public function get_article_number_by_id($guide_names) {
    if (is_array($guide_names)) {
      $guide_names = implode("','", $guide_names);
    }
    $sql = "SELECT `guide_name`, COUNT('X') AS `num`
            FROM " . self::TABLE . "
            WHERE `guide_name` in ('$guide_names')
            GROUP BY `guide_name`";
    return $this->DB->query($sql)->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_UNIQUE);
  }
} 