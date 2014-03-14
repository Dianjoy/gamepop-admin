<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14-3-10
 * Time: 下午2:36
 */

class Admin {
  const TABLE = 't_admin';
  const LOG = 't_admin_log';

  const NORMAL = 0;
  const DELETE = 1;

  public static $ROLES = array(
    '管理员',
    '开发',
    '编辑'
  );

  public static $PERMISSION = array(
    array( // 管理员权限
      'root',
      'game',
      'upload',
    ),
    array( // 开发权限
      'game',
      'upload',
    ),
    array( // 编辑权限
      'game',
      'upload',
    )
  );

  private $DB;

  public function __construct(PDO $DB) {
    $this->DB = $DB;
  }

  private function encrypt($username, $password) {
    return md5($password.$username);
  }

  public function add($username, $fullname, $password, $role, $qq) {
    $password = $this->encrypt($username, $password);
    $sql = "INSERT INTO `" . self::TABLE . "`
            (`user`, `fullname`, `password`, `role`, `qq`)
            VALUES ('$username', '$fullname', '$password', $role, '$qq')";
    return $this->DB->exec($sql);
  }

  public function get_admin($username, $password) {
    $password = $this->encrypt($username, $password);
    $sql = "SELECT `id`, `fullname`, `role`
            FROM `". self::TABLE . "`
            WHERE `user`='$username' AND `password`='$password'";
    return $this->DB->query($sql)->fetch(PDO::FETCH_ASSOC);
  }

  public function delete($id) {
    $sql = "UPDATE `" . self::TABLE . "`
            SET `status`=" . self::DELETE . "
            WHERE `id`=$id";
    return $this->DB->exec($sql);
  }

  public function get_live_admins() {
    $sql = "SELECT t.`id`, `user`, `fullname`, `qq`, `role`, MAX(`login_time`) AS last_login
            FROM `" . self::TABLE . "` t LEFT JOIN `" . self::LOG . "` l ON t.`id`=l.`userid`
            WHERE `status`=" . self::NORMAL . "
            GROUP BY t.`id`";
    $result = $this->DB->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    foreach ($result as &$row) {
      $row['role_label'] = self::$ROLES[$row['role']];
    }
    return $result;
  }

  public function insert_login_log($id, $ip) {
    $now = date('Y-m-d H:i:s');
    $sql = "INSERT INTO `" . self::LOG . "`
            (`userid`, `ip`, `login_time`)
            VALUES ($id, '$ip', '$now')";
    return $this->DB->exec($sql);
  }

  public function is_exist($username) {
    $sql = "SELECT 'X'
            FROM `" . self::TABLE . "`
            WHERE `user`='$username'";
    return $this->DB->query($sql)->fetchColumn();
  }

  public function update($id, $fullname, $password, $role) {
    $sql = "SELECT `user`
            FROM `" . self::TABLE . "`
            WHERE `id`=$id";
    $username = $this->DB->query($sql)->fetchColumn();
    if (!$username) {
      return false;
    }
    $password = $this->encrypt($username, $password);
    $sql = "UPDATE `" . self::TABLE . "`
            SET `fullname`='$fullname', `password`='$password', `role`=$role
            WHERE `id`=$id";
    return $this->DB->exec($sql);
  }

  public function destroy() {
    $this->DB = null;
  }
}