<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14-3-10
 * Time: 下午2:36
 */
include_once 'Base.class.php';

class Admin extends \gamepop\Base {
  const TABLE = '`t_admin`';
  const LOG = '`t_admin_log`';
  const OUTSIDER_LOG = '`t_outsider_op_log`';

  const NORMAL = 0;
  const DELETE = 1;

  const ROOT = 0;
  const DEVELOPER = 1;
  const EDITOR = 2;
  const OUTSIDER = 100;

  static $ALL = "`id`, `fullname`, `role`";
  static $BASE = "`id`, `fullname`";

  static function is_outsider() {
    return (int)$_SESSION['role'] === self::OUTSIDER;
  }
  static function log_outsider_action($article_id, $operation, $label = '') {
    self::init_write();
    $user_id = $_SESSION['id'];
    $now = date('Y-m-d H:i:s');
    $sql = "INSERT INTO " . self::OUTSIDER_LOG . "
            (`user_id`, `time`, `operation`, `label`, `article_id`)
            VALUES ($user_id, '$now', '$operation', '$label', $article_id)";
    self::$WRITE->exec($sql);
  }

  public static $ROLES = array(
    0 => '管理员',
    1 => '开发',
    2 => '编辑',
    100 => '外包编辑',
  );

  public static $PERMISSION = array(
    0 => array( // 管理员权限
      'root',
      'game',
      'upload',
      'article',
      'app',
      'article_wb',
    ),
    1 => array( // 开发权限
      'game',
      'article',
      'upload',
      'app',
      'article_wb',
    ),
    2 => array( // 编辑权限
      'game',
      'upload',
      'article',
      'app',
    ),
    100 => array(
      'article_wb',
      'upload',
    ),
  );

  public function __construct($need_write = false) {
    parent::__construct($need_write, false);
  }

  protected function getTable($fields) {
    return self::TABLE;
  }

  public function update($args, $table = '') {
    if (isset($args['password'])) {
      $args['password'] = $this->encrypt($_SESSION['user'], $args['password']);
    }
    return parent::update($args, $table);
  }

  public function where($args, $table = '', $relation = '=', $is_or = false) {
    if (isset($args['password'])) {
      $args['password'] = $this->encrypt($args['user'], $args['password']);
    }
    return parent::where($args, $table, $relation);
  }

  public function add($username, $fullname, $password, $role, $qq = '') {
    $this->init_write();
    $password = $this->encrypt($username, $password);
    $sql = "INSERT INTO " . self::TABLE . "
            (`user`, `fullname`, `password`, `role`, `qq`)
            VALUES ('$username', '$fullname', '$password', $role, '$qq')";
    return self::$WRITE->exec($sql);
  }

  public function delete($id) {
    self::init_write();
    $sql = "UPDATE " . self::TABLE . "
            SET `status`=" . self::DELETE . "
            WHERE `id`=$id";
    return self::$WRITE->exec($sql);
  }

  public function get_live_admins() {
    $sql = "SELECT t.`id`, `user`, `fullname`, `qq`, `role`, MAX(`login_time`) AS last_login
            FROM " . self::TABLE . " t LEFT JOIN " . self::LOG . " l ON t.`id`=l.`userid`
            WHERE `status`=" . self::NORMAL . "
            GROUP BY t.`id`";
    $result = self::$READ->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    foreach ($result as &$row) {
      $row['role_label'] = self::$ROLES[$row['role']];
    }
    return $result;
  }

  public function insert_login_log($id, $ip) {
    self::init_write();
    $now = date('Y-m-d H:i:s');
    $sql = "INSERT INTO " . self::LOG . "
            (`userid`, `ip`, `login_time`)
            VALUES ($id, '$ip', '$now')";
    return self::$WRITE->exec($sql);
  }

  private function encrypt($username, $password) {
    return md5($password.$username);
  }
}