<?php
/**
 * Created by PhpStorm.
 * Date: 14-4-26
 * Time: 下午11:39
 * @overview 
 * @author Meatill <lujia.zhai@dianjoy.com>
 * @since 
 */
die('一次性，已完成');
require_once "../inc/Admin.class.php";

// 生成20个账号
$prefix = 'contributor';
$fullname_prefix = '外包';
$num = 20;
$role = Admin::OUTSIDER;
$admin = new Admin(true);

if ($_REQUEST['m'] == 'accounts') {
  echo '<table><tr><th>用户名</th><th>密码</th></tr>';
  for ($i = 0; $i < $num; $i++) {
    $account = $prefix . $i;
    $password = create_password();
    $fullname = $fullname_prefix . $i;
    if ($admin->add($account, $fullname, $password, $role)) {
      echo "<tr><td>$account</td><td>$password</td></tr>";
    }
  }
  echo '</table>';
}

// 将所有游戏分配给这20个账号
if ($_REQUEST['m'] == 'games') {
  $sql = "SELECT `guide_name`
          FROM `t_game`
          WHERE `status`=0";
  $names = Admin::$READ->query($sql)->fetchAll(PDO::FETCH_COLUMN);

  $count = 0;
  $sql = "INSERT INTO `o_game_user`
          (`guide_name`, `user_id`)
          VALUES ";
  foreach ($names as $name) {
    $user_id = 10 + ($count % 20);
    $sql .= "('$name', $user_id),";
    $count++;
  }
  $sql = substr($sql, 0, -1);
  echo Admin::$WRITE->exec($sql);
}

function create_password($length = 12) {
  $chars = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$_-";
  $password = '';
  for ($i = 0; $i < $length; $i++) {
    $password .= $chars[rand(0, strlen($chars) - 1)];
  }
  return $password;
}