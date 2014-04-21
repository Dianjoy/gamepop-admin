<?php
  session_start();
  $username = trim($_REQUEST['username']);
  $password  = trim($_REQUEST['password']);
  $verifycode  = $_POST['verifycode'];

  if ($verifycode != $_SESSION["Checknum"]) {
    $_SESSION['msg'] = "验证码错误！";
    header("location:login.php");
    exit();
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

      $turn = './' . $hash;
    } else {
      $_SESSION['msg'] = "用户名或密码不正确！";
      $turn = 'login.php';
    }
  } else {
    $_SESSION['msg'] = "用户名或密码不能为空！";
    $turn = 'login.php';
  }

  header("location:$turn");