<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14-3-20
 * Time: 下午1:23
 */
namespace gamepop;

class SQLBuilder {
  public $is_select = false;
  public $args = array();
  private $sql;
  private $fields;
  private $tables;
  private $conditions = array();
  private $template = "SELECT {{fields}}
    FROM {{tables}}
    WHERE {{conditions}}";
  private $reg = '/{{(\w+)}}/';

  public function __construct() {

  }

  public function select($fields) {
    $this->sql = null;
    $this->is_select = true;
    $this->fields = $fields;
    return $this;
  }
  public function from($table) {
    $this->sql = null;
    $this->tables = $table;
    return $this;
  }
  public function begin($begin) {
    return $this;
  }
  public function end($end) {
    return $this;
  }
  public function where($args, $is_in = false) {
    $conditions = array();
    foreach ($args as $key => $value) {
      if ($value === null) {
        continue;
      }
      $conditions[] = $is_in ? "`$key` IN (:$key)" : "`$key`=:$key";
    }
    if (count($conditions) === 0) {
      return;
    }
    $this->sql = null;
    $this->args = array_merge($this->args, $args);
    $this->conditions = array_merge($this->conditions, $conditions);
  }
  public function search($key, $query) {
    if (!$query) {
      return $this;
    }
    $this->sql = null;
    $this->conditions[] = "`$key` LIKE '%$query'";
    $this->args[$key] = $query;
    return $this;
  }
  public function output() {
    if ($this->sql) {
      return $this->sql;
    }
    // 三大件
    $sql = preg_replace_callback($this->reg, function ($matches) {
      switch ($matches[1]) {
        case 'conditions':
          return $this->conditions ? implode(" AND ", $this->conditions) : '1';
          break;

        case 'fields':
          return $this->fields;
          break;

        case 'tables':
          return $this->tables;
          break;
      }
    }, $this->template);
    $this->sql = $sql;
    return $sql;
  }
}
class Base {
  static $READ;
  static $WRITE;
  static $MEMCACHE;

  const TABLE = '`table`';

  protected $builder;
  protected $sth;
  protected $rs;
  protected $cache;

  public function __construct($need_write = false) {
    if (!self::$READ) {
      self::$READ = require_once(dirname(__FILE__) . '/pdo_read.php');
    }
    if (!self::$MEMCACHE) {
      self::$MEMCACHE = require_once(dirname(__FILE__) . '/memcache.php');
    }
    if ($need_write) {
      $this->init_write();
    }
  }

  public function init_write() {
    if (!self::$WRITE) {
      self::$WRITE = require_once(dirname(__FILE__) . '/pdo_write.php');
    }
  }

  public function select($fields) {
    $this->builder = new SQLBuilder(self::$READ);
    $this->builder->select($fields)->from($this->getTable($fields));
    return $this;
  }
  public function where($args) {
    $this->builder->where($args);
    return $this;
  }
  public function search($keyword) {
    $this->builder->search($keyword);
    return $this;
  }
  public function execute($debug = false) {
    $sql = $this->builder->output();
    if ($debug) {
      var_dump($sql);
    }
    // 读取缓存
    if ($this->builder->is_select) {
      $cache = self::$MEMCACHE->get($sql);
      if ($cache) {
        $this->cache = $cache;
        return $this;
      }
    }

    $this->sth = $this->builder->is_select ? self::$READ->prepare($sql) : self::$WRITE->prepare($sql);
    $this->rs = $this->sth->execute($this->builder->args);
    return $this;
  }
  public function result() {
    return $this->rs;
  }
  public function fetch($method, $is_all = false) {
    if ($this->cache) {
      var_dump('cache');
      $cache = $this->cache;
      // 只能保留一次
      $this->cache = null;
      return $cache;
    }
    $result = $is_all ? $this->sth->fetchAll($method) : $this->sth->fetch($method);
    $sql = $this->builder->output();
    self::$MEMCACHE->set($sql, $result);
    return $result;
  }
  public function fetchAll($method) {
    return $this->fetch($method, true);
  }
  public function debug_info() {
    var_dump($this->sth->errorInfo());
  }

  protected function getTable($fields) {
    return self::TABLE;
  }
} 