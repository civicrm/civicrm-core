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

namespace Civi\Core\Themes;

use Civi;

/**
 *
 * @package CiviCRM_Hook
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class Resolvers {

  /**
   * In the simple format, the CSS file is loaded from the extension's "css" subdir;
   * if it's missing, then it searches the parents.
   *
   * To use an alternate subdir, override "prefix".
   *
   * Simple themes may use the "search_order" to assimilate content from other themes.
   *
   * @param \Civi\Core\Themes $themes
   *   The theming subsystem.
   * @param string $themeKey
   *   The active/desired theme key.
   * @param string $cssExt
   *   The extension for which we want a themed CSS file (e.g. "civicrm").
   * @param string $cssFile
   *   File name (e.g. "css/bootstrap.css").
   * @return array|string
   *   List of CSS URLs, or PASSTHRU.
   */
  public static function simple($themes, $themeKey, $cssExt, $cssFile) {
    $res = Civi::resources();
    $theme = $themes->get($themeKey);
    $file = '';
    if (isset($theme['prefix'])) {
      $file .= $theme['prefix'];
    }
    $file .= $themes->cssId($cssExt, $cssFile);
    $file = $res->filterMinify($theme['ext'], $file);

    if ($res->getPath($theme['ext'], $file)) {
      return [$res->getUrl($theme['ext'], $file, TRUE)];
    }
    else {
      return Civi\Core\Themes::PASSTHRU;
    }
  }

  /**
   * The base handler falls back to loading files from the main application (rather than
   * using the theme).
   *
   * @param \Civi\Core\Themes $themes
   *   The theming subsystem.
   * @param string $themeKey
   *   The active/desired theme key.
   * @param string $cssExt
   *   The extension for which we want a themed CSS file (e.g. "civicrm").
   * @param string $cssFile
   *   File name (e.g. "css/bootstrap.css").
   * @return array|string
   *   List of CSS URLs, or PASSTHRU.
   */
  public static function fallback($themes, $themeKey, $cssExt, $cssFile) {
    $res = Civi::resources();
    return [$res->getUrl($cssExt, $cssFile, TRUE)];
  }

}
