<?php
define('OPTIONS', 'root');
include_once '../../inc/session.php';
?>
<?php
$DB = include(dirname(__FILE__).'/../../inc/pdo.php');
include_once(dirname(__FILE__) . '/../../inc/Admin.class.php');
$admin = new Admin($DB);
$res = $admin->get_live_admins();

$result = array(
  'admins' => $res,
);

require('../../inc/Template.class.php');
$tpl = new Template('template/list.html');
echo $tpl->render($result);
?>