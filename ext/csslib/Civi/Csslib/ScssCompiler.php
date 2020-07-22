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
namespace Civi\Csslib;

use Padaliyajay\PHPAutoprefixer\Autoprefixer;
use ScssPhp\ScssPhp\Compiler;
use Symfony\Component\Process\Process;

class ScssCompiler {

  public function compile($content, $includeDirs = []) {
    $scss = new Compiler();
    foreach ($includeDirs as $includeDir) {
      $scss->addImportPath($includeDir);
    }

    switch (\Civi::settings()->get('csslib_srcmap')) {
      case 'inline':
        $scss->setSourceMap(Compiler::SOURCE_MAP_INLINE);
        break;

      case 'none':
      default:
        // Nothing needed.
        break;
    }

    $content = $scss->compile($content);

    switch (\Civi::settings()->get('csslib_autoprefixer')) {
      case 'php-autoprefixer':
        $autoprefixer = new Autoprefixer($content);
        $content = $autoprefixer->compile();
        break;

      case 'autoprefixer-cli':
        $p = new Process('autoprefixer-cli');
        $p->setInput($content);
        $p->setTimeout(120);
        $p->run();
        if ($p->isSuccessful()) {
          $content = $p->getOutput();
        }
        else {
          throw new \CRM_Core_Exception("Failed to invoke the NodeJS autoprefixer via CLI.");
        }
        break;

      case 'none':
      default:
        // Nothing needed.
        break;
    }

    return $content;
  }

}
