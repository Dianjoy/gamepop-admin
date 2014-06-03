<?php
/**
 * Created by PhpStorm.
 * Date: 14-4-1
 * Time: 上午8:50
 * @overview 统一内容输出类
 * @author Meatill <lujia.zhai@dianjoy.com>
 * @since 
 */

class Spokesman {
  public static function say($args) {
    // 判断下是否需要给图片加绝对路径
    if (isset($args['list']) && is_array($args['list'])) {
      foreach ($args['list'] as $key => $item) {
        $args['list'][$key] = self::checkImageUrl($item);
      }
    }
    $args = self::checkImageUrl($args);
    header("Content-Type:application/json;charset=UTF-8");
    exit(json_encode($args));
  }

  public static function judge($result, $success, $error, $args = null) {
    header("Content-Type:application/json;charset=UTF-8");
    if ($result) {
      echo json_encode(array_merge(array(
        'code' => 0,
        'msg' => $success,
      ), (array)$args));
    } else {
      header("HTTP/1.1 400 Bad Request");
      echo json_encode(array_merge(array(
        'code' => 1,
        'msg' => $error,
      ), $args));
    }
  }

  /**
   * @param bool $is_game 是否读游戏相关，游戏的id是guide_name
   * @return array
   */
  public static function extract($is_game = false) {
    $param = array();
    $url = $_SERVER['PATH_INFO'];
    $arr = array_values(array_filter(explode('/', $url)));
    foreach ($arr as $key => $value) {
      preg_match('/^(category|game|author)(\w+)$/', $value, $matches);
      if (count($matches) > 0) {
        $param[$matches[1] === 'game' ? 'guide_name' : $matches[1]] = $matches[2];
      } else {
        $param[$is_game && $key === 0 ? 'guide_name' : 'id'] = $value;
      }
    }
    return $param;
  }

  public static function toHTML($data, $tpl) {
    require_once "mustache.php";
    $mustache = new Mustache_Engine(array('cache' => '/var/tmp'));
    $html = file_get_contents($tpl);

    header("Content-type: text/html; charset=UTF-8");
    $html = $mustache->render($html, $data);
    $html = preg_replace('/(http:\/\/r\.yxpopo\.com\/)+/', "\$1", $html);
    echo $html;
  }

  private static function checkImageUrl($item) {
    if (isset($item['image']) && !file_exists('../../' . $item['image'])) {
      $item['image'] = self::addDomain($item['image']);
    }
    if (isset($item['icon_path']) && !file_exists('../../' . $item['icon_path'])) {
      $item['icon_path'] = self::addDomain($item['icon_path']);
    }
    return $item;
  }
  private static function addDomain($url) {
    $prefix = 'http://r.yxpopo.com/';
    return strpos($url, $prefix) === false ? $prefix . $url : $url;
  }
} 