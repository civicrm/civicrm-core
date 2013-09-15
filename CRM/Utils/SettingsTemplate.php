<?php

class CRM_Utils_SettingsTemplate {
  public $template_path;
  public $template_values;

  function __construct($base_path, $template_values) {
    $this->template_path = CRM_Utils_Path::join($base_path, 'templates', 'CRM', 'common', 'civicrm.settings.php.template');
    $this->template_values = $template_values;
  }

  function install($target_path) {
    $template_file = CRM_Utils_File::open($this->template_path, 'r');
    $file_size = filesize($this->template_path);
    if ($file_size === FALSE) {
      throw new Exception("Couldn't get size of template file '{$this->template_path}': " . print_r(error_get_last(), TRUE));
    }
    $template_contents = CRM_Utils_File::read($template_file, $file_size);
    CRM_Utils_File::close($template_file);
    foreach ($this->template_values as $key => $value) {
      $template_contents = str_replace('%%' . $key . '%%', $value, $template_contents);
    }
    $target_file = CRM_Utils_File::open($target_path, 'w');
    CRM_Utils_File::write($target_file, $template_contents);
    CRM_Utils_File::close($target_file);
  }
}
