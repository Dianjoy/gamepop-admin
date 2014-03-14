<?php

/**
 * Created by JetBrains PhpStorm.
 * User: HUI
 * Date: 13-3-26
 * Time: 下午11:04
 * To change this template use File | Settings | File Templates.
 */
class upload {
  public static function insert($DB, $id, $type, $new_path, $upload_user, $file_name) {
    $now = date('Y-m-d H:i:s');
    $sql = "INSERT INTO t_upload_log
            (`id`, `type`, `url`, `upload_user`, `upload_time`, `file_name`)
            VALUE ('$id', '$type', '$new_path', '$upload_user', '$now', '$file_name')";
    $DB->query($sql);
  }

  public static function get_file_name($DB, $id) {
    $sql = "SELECT file_name
            FROM t_upload_log
            WHERE id='$id' AND type='ad_url'
            ORDER BY upload_time DESC
            LIMIT 1";
    return $DB->query($sql)->fecthColumn();
  }
}
