<?php
	session_start();
  $_SESSION['user'] = null; // 管理员账号
  $_SESSION['id'] = null; // 管理员id
  $_SESSION['fullname'] = null; // 姓名
  $_SESSION['role'] = null; // 级别
  $_SESSION['permission'] = null;

  header("location:./login.php");