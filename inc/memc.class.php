<?php

/**
 * Created by JetBrains PhpStorm.
 * User: HUI
 * Date: 13-10-15
 * Time: 下午2:26
 * To change this template use File | Settings | File Templates.
 */
class MemcacheMock {
  function __construct() {

  }
  function close() {

  }
  function delete($key) {

  }
  function getstats() {
    return 'I am a mock';
  }
  function get($key) {
    return null;
  }
  function set($key, $value, $exp_time, $is_compressed) {

  }
}
class memc {
  private $mc = null;

  /**
   * 构造方法,用于添加服务器并创建memcahced对象
   */
  function __construct($host, $port) {
    // 如果环境里没有memcache，则使用mock类，这样可以正常使用
    if (defined('Memcache')) {
      // 目前暂不考虑多个server的情况
      $this->mc = new Memcache;
      $this->mc->addserver($host, $port);
    } else {
      $this->mc = new MemcacheMock();
    }
  }

  function status() {
    return $this->mc->getstats();
  }
  function close() {
    $this->mc->close();
  }

  function set($key, $value, $exp_time = 60, $is_compressed = false) { // 默认缓存1分钟
    $key = md5($key);
    $this->mc->set($key, $value, $is_compressed, $exp_time);
  }
  function get($key) {
    $key = md5($key);
    return $this->mc->get($key);
  }
  function delete($key) {
    $key = md5($key);
    $this->mc->delete($key, 0); //0 表示立刻删除
  }
}
