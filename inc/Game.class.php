<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14-3-11
 * Time: 上午11:24
 */
include_once 'Base.class.php';

class Game extends \gamepop\Base {
  const TABLE = '`t_game`';
  const MIDDLE = '`m_pack_guide`';
  const APK_INFO = '`t_app_info`';
  const SLIDE = '`t_game_slide`';
  const HOMEPAGE_NAV = '`t_article_category_image`';
  const OUTSIDE = '`o_game_user`';
  const TAGS = '`m_game_tags`';

  const NORMAL = 0;
  const DELETED = 1;

  const ID = 'guide_name';

  static $ALL = "`guide_name`, `game_name`, `game_desc`, `update_time`, `icon_path`,
   `os_android`, `os_ios`, `update_time`, `hot`, `tags`";
  static $SLIDE = "`id`, `image`, `link`, `seq`";
  static $HOMEPAGE_NAV = "`category`, `id`, `guide_name`, `image`, `seq`, `status`, `order_by`";
  static $OUTSIDE = "`id`, `guide_name`, `user_id`, `score`";
  static $TAGS = "`id`, `tag`";

  static $ORDER_HOT = "hot";

  public function __construct($need_write = false, $need_cache = true, $is_debug = false) {
    parent::__construct($need_write, $need_cache, $is_debug);
  }

  public function search($keyword) {
    $this->builder->search('game_name', $keyword);
    return $this;
  }
  protected function getTable($fields) {
    if ($fields === self::$ALL) {
      return self::TABLE;
    }
    if ($fields === self::$SLIDE) {
      return self::SLIDE;
    }
    if ($fields === self::$HOMEPAGE_NAV) {
      return self::HOMEPAGE_NAV;
    }
    if ($fields === self::$OUTSIDE) {
      return self::OUTSIDE;
    }
    if ($fields === self::$TAGS) {
      return self::TAGS;
    }
    if (is_array($fields)) {
      foreach ($fields as $key => $value) {
        if ($key === 'link' || $key === 'image') {
          return self::SLIDE;
        }
        if ($key === 'user_id') {
          return self::OUTSIDE;
        }
      }
    }
    return self::TABLE;
  }
}