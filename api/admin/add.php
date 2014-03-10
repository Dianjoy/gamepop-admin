<?php
define('OPTIONS', 'root');
include_once '../../inc/session.php';
?>
<?php
require '../../inc/Admin.class.php';

$result = array(
  'roles' => array(),
);
for($i = 0; $i < sizeof(Admin::$ROLES); $i++) {
  $admin = array(
    'id' => $i,
    'admin' => Admin::$ROLES[$i],
  );
  $result['roles'][] = $admin;
}

require('../../inc/Template.class.php');
$tpl = new Template('template/add.html');
echo $tpl->render($result);
?>
