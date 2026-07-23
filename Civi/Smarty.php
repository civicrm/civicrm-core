<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

namespace Civi;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class Smarty {

  private \Smarty\Smarty $smarty;

  private $registeredPluginDirectories = [];

  public function __construct() {
    $this->smarty = new \Smarty\Smarty();
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

  /**
   * @param null|string|array $pluginsDirectories
   *
   * @return void
   * @throws \Smarty\Exception
   */
  public function addPluginsDir($pluginsDirectories): void {
    foreach ((array) $pluginsDirectories as $pluginsDirectory) {
      if (in_array($pluginsDirectory, $this->registeredPluginDirectories, TRUE)) {
        continue;
      }
      $files = scandir($pluginsDirectory);
      foreach ($files as $file) {
        if (str_starts_with($file, '.')) {
          continue;
        }
        $registeredPlugins = $this->smarty->registered_plugins;
        if (\CRM_Utils_File::isIncludable($pluginsDirectory . DIRECTORY_SEPARATOR . $file)) {
          require_once $pluginsDirectory . DIRECTORY_SEPARATOR . $file;
          $parts = explode('.', $file);
          if (!empty($registeredPlugins[$parts[0]][$parts[1]])) {
            continue;
          }
          $this->smarty->registerPlugin($parts[0], $parts[1], 'smarty_' . $parts[0] . '_' . $parts[1]);
        }
      }
      $this->registeredPluginDirectories[] = $pluginsDirectory;
    }
  }

  public function getVersion(): ?int {
    return 5;
  }

}
