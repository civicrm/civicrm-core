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

/**
 * Compiler plugin for closing {/ts} tags.
 *
 * Retrieves the buffered text, emits the _ts() call, and applies the same
 * default-modifier / escape_html logic that {$variable} output receives
 * via PrintExpressionCompiler.
 */
class smarty_compiler_tsclose extends \Smarty\Compile\Base {

  public function compile($args, \Smarty\Compiler\Template $compiler, $parameter = [], $tag = NULL, $function = NULL): string {
    $saved = $compiler->closeTag('ts');
    $_attr = $saved['attr'];

    // 'escape' parameter is forwarded to _ts() only if it is a custom CiviCRM mode.
    $explicitEscape = isset($_attr['escape']) ? trim($_attr['escape'], '\'\"') : NULL;
    $civiEscapes = ['sql', 'js', 'html', 'htmlattribute'];

    $compilerOnlyKeys = ['nofilter', 'nocache'];
    if ($explicitEscape === NULL || !in_array($explicitEscape, $civiEscapes, TRUE)) {
      $compilerOnlyKeys[] = 'escape';
    }

    $runtimeParams = [];
    foreach (array_diff_key($_attr, array_flip($compilerOnlyKeys)) as $k => $v) {
      $runtimeParams[] = var_export($k, TRUE) . ' => ' . $v;
    }

    $output = '_ts(ob_get_clean(), array(' . implode(', ', $runtimeParams) . '))';

    if ($saved['nofilter']) {
      // Skip all escaping
    }
    elseif ($explicitEscape !== NULL) {
      if (!in_array($explicitEscape, $civiEscapes, TRUE)) {
        $output = $compiler->compileModifier([['escape', var_export($explicitEscape, TRUE)]], $output);
      }
      $compiler->setRawOutput(TRUE);
    }
    else {
      // Apply template-wide filters/modifiers and escape_html
      if ($compiler->getSmarty()->getDefaultModifiers()) {
        $modifierlist = [];
        foreach ($compiler->getSmarty()->getDefaultModifiers() as $key => $modifier) {
          preg_match_all('/(\' [^\'\\\\]* (?: \\\\. [^\'\\\\]* )* \' | " [^"\\\\]* (?: \\\\. [^"\\\\]* )* " | : | [^:]+)/x', $modifier, $matches);
          $modifierlist[$key] = array_values(array_filter($matches[0], fn($m) => $m !== ':'));
        }
        $output = $compiler->compileModifier($modifierlist, $output);
      }

      if ($compiler->getTemplate()->getSmarty()->escape_html && !$compiler->isRawOutput()) {
        $output = "htmlspecialchars((string) ($output), ENT_QUOTES, '" . addslashes(\Smarty\Smarty::$_CHARSET) . "')";
      }
    }

    $compiler->setRawOutput(FALSE);
    return "<?php echo $output; ?>\n";
  }

}
