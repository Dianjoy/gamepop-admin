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
  'image' => 'image/',
  'attachment' => 'attachments/',
);
$upload_user = $_SESSION['id'];

$file = $_FILES['file'];
$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : 'image';
// 目前icon_path可能作为文章缩略图，也可能作为游戏图标
// 所以暂时以id来判断用户上传的类型
// 为空或者为数字的，认为是文章（文章允许新建）；不然则认为是游戏
if ($type === 'icon_path') {
  if (empty($_REQUEST['id'])) {
    $id = md5(uniqid());
    $type = 'icon_path_article';
  } else if (is_int($_REQUEST['id'])) {
    $id = (int)$_REQUEST['id'];
    $type = 'icon_path_article';
  }
} else {
  $id = empty($_REQUEST['id']) ? md5(uniqid()) : $_REQUEST['id'];
}

// 暂时只允许上传图片
$ext = substr($file['name'], strrpos($file['name'], '.'));
if ($ext != '.jpg' && $ext != '.gif' && $ext != '.png') {
  exit(json_encode(array(
    'code' => 1,
    'msg' => '只支持上传图片',
  )));
}

//检查上传的图片是否符合要求
$image_arr = getimagesize($file['tmp_name']);
$width = $image_arr[0];
$height = $image_arr[1];
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
if (isset($result) && $result['code'] === 1) {
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
  'url' => (defined('DEBUG') ? './upload/' : 'http://r.yxpopo.com/') . substr($new_path, 7),
  'filename' => $file['name'],
);
echo json_encode($result);

function write_log($str) {
  $open = fopen("log.txt", "a");
  fwrite($open, $str);
  fclose($open);
}