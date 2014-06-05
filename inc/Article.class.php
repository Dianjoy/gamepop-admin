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
  const CATEGORY_IMAGE = '`t_article_category_image`';

  const NORMAL = 0;
  const DELETED = 1;
  const DRAFT = 2;
  const FETCHED = 3;

  static $ALL = "`t_article`.`id`, `guide_name`, `category`, `label`, `source`,
    `topic`, `author`, `t_article`.`icon_path`, `pub_date`, `src_url`, `seq`, `remark`,
    `update_time`, `update_editor`, `is_top`, `is_index`, `t_article`.`status`";
  static $TOP = "`id`, `topic`, `update_time`, `seq`, `is_top`, `icon_path`, `source`, `author`";
  static $DETAIL = "`guide_name`, `category`, `label`, `source`,
    `topic`, `author`, `icon_path`, `content`, `remark`, `pub_date`, `src_url`,
     `seq`, `update_time`, `update_editor`, `t_article`.`status`, `is_top`";
  static $ALL_CATEGORY = "`t_article_category`.`id`, `cate`, `label`";

  public function __construct($need_write = false, $need_cache = true, $is_debug = false) {
    parent::__construct($need_write, $need_cache, $is_debug);
  }
  // overrides parent's method
  public function search($keyword) {
    $this->builder->search('topic', $keyword);
    return $this;
  }

  protected function getTable($fields) {
    if (is_string($fields)) {
      if ($fields == self::$ALL || $fields == self::$DETAIL) {
        return self::TABLE . " LEFT JOIN " . self::CATEGORY . " ON " . self::TABLE . ".`category`=" . self::CATEGORY . ".`id`";
      }
      if (strpos($fields, self::$ALL_CATEGORY) !== false) {
        return self::CATEGORY . " RIGHT JOIN " . self::TABLE . " ON " . self::TABLE . ".`category`=" . self::CATEGORY . ".`id`";
      }
    }
    if (is_array($fields)) {
      foreach ($fields as $key => $value) {
        if ($key === 'label' || $key === 'cate') {
          return self::CATEGORY;
        }
      }
    }
    return self::TABLE;
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
} 