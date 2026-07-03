<?php

use Smarty\Extension\Base;

class CRM_Core_Smarty_EscapeOverrideExtension extends Base {

  public function getModifierCompiler(string $modifier): ?\Smarty\Compile\Modifier\ModifierCompilerInterface {
    switch ($modifier) {
      case 'escape':
        return new CRM_Core_Smarty_EscapeModifierCompilerOverride();

    }

    return NULL;
  }

}
