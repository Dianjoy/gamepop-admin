<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14-6-5
 * Time: 下午4:45
 */

class Log extends \gamepop\Base {
  const SEARCH = '`t_searchlog`';

  static $SEARCH = "`skey`, COUNT('X') AS `num`";

  protected function getTable($field = '') {
    if ($field === self::$SEARCH) {
      return self::SEARCH;
    }
  }
} 