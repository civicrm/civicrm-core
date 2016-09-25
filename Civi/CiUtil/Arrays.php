<?php
namespace Civi\CiUtil;

/**
 * Class Arrays
 *
 * @package Civi\CiUtil
 */
class Arrays {
  /**
   * @param $arr
   * @param $col
   *
   * @return array
   */
  public static function collect($arr, $col) {
    $r = array();
    foreach ($arr as $k => $item) {
      $r[$k] = $item[$col];
    }
    return $r;
  }

}
