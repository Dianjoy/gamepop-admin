<?php

function output($is_success, $is_ajax, $msg, $redirect_to) {
  if ($is_ajax) {
    header("Content-type: application/json;charset=UTF-8");
    $result = array(
      'code' => $is_success ? 0 : 1,
      'msg' => $msg,
    );
    echo json_encode($result);
  } else {
    $_SESSION['msg'] = $msg;
    header("location:$redirect_to");
  }
  exit();
}

session_start();
$username = trim($_REQUEST['username']);
$password  = trim($_REQUEST['password']);
$verifycode  = $_POST['verifycode'];
$is_ajax = strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
$is_success = false;

if ($verifycode != $_SESSION["Checknum"]) {
  $msg = "验证码错误！";
  $turn = "login.php";
  output(false, $is_ajax, $msg, $turn);
}

if ($username != "" && $password != "") {
  include_once('inc/Admin.class.php');
  $admin = new Admin();
  $info = $admin->select(Admin::$ALL)
    ->where(array(
      'user' => $username,
      'password' => $password,
    ))
    ->fetch(PDO::FETCH_ASSOC);
  if ($info) {
    unset($_SESSION['Checknum']);
    unset($_SESSION['msg']);
    $_SESSION['user'] = $username; // 管理员账号
    $_SESSION['id'] = $info['id']; // 管理员id
    $_SESSION['fullname'] = $info['fullname']; // 姓名
    $_SESSION['role'] = $info['role']; // 级别
    $_SESSION['permission'] = Admin::$PERMISSION[$info['role']];
    $hash = $_REQUEST['hash'];

    $ip = $_SERVER['REMOTE_ADDR'];
    $admin->insert_login_log($info['id'], $ip);

    $is_success = true;
    $msg = '登录成功';
    $turn = './' . $hash;
  } else {
    $msg = "用户名或密码不正确！";
    $turn = 'login.php';
  }
} else {
  $msg = "用户名或密码不能为空！";
  $turn = 'login.php';
}

output($is_success, $is_ajax, $msg, $turn);
