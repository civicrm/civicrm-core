<?php

class CRM_Utils_Dir {
  static function getcwd() {
    $result = getcwd();
    if ($result === FALSE) {
      throw new Exception("Error getting current working directory: " . print_r(error_get_last(), TRUE));
    }
    return $result;
  }

  static function chdir($directory_path) {
    $result = chdir($directory_path);
    if ($result === FALSE) {
      throw new Exception("Error changing to $directory_path: " . print_r(error_get_last(), TRUE));
    }
    return $result;
  }
}
