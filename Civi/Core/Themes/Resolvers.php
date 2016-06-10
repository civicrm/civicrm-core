<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

namespace Civi\Core\Themes;

use Civi;

/**
 *
 * @package CiviCRM_Hook
 * @copyright CiviCRM LLC (c) 2004-2016
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
    if ($cssExt !== 'civicrm') {
      $file .= $cssExt . '-';
    }
    $file .= $cssFile;
    $file = $res->filterMinify($theme['ext'], $file);

    if ($res->getPath($theme['ext'], $file)) {
      return array($res->getUrl($theme['ext'], $file, TRUE));
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
    return array($res->getUrl($cssExt, $cssFile, TRUE));
  }

}
