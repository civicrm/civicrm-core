<?php

namespace Civi\Smarty;

use Smarty\Extension\Base;

class Extension extends Base {

  public function getModifierCallback(string $modifierName) {
    global $civicrm_root;
    $pluginsDirectory = $civicrm_root . DIRECTORY_SEPARATOR . 'CRM' . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . 'Smarty'  . DIRECTORY_SEPARATOR . 'plugins';
    if (\CRM_Utils_File::isIncludable($pluginsDirectory .DIRECTORY_SEPARATOR . 'modifier.' . $modifierName . '.php')) {
      require_once $pluginsDirectory .DIRECTORY_SEPARATOR . 'modifier.' . $modifierName . '.php';
      return 'smarty_modifier_' . $modifierName;
    }
    return null;
  }

}
