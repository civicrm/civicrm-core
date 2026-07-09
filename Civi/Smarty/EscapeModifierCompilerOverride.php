<?php

namespace Civi\Smarty;

use Smarty\Compile\Modifier\EscapeModifierCompiler;

/**
 * Smarty escape modifier plugin
 * Type:     modifier
 * Name:     escape
 * Purpose:  escape string for output
 *
 * @author Rodney Rehm
 */
class EscapeModifierCompilerOverride extends EscapeModifierCompiler {

  public function compile($params, \Smarty\Compiler\Template $compiler) {
    $esc_type = $this->literal_compiler_param($params, 1, 'html');
    if ($esc_type !== 'htmlall'
    ) {
      return parent::compile($params, $compiler);
    }
    return '\CRM_Core_Smarty::escape((string)' . $params[0] . ', "htmlall")';
  }

}
