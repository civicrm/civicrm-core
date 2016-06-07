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

/**
 *
 * @package CiviCRM_Hook
 * @copyright CiviCRM LLC (c) 2004-2016
 */
class CRM_Core_Theme {

  const DEFAULT_THEME = 'classic';

  /**
   * Get a list of available themes.
   *
   * @return array
   *   List of themes, keyed by name. Each theme has properties:
   *      - ext: The extension name.
   *      - title: string.
   *      - subdir: The subdir within the extension that contains the CSS.
   *      - css_callback: (Optional) Function to compute the CSS URLs.
   *   Ex:
   *   array('judy' => array(
   *     'ext' => 'com.paramount.judy',
   *     'title' => 'The Judy Theme',
   *     'subdir' => 'css/',
   *     'css_callback' => function($themeKey, $cssKey) {...},
   *   )).
   */
  public static function getThemes() {
    if (!isset(Civi::$statics[__CLASS__]['themes'])) {
      Civi::$statics[__CLASS__]['themes'] = array(
        'none' => array(
          'title' => 'No theming',
        ),
      );
      CRM_Utils_Hook::themes(Civi::$statics[__CLASS__]['themes']);

      $defaults = array(
        'subdir' => 'css/',
      );

      foreach (array_keys(Civi::$statics[__CLASS__]['themes']) as $themeKey) {
        Civi::$statics[__CLASS__]['themes'][$themeKey] = array_merge(
          $defaults,
          Civi::$statics[__CLASS__]['themes'][$themeKey]
        );
      }

    }
    return Civi::$statics[__CLASS__]['themes'];
  }

  /**
   * Get the URL(s) for a themed CSS file.
   *
   * This implements a prioritized search, in order:
   *  - Check for the specified theme.
   *  - If that doesn't exist, check for the default theme.
   *  - If that doesn't exist, use the 'none' theme.
   *
   * @param string $themeKey
   *   Ex: 'judy'
   * @param string $cssKey
   *   Ex: 'bootstrap.css' or 'civicrm.css'.
   * @return array
   *   List of URLs to display.
   *   Ex: array(string $url)
   */
  public static function getCssUrls($themeKey, $cssKey) {
    if ($themeKey === 'default') {
      $themeKey = self::DEFAULT_THEME;
    }
    if ($themeKey === 'none') {
      return array();
    }

    $themes = self::getThemes();
    if (!isset($themes[$themeKey]) || !isset($themes[$themeKey]['ext'])) {
      if (isset($themes[self::DEFAULT_THEME])) {
        $themeKey = self::DEFAULT_THEME;
      }
      else {
        return array();
      }
    }

    $theme = $themes[$themeKey];

    if (isset($theme['css_callback'])) {
      return Civi\Core\Resolver::singleton()->call($theme['css_callback'], array(
        $themeKey,
        $cssKey,
      ));
    }

    $prefix = empty($theme['subdir']) ? '' : CRM_Utils_File::addTrailingSlash($theme['subdir'], '/');

    return array(
      Civi::resources()->getUrl($theme['ext'], $prefix . $cssKey),
    );
  }

}
