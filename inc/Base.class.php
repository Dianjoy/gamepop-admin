<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14-3-20
 * Time: 下午1:23
 */

namespace gamepop;


class Base {
  static $READ;
  static $WRITE;

  public function __construct($need_write = false) {
    if (!self::$READ) {
      self::$READ = require_once(dirname(__FILE__) . '/pdo_read.php');
    }
    if ($need_write) {
      $this->initWrite();
    }
  }

  public function initWrite() {
    if (!self::$WRITE) {
      self::$WRITE = require_once(dirname(__FILE__) . '/pdo_write.php');
    }
  }
} 