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

class Options {

  /**
   * Options list for "csslib_autoprefixer".
   *
   * @return array
   */
  public static function autoprefixers() {
    return [
      'none' => ts('None'),

      // PRO: Built-in, no extra dependencies.
      'php-autoprefixer' => ts('PHP Autoprefixer'),

      // PRO: Widely used, more actively maintained. Seems to update sourcemap while filtering.
      'autoprefixer-cli' => ts('Node Autoprefixer'),
    ];
  }

  /**
   * Options list for "csslib_srcmap".
   *
   * @return array
   */
  public static function srcmaps() {
    return [
      'none' => ts('None'),
      'inline' => ts('Inline'),
    ];
  }

}
