<?php
  session_start();
  $username = trim($_REQUEST['username']);
  $password  = trim($_REQUEST['password']);
  $verifycode  = $_POST['verifycode'];

  //防止sql注入
  function filter_login($get_value) {
    $filter = array(
      (strpos($get_value, ' ') !== false),
      (strpos($get_value, "'") !== false),
      (strpos($get_value, '"') !== false),
    );
    if (in_array(1, $filter)) {
      header("location:login.php");
    }
  }	

  if ($verifycode != $_SESSION["Checknum"]) {
    $_SESSION['msg'] = "验证码错误！";
    header("location:login.php");
  }

  if ($username != "" && $password != "") {
    filter_login($username);
    filter_login($password);
    $DB = require_once("inc/pdo.php");
    include_once('inc/Admin.class.php');
    $admin = new Admin($DB);
    $info = $admin->check($username, $password);
    if ($info) {
      unset($_SESSION['Checknum']);
      unset($_SESSION['msg']);
      $_SESSION['user'] = $username; // 管理员账号
      $_SESSION['id'] = $info['id']; // 管理员id
      $_SESSION['fullname'] = $info['fullname']; // 姓名
      $_SESSION['role'] = $info['role']; // 级别
      $_SESSION['permission'] = Admin::$PERMISSION[$info['role']];

      $ip = $_SERVER['REMOTE_ADDR'];
      $admin->insert_login_log($user_id, $ip);

      $turn = './';
    } else {
      $_SESSION['msg'] = "用户名或密码不正确！";
      $turn = 'login.php';
    }
  } else {
    $_SESSION['msg'] = "用户名或密码不能为空！";
    $turn = 'login.php';
  }

  header("location:$turn");