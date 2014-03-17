<?php
defined('OPTIONS') or exit();
session_start();

$string = OPTIONS;
$pos_right = strpos($string, ')');
while ($pos_right) {
  $pos_left = strrpos(substr($string, 0, $pos_right), '(');
  $string = substr($string, 0, $pos_left) . result_without_parenthese(substr($string, $pos_left + 1, $pos_right - $pos_left - 1)) . substr($string, $pos_right + 1);
  $pos_right = strpos($string, ')');
}

if (result_without_parenthese($string) == 'false') {
  require(dirname(__FILE__) . '/Template.class.php');
  $tpl = new Template(dirname(__FILE__) . '/../web/template/permission-error.html');
  header("HTTP/1.1 401 Unauthorized");
  exit($tpl->render());
}

function result_without_parenthese($str) {
  $result = explode('|', $str);
  foreach ($result as $sub_str) {
    if (result_without_or($sub_str) == 'true') {
      return 'true';
    }
  }
  return 'false';
}

function result_without_or($str) {
  $result = explode('&', $str);
  foreach ($result as $sub_str) {
    if (!in_array($sub_str, $_SESSION['permission']) && $sub_str != 'true' || $sub_str == 'false') {
      return 'false';
    }
  }
  return 'true';
}
?>