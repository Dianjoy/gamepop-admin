<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14-6-6
 * Time: 下午5:45
 */
require_once "Base.class.php";

class App extends \gamepop\Base {
  const HOMEPAGE = '`t_app_homepage`';

  const NORMAL = 0;

  static $HOMEPAGE = '`id`, `guide_name`, `big_pic`, `logo`, `create_time`, `status`';

  protected function getTable($field = '') {
    if ($field === self::$HOMEPAGE) {
      return self::HOMEPAGE;
    }
  }
} 