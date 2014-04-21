<?php
define('OPTIONS', 'root');
include_once '../../inc/session.php';
?>
<?php
require '../../inc/Admin.class.php';

$result = array(
  'roles' => array(),
);
foreach (Admin::$ROLES as $key => $value) {
  $admin = array(
    'id' => $key,
    'admin' => $value,
  );
  $result['roles'][] = $admin;
}

require('../../inc/Template.class.php');
$tpl = new Template('template/add.html');
echo $tpl->render($result);
?>
