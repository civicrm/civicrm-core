<?php

namespace Civi\Smarty;

use Smarty\Extension\Base;

class EscapeOverrideExtension extends Base {

  public function getModifierCompiler(string $modifier): ?\Smarty\Compile\Modifier\ModifierCompilerInterface {
    switch ($modifier) {
      case 'escape':
        return new EscapeModifierCompilerOverride();

    }

    return NULL;
  }

}
