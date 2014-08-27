<?php
namespace Civi\CiUtil;

class Arrays {
  static function collect($arr, $col) {
    $r = array();
    foreach ($arr as $k => $item) {
      $r[$k] = $item[$col];
    }
    return $r;
  }
}