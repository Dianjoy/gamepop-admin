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

  public function get_all_games($size, $page, $order_by = 'i.now_use-i.pre_use', $order = 'DESC') {
    $order_by = $order_by ? "ORDER BY $order_by $order" : "";
    $start = $size * $page;
    $sql = "SELECT g.`guide_name`, `game_name`, `game_desc`, g.`update_time`, `icon_path`
            FROM " . self::MIDDLE . " m JOIN " . self::TABLE . " g ON m.`guide_name`=g.`guide_name`
              JOIN " . self::APK_INFO . " i ON m.`packagename`=i.`packagename`
            WHERE `status`=" . self::NORMAL . " AND i.`is_game`=1
            $order_by
            LIMIT $start, $size";
    $sth = $this->DB->query($sql);
    return $sth->fetchAll(PDO::FETCH_ASSOC);
  }

  public function get_game_number() {
    $sql = "SELECT COUNT('X')
            FROM " . self::TABLE . "
            WHERE `status`=" . self::NORMAL;
    return $this->DB->query($sql)->fetchColumn();
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
}