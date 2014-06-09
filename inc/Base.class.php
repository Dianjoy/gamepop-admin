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
  private $key_dict = array();
  private $order_sql = '';
  private $group_by = '';
  private $limit = '';
  private $template = '';
  private $reg = '/{{(\w+)}}/';

  public function __construct() {
    $this->key_dict = array();
  }

  // --> 生成select
  public function select($fields) {
    $this->sql = null;
    $this->is_select = true;
    $this->template = self::SELECT;
    $this->fields = $fields;
    $this->conditions = array();
    $this->args = array();
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
  /**
   * 用来生成sql中的条件，可以使用多次，后面的条件会覆盖前面的同名条件
   * `key` in (value1, value2 ...) 类型的sql，在prepare的时候，必须对每个值创建单独的占位符
   * @param array $args
   * @param string $table 条件属于哪个表
   * @param bool $relation key与值的关系
   * @param bool $is_or 是or还是and
   */
  public function where($args, $table = '', $relation = '=', $is_or = false) {
    $conditions = array();
    $values = array();
    foreach ($args as $key => $value) {
      if ($value === null) {
        continue;
      }
      $value_key = $this->strip($key);
      if (is_array($value)) {
        $keys = array();
        $count = 0;
        $value = array_unique($value);
        foreach ($value as $single) {
          $keys[] = ":{$value_key}_{$count}";
          $values[":{$value_key}_{$count}"] = $single;
          $count++;
        }
        $keys = implode(",", $keys);
        $conditions[] = $this->strip_multi_accent("`$key` $relation ($keys)");
      } else if ($relation === Base::R_IS || $relation === Base::R_IS_NOT) {
        $conditions[] = $this->strip_multi_accent(($table ? "`$table`." : "") . "`$key` $relation NULL");
      } else {
        $conditions[] = $this->strip_multi_accent(($table ? "`$table`." : "") . "`$key`$relation:$value_key");
        $values[':' . $value_key] = $value;
      }
    }
    if (count($conditions) === 0) {
      return;
    }
    $this->args = array_merge($this->args, $values);
    $this->conditions[] = array($is_or, $conditions);
  }
  public function search($key, $query, $is_or = false) {
    if (!$query) {
      return $this;
    }
    $this->conditions[] = array($is_or, "`$key` LIKE :$key");
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
  public function limit($start, $length) {
    $this->limit = "\nLIMIT $start,$length";
  }
  public function output() {
    if ($this->sql) {
      return $this->sql;
    }
    // 三大件
    $sql = preg_replace_callback($this->reg, function ($matches) {
      switch ($matches[1]) {
        case 'conditions':
          if (!$this->conditions) {
            return 1;
          }
          foreach ($this->conditions as $key => $conditions) {
            $is_or = $conditions[0];
            $connect = $is_or ? ' OR ' : ' AND ';
            $conditions = $conditions[1];
            $conditions = is_array($conditions) ? implode(" $connect ", $conditions) : $conditions;
            $conditions = $is_or ? "($conditions)" : $conditions;
            $this->conditions[$key] = $conditions;
          }
          return implode(' AND ', $this->conditions);
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
    $this->sql = $sql . $this->order_sql . $this->group_by . $this->limit;
    return $this->sql;
  }

  private function strip_multi_accent($string) {
    return preg_replace('/`{2,}/', '`', $string);
  }
  private function strip($string) {
    $key = preg_replace('/[`\.]/', '', $string);
    $count = 0;
    while (in_array($key, $this->key_dict)) {
      $key = $key . $count;
      $count++;
    }
    $this->key_dict[] = $key;
    return $key;
  }
}

class Base {
  static $READ;
  static $WRITE;
  static $MEMCACHE;

  const TABLE = '`table`';

  const R_EQUAL = '=';
  const R_NOT_EQUAL = '!=';
  const R_IN = 'IN';
  const R_NOT_IN = 'NOT IN';
  const R_IS = 'IS';
  const R_IS_NOT = 'IS NOT';
  const R_LESS = '<';
  const R_LESS_EQUAL = '<=';
  const R_MORE = '>';
  const R_MORE_EQUAL = '>=';

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
  public function where($args, $table = '', $relation = '=', $is_or = false) {
    $this->builder->where($args, $table, $relation, $is_or);
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
  public function limit($start, $length) {
    $this->builder->limit($start, $length);
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
    try {
      $this->result = $this->sth->execute($this->builder->args);
    } catch (\Exception $e) {
      var_dump($this->sth->errorInfo);
      var_dump($e->getMessage());
    }

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

  public function count($key = '', $table = '') {
    return "COUNT(" . ($table ? "`$table`" : "") . ($key ? "`$key`" : "'X'") . ") AS NUM";
  }

  protected function getTable($fields) {
    return self::TABLE;
  }
  protected function getCacheKey($sql) {
    return $sql . json_encode($this->builder->args);
  }
}