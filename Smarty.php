<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'packages/smarty5/vendor/autoload.php';
class Smarty {

  private \Smarty\Smarty $smarty;

  public function __construct() {
    $this->smarty = new Smarty\Smarty();
    $pluginsDirectory = __DIR__ . DIRECTORY_SEPARATOR . 'CRM' . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . 'Smarty'  . DIRECTORY_SEPARATOR . 'plugins';
    $files = scandir($pluginsDirectory);
    foreach ($files as $file) {
      if (str_starts_with($file, '.')) {
        continue;
      }
      if (CRM_Utils_File::isIncludable($pluginsDirectory .DIRECTORY_SEPARATOR . $file)) {
        require_once $pluginsDirectory .DIRECTORY_SEPARATOR . $file;
        $parts = explode('.', $file);
        $this->smarty->registerPlugin($parts[0], $parts[1], 'smarty_' . $parts[0] . '_' . $parts[1]);
      }
    }
    // Smarty5 can't use php functions unless they are registered....
    $functionsForSmarty = [
      'is_numeric',
      // used for permission checks although it might be nicer if it wasn't
      'call_user_func',
      'array_key_exists',
      'strstr',
      'strpos',
    ];
    foreach ($functionsForSmarty as $function) {
      $this->smarty->registerPlugin('modifier', $function, $function);
    }

  }

  public function __call($name, $arguments) {
    return call_user_func_array([$this->smarty, $name], $arguments);
  }

  public function __get($name) {
    // Quick form accesses these in HTML_QuickForm_Renderer_ArraySmarty->_renderRequired()
    if ($name === 'left_delimiter') {
      return $this->smarty->getLeftDelimiter();
    }
    if ($name === 'right_delimiter') {
      return $this->smarty->getRightDelimiter();
    }
    return $this->smarty->$name;
  }

  public function __set($name, $value) {
    $this->smarty->$name = $value;
  }

  /**
   * @throws \Smarty\Exception
   */
  public function loadFilter($type, $name) {
    if ($type === 'pre') {
      $this->smarty->registerFilter($type, 'smarty_prefilter_' . $name);
    }
    else {
      $this->smarty->loadFilter($type, $name);
    }
  }

}
