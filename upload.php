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
  'image' => 'image/',
);
$upload_user = $_SESSION['id'];

$file = $_FILES['file'];
$id = isset($_REQUEST['id']) && $_REQUEST['id'] != '' && $_REQUEST['id'] != 'undefined' ? $_REQUEST['id'] : md5(uniqid());
$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : 'ad_url';
$isPic = $type == 'pic_path' || $type == 'icon_path' || $type == 'banner_url';

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
if ($type == 'app_pic' && ($width < 320 || $height < 480)) {
  $result = array(
    'code' => 1,
    'msg' => '应用截图宽应不小于320，高应不小于480',
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
$ext = substr($file['name'], strrpos($file['name'], '.'));

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
  'url' => $new_path,
  'filename' => $file['name'],
);
echo json_encode($result);

function write_log($str) {
  $open = fopen("log.txt", "a");
  fwrite($open, $str);
  fclose($open);
}