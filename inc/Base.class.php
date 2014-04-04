<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14-3-20
 * Time: 下午1:23
 */
namespace gamepop;

class SQLBuilder {
  const SELECT = "SELECT {{fields}}
    FROM {{tables}}
    WHERE {{conditions}}";
  const UPDATE = "UPDATE {{tables}}
    SET {{fields}}
    WHERE {{conditions}}";
  const INSERT = "INSERT INTO {{tables}}
    ({{fields}})
    VALUES ({{values}})";

  public $is_select = false;
  public $args = array();
  private $sql;
  private $fields;
  private $tables;
  private $conditions = array();
  private $order_sql = '';
  private $group_by = '';
  private $template = '';
  private $reg = '/{{(\w+)}}/';

  public function __construct() {

  }

  // --> 生成select
  public function select($fields) {
    $this->sql = null;
    $this->is_select = true;
    $this->template = self::SELECT;
    $this->fields = $fields;
    return $this;
  }
  public function from($table) {
    $this->tables = $table;
    return $this;
  }
  // --> 生成update
  public function update($args) {
    $params = array();
    $conditions = array();
    foreach ($args as $key => $value) {
      $params[] = "`$key`=:$key";
      $conditions[":$key"] = $value;
    }
    $this->sql = null;
    $this->is_select = false;
    $this->fields = implode(", ", $params);
    $this->args = $conditions;
    $this->template = self::UPDATE;
    return $this;
  }
  public function on($table) {
    $this->tables = $table;
    return $this;
  }
  public function insert($args) {
    $keys = array();
    $values = array();
    $conditions = array();
    foreach ($args as $key => $value) {
      $keys[] = "`$key`";
      $conditions[] = ":$key";
      $values[":$key"] = $value;
    }
    $this->sql = null;
    $this->is_select = false;
    $this->fields = implode(", ", $keys);
    $this->conditions = $conditions;
    $this->args = $values;
    $this->template = self::INSERT;
    return $this;
  }
  public function into($table) {
    $this->tables = $table;
    return $this;
  }
  public function begin($begin) {
    return $this;
  }
  public function end($end) {
    return $this;
  }
  /**
   * 用来生成sql中的条件，可以使用多次，后面的条件会覆盖前面的同名条件
   * `key` in (value1, value2 ...) 类型的sql，在prepare的时候，必须对每个值创建单独的占位符
   * @param $args
   * @param bool $is_in 是in还是=
   * @param string $table 条件属于哪个表
   */
  public function where($args, $is_in = false, $table = '') {
    $conditions = array();
    $values = array();
    foreach ($args as $key => $value) {
      if ($value === null) {
        continue;
      }
      if ($is_in) {
        $keys = array();
        $count = 0;
        foreach ($value as $single) {
          $keys[] = ":{$key}_{$count}";
          $values[":{$key}_{$count}"] = $single;
          $count++;
        }
        $keys = implode(",", $keys);
        $conditions[] = "`$key` IN ($keys)";
      } else {
        $conditions[] = ($table ? "$table." : "") ."`$key`=:$key";
        $values[':' . $key] = $value;
      }
    }
    if (count($conditions) === 0) {
      return;
    }
    $this->sql = null;
    $this->args = array_merge($this->args, $values);
    $this->conditions = array_merge($this->conditions, $conditions);
  }
  public function search($key, $query) {
    if (!$query) {
      return $this;
    }
    $this->sql = null;
    $this->conditions[] = "`$key` LIKE :$key";
    $this->args[$key] = "%$query%";
    return $this;
  }
  public function order($key, $order = "DESC") {
    $this->order_sql = "\nORDER BY $key $order";
    return $this;
  }
  public function group($key, $table) {
    if ($key) {
      $this->group_by = "\nGROUP BY " . ($table ? "$table." : "" ) . "`$key`";
    }
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

        case 'values':
          return $this->conditions ? implode(", ", $this->conditions) : '';
          break;

        default:
          return '';
          break;
      }
    }, $this->template);
    $this->sql = $sql . $this->order_sql . $this->group_by;
    return $this->sql;
  }
}

class Base {
  static $READ;
  static $WRITE;
  static $MEMCACHE;

  const TABLE = '`table`';

  protected $builder;
  protected $sth;
  protected $result;
  protected $cache;
  protected $is_debug;
  protected $has_cache;

  public function __construct($need_write = false, $need_cache = true, $is_debug = false) {
    $this->is_debug = $is_debug;
    if (!self::$READ) {
      self::$READ = require_once(dirname(__FILE__) . '/pdo_read.php');
    }
    $this->has_cache = $need_cache;
    if ($need_cache && !self::$MEMCACHE) {
      self::$MEMCACHE = require_once(dirname(__FILE__) . '/memcache.php');
    }
    if ($need_write) {
      $this->init_write();
    }
  }

  public function getResult() {
    return $this->result;
  }

  public function init_write() {
    if (!self::$WRITE) {
      self::$WRITE = require_once(dirname(__FILE__) . '/pdo_write.php');
    }
  }

  public function select() {
    $vars = func_get_args();
    $fields = implode(",", $vars);
    $this->builder = new SQLBuilder(self::$READ);
    $this->builder->select($fields)->from($this->getTable($fields));
    $this->sth = null;
    return $this;
  }
  public function update($args, $table = '') {
    self::init_write();
    $this->builder = new SQLBuilder(self::$WRITE);
    $this->builder->update($args)->on($table ? $table : $this->getTable($args));
    $this->sth = null;
    return $this;
  }
  public function insert($args, $table = '') {
    self::init_write();
    $this->builder = new SQLBuilder(self::$WRITE);
    $this->builder->insert($args)->into($table ? $table : $this->getTable($args));
    $this->sth = null;
    return $this;
  }
  public function where($args, $is_in = false, $table = '') {
    $this->builder->where($args, $is_in, $table);
    return $this;
  }
  public function search($keyword) {
    $this->builder->search($keyword);
    return $this;
  }
  public function group($key, $table = "") {
    $this->builder->group($key, $table);
    return $this;
  }
  public function order($key, $order = 'DESC') {
    $this->builder->order($key, $order);
    return $this;
  }
  public function execute($debug = false) {
    $sql = $this->builder->output();
    if ($debug) {
      var_dump($sql);
    }
    // 读取缓存
    if ($this->has_cache && $this->builder->is_select) {
      $cache = self::$MEMCACHE->get($this->getCacheKey($sql));
      if ($cache) {
        $this->cache = $cache;
        return $this;
      }
    }

    $this->sth = $this->builder->is_select ? self::$READ->prepare($sql) : self::$WRITE->prepare($sql);
    $this->result = $this->sth->execute($this->builder->args);
    if ($this->is_debug || $debug) {
      var_dump($this->builder->args);
    }
    return $this;
  }
  public function fetch($method, $is_all = false) {
    if (!$this->sth) {
      $this->execute($this->is_debug);
    }
    if ($this->has_cache && $this->cache) {
      header('From: Memcache');
      $cache = $this->cache;
      // 只能保留一次
      $this->cache = null;
      return $cache;
    }
    $result = $is_all ? $this->sth->fetchAll($method) : $this->sth->fetch($method);
    if (!$result && $this->is_debug) {
      $this->debug_info();
    }
    if ($this->has_cache) {
      $sql = $this->builder->output();
      self::$MEMCACHE->set($this->getCacheKey($sql), $result);
    }
    return $result;
  }
  public function fetchAll($method) {
    return $this->fetch($method, true);
  }
  public function lastInsertId() {
    return self::$WRITE->lastInsertId();
  }
  public function debug_info() {
    var_dump($this->sth->errorInfo());
  }

  public function count($key = '') {
    return ($key ? "`$key`," : "") . "COUNT('X') AS NUM";
  }

  protected function getTable($fields) {
    return self::TABLE;
  }
  protected function getCacheKey($sql) {
    return $sql . json_encode($this->builder->args);
  }
}