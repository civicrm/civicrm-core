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
 * Compiler plugin for opening {ts ...} tags.
 *
 * Emits ob_start() and pushes the tag's attributes onto the compiler
 * tag stack so the closing tag can retrieve them.
 */
class smarty_compiler_ts extends \Smarty\Compile\Base {

  protected $optional_attributes = ['_any'];

  protected $option_flags = ['nocache', 'nofilter'];

  public function compile($args, \Smarty\Compiler\Template $compiler, $parameter = [], $tag = NULL, $function = NULL): string {
    $_attr = $this->getAttributes($compiler, $args);

    // Save attributes and the current nofilter flag for the closing tag.
    $compiler->openTag('ts', [
      'attr' => $_attr,
      'nofilter' => (bool) ($_attr['nofilter'] ?? FALSE),
    ]);

    return '<?php ob_start(); ?>';
  }

}
