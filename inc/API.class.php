<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14-5-6
 * Time: 下午12:58
 */

class API {
  public function __construct($auth, $handlers) {
    define('OPTIONS', $auth);
    require_once '../../inc/session.php';

    $args = $_REQUEST;
    // 对backbone开放的接口，传进来的数据多是json对象
    $request = file_get_contents('php://input');
    if ($request) {
      $attr = json_decode($request, true);
    }

    header("Content-Type:application/json;charset=utf-8");
    switch ($_SERVER['REQUEST_METHOD']) {
      case 'GET':
        if (empty($handlers['fetch'])) {
          header("HTTP/1.1 406 Not Acceptable");
        }
        $handlers['fetch']($args, $attr);
        break;

      case 'PATCH':
        if (empty($handlers['update'])) {
          header("HTTP/1.1 406 Not Acceptable");
        }
        $handlers['update']($args, $attr);
        break;

      case 'DELETE':
        if (empty($handlers['delete'])) {
          header("HTTP/1.1 406 Not Acceptable");
        }
        $handlers['delete']($args, $attr);
        break;

      case 'POST':
        if (empty($handlers['create'])) {
          header("HTTP/1.1 406 Not Acceptable");
        }
        $handlers['create']($args, $attr);
        break;

      default:
        header("HTTP/1.1 406 Not Acceptable");
        break;
    }
  }
} 