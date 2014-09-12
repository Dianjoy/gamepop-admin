<?php
define('OPTIONS', 'upload');
include_once 'inc/session.php';
?>
<?php
/**
 * 处理用户上传
 * @author Meathill
 */

header("Content-Type: application/json; charset: utf-8");

$up_path = array(
  'icon_path' => 'icon/',
  'icon_path_article' => 'icon/',
  'poster' => 'image/',
  'big_pic' => 'image/',
  'blur_pic' => 'image/',
  'logo' => 'image/',
  'image' => 'image/',
  'attachment' => 'attachments/',
);
$upload_user = $_SESSION['id'];

$file = $_FILES['file'];
$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : 'image';
$id = empty($_REQUEST['id']) ? md5(uniqid()) : $_REQUEST['id'];

// 暂时只允许上传图片
$ext = substr($file['name'], strrpos($file['name'], '.'));
if ($ext != '.jpg' && $ext != '.gif' && $ext != '.png') {
  header("HTTP/1.1 415 Unsupported Media Type");
  exit(json_encode(array(
    'code' => 1,
    'msg' => '只支持上传图片',
  )));
}

//检查上传的图片是否符合要求
$image_arr = getimagesize($file['tmp_name']);
$width = $image_arr[0];
$height = $image_arr[1];
$min_width = (int)$_REQUEST['min-width'];
$min_height = (int)$_REQUEST['min-height'];
$ratio = $_REQUEST['ratio'];
if ($min_width || $min_height || $ratio) { // 按照提交的要求检查图片
  if ($min_width && $min_width > $width) {
    $result = array(
      'code' => 1,
      'msg' => "图片宽度不得小于$min_width",
    );
  }
  if ($min_height && $min_height > $height) {
    $result = array(
      'code' => 1,
      'msg' => "图片高度不得小于$min_height",
    );
  }
  if ($width / $height != $ratio) {
    $result = array(
      'code' => 1,
      'msg' => "图片宽高比应为$ratio",
    );
  }
} else { // 按照内设标准检查图片
  if ($type == 'icon_path' && ($width != $height || $width < 72)) {
    $result = array(
      'code' => 1,
      'msg' => '应用图标应宽高相等，且均大于72px',
    );
  }
  if ($type == 'icon_path_article' && ($width / $height * 2 != 3)) {
    $result = array(
      'code' => 1,
      'msg' => '文章缩略图应使用3:2的图片，宽240，高160',
    );
  }
}
if (isset($result) && $result['code'] === 1) {
  header("HTTP/1.1 406 Not Acceptable");
  exit(json_encode($result));
}

$up_path = 'upload/' . $up_path[$type];
$dir = $up_path . date("Ym") . '/';
if (!is_dir($dir)) {
  mkdir($dir, 0777, TRUE);
}

$index = 0;
$new_path = $dir . $index . '_' . $id . $ext;
while (file_exists($new_path)) {
  $index++;
  $new_path = $dir . $index . '_' . $id . $ext;
}

move_uploaded_file($file['tmp_name'], $new_path);

// 记录到log里
$DB = require("inc/pdo_write.php");
require_once('inc/upload.class.php');
upload::insert($DB, $id, $type, $new_path, $upload_user, $file['name']);

$result = array(
  'code' => 0,
  'type' => $type,
  'url' => defined('DEBUG') ? $new_path : ('http://r.yxpopo.com/' . substr($new_path, 7)),
  'filename' => $file['name'],
);
echo json_encode($result);

function write_log($str) {
  $open = fopen("log.txt", "a");
  fwrite($open, $str);
  fclose($open);
}