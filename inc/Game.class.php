<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14-3-11
 * Time: 上午11:24
 */
class Game {
  const TABLE = '`t_game`';
  const MIDDLE = '`m_pack_guide`';
  const APK_INFO = '`apkparser`.`t_info`';

  const NORMAL = 0;
  const DELETED = 1;

  private $DB;


  public function __construct($DB) {
    $this->DB = $DB;
  }

  public function get_all_games($size, $page, $keyword = '', $order_by = 'i.now_use-i.pre_use', $order = 'DESC') {
    $order_by = $order_by ? "ORDER BY $order_by $order" : "";
    $keyword = $this->get_keyword_condition($keyword, 'g.');
    $start = $size * $page;
    $sql = "SELECT g.`guide_name`, `game_name`, `game_desc`, g.`update_time`, i.`icon_path`,
              g.`icon_path` AS `new_icon`
            FROM " . self::MIDDLE . " m JOIN " . self::TABLE . " g ON m.`guide_name`=g.`guide_name`
              JOIN " . self::APK_INFO . " i ON m.`packagename`=i.`packagename`
            WHERE `status`=" . self::NORMAL . " AND i.`is_game`=1 $keyword
            $order_by
            LIMIT $start, $size";
    $sth = $this->DB->query($sql);
    $result = $sth->fetchAll(PDO::FETCH_ASSOC);
    foreach ($result as $key => $row) {
      $result[$key] = $this->get_icon_path($row);
    }
    return $result;
  }

  public function get_game_number($keyword) {
    $keyword = $this->get_keyword_condition($keyword);
    $sql = "SELECT COUNT('X')
            FROM " . self::TABLE . "
            WHERE `status`=" . self::NORMAL . " $keyword";
    return $this->DB->query($sql)->fetchColumn();
  }

  public function get_info($id) {
    $sql = "SELECT g.`guide_name`, `game_name`, `game_desc`, g.`update_time`, i.`icon_path`,
              g.`icon_path` AS `new_icon`
            FROM " . self::MIDDLE . " m JOIN " . self::TABLE . " g ON m.`guide_name`=g.`guide_name`
              JOIN " . self::APK_INFO . " i ON m.`packagename`=i.`packagename`
            WHERE `status`=" . self::NORMAL . " AND i.`is_game`=1 AND g.`guide_name`='$id'";
    $info = $this->DB->query($sql)->fetch(PDO::FETCH_ASSOC);
    $info = $this->get_icon_path($info);
    return $info;
  }

  public function remove($id) {
    $sql = "UPDATE " . self::TABLE ."
            SET `status`=" . self::DELETED . "
            WHERE `guide_name`=:guide_name";
    $sth = $this->DB->prepare($sql);
    return $sth->execute(array(
      ':guide_name' => $id
    ));
  }

  public function update($id, $args) {
    $params = '';
    foreach ($args as $key => $value) {
      $params .= "`$key`=\"$value\",";
    }

    $sql = "UPDATE " . self::TABLE . "
            SET " . substr($params, 0, -1) . "
            WHERE `guide_name`=:guide_name";
    $sth = $this->DB->prepare($sql);
    return $sth->execute(array(
      ':guide_name' => $id,
    ));
  }

  private function get_keyword_condition($keyword, $table = '') {
    return $keyword ? "AND ($table`guide_name` LIKE '%$keyword%' OR `game_name` LIKE '%$keyword%')" : '';
  }
  private function get_icon_path($game) {
    $game['icon_path'] = empty($game['new_icon']) ? (empty($game['icon_path']) ? '' : '//r.yxpopo.com/popoicon' . $game['icon_path']) : $game['new_icon'];
    return $game;
  }
}