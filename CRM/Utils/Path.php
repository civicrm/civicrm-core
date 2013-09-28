<?php

class CRM_Utils_Path {
  static function join() {
    $path_parts = array();
    $args = func_get_args();
    foreach ($args as $arg) {
      if (is_array($arg)) {
        $path_parts = array_merge($path_parts, $arg);
      } else {
        $path_parts[] = $arg;
      }
    }
    return implode(DIRECTORY_SEPARATOR, $path_parts);
  }

  static function mkdir_p_if_not_exists($path) {
    if (file_exists($path)) {
      if (!is_dir($path)) {
        throw new Exception("Trying to make a directory at '$path', but there is already a file there with the same name.");
      }
    } else {
      $result = @mkdir($path, 0777, TRUE);
      if ($result === FALSE) {
        throw new Exception("Error trying to create directory '$path': " . print_r(error_get_last(), TRUE));
      }
    }
  }
}
