<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14-5-14
 * Time: 下午6:05
 */
require_once "Base.class.php";

class Fetcher extends \gamepop\Base {
  const SOURCE = 't_data_source';

  static $ALL = '*';

  public function getTable($fields) {
    return self::SOURCE;
  }
} 