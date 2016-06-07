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

namespace Civi\Core;

use Civi;

/**
 *
 * @package CiviCRM_Hook
 * @copyright CiviCRM LLC (c) 2004-2016
 */
class Theme {

  const DEFAULT_THEME = 'greenwich';

  /**
   * @var string
   *   Ex: 'judy', 'liza'.
   */
  private $activeThemeKey;

  /**
   * @var array
   *   Array(string $themeKey => array $themeSpec).
   */
  private $themes = NULL;

  /**
   * @var \CRM_Utils_Cache_Interface
   */
  private $cache = NULL;

  /**
   * Theme constructor.
   * @param \CRM_Utils_Cache_Interface $cache
   */
  public function __construct($cache = NULL) {
    $this->cache = $cache ? $cache : Civi::cache();;
  }

  /**
   * Determine the name of active theme.
   *
   * @return string
   *   Ex: "greenwich".
   */
  public function getActiveThemeKey() {
    if ($this->activeThemeKey === NULL) {
      // Ambivalent: is it better to use $config->userFrameworkFrontend or $template->get('urlIsPublic')?
      $config = \CRM_Core_Config::singleton();
      $settingKey = $config->userFrameworkFrontend ? 'theme_frontend' : 'theme_backend';

      $themeKey = Civi::settings()->get($settingKey);
      if ($themeKey === 'default') {
        $themeKey = self::DEFAULT_THEME;
      }

      $themes = $this->getAll();
      $this->activeThemeKey = isset($themes[$themeKey]) ? $themeKey : self::DEFAULT_THEME;
    }
    return $this->activeThemeKey;
  }

  /**
   * Get the definition of the active theme.
   *
   * @return array
   * @see CRM_Utils_Hook::themes
   */
  public function getActive() {
    $all = $this->getAll();
    $themeKey = $this->getActiveThemeKey();
    return isset($all[$themeKey]) ? $all[$themeKey] : NULL;
  }

  /**
   * Get a list of available themes.
   *
   * @return array
   *   List of themes, keyed by name. Same format as CRM_Utils_Hook::themes(),
   *   but any default values are filled in.
   * @see CRM_Utils_Hook::themes
   */
  public function getAll() {
    if ($this->themes === NULL) {
      // Cache includes URLs/paths, which change with runtime.
      $cacheKey = 'theme_list_' . \CRM_Core_Config_Runtime::getId();
      $this->themes = $this->cache->get($cacheKey);
      if ($this->themes === NULL) {
        $this->themes = $this->buildAll();
        $this->cache->set($cacheKey, $this->themes);
      }
    }
    return $this->themes;
  }

  /**
   * Get the URL(s) for a themed CSS file.
   *
   * This implements a prioritized search, in order:
   *  - Check for the specified theme.
   *  - If that doesn't exist, check for the default theme.
   *  - If that doesn't exist, use the 'none' theme.
   *
   * @param string $file
   *   Ex: 'css/bootstrap.css' or 'css/civicrm.css'.
   * @return array
   *   List of URLs to display.
   *   Ex: array(string $url)
   */
  public function getUrls($file) {
    $theme = $this->getActive();
    if (!$theme) {
      return array();
    }

    return Civi\Core\Resolver::singleton()
      ->call($theme['url_callback'], array($theme, $file));
  }

  /**
   * Construct the list of available themes.
   *
   * @return array
   *   List of themes, keyed by name.
   * @see CRM_Utils_Hook::themes
   */
  protected function buildAll() {
    $themes = array(
      'default' => array(
        'ext' => 'civicrm',
        'title' => 'System Default',
        'help' => ts('Determine a system default automatically'),
        'url_callback' => '\Civi\Core\Theme\Formats::none',
      ),
      'greenwich' => array(
        'ext' => 'civicrm',
        'title' => 'Greenwich',
        'help' => ts('CiviCRM 4.x look-and-feel'),
        'url_callback' => '\Civi\Core\Theme\Formats::hierarchical',
      ),
      'none' => array(
        'ext' => 'civicrm',
        'title' => 'Empty Theme',
        'help' => ts('Disable CiviCRM built-in CSS theming.'),
        'url_callback' => '\Civi\Core\Theme\Formats::none',
      ),
    );

    \CRM_Utils_Hook::themes($themes);

    foreach (array_keys($themes) as $themeKey) {
      $themes[$themeKey] = $this->build($themeKey, $themes[$themeKey]);
    }

    return $themes;
  }

  /**
   * Apply defaults for a given them.
   *
   * @param string $themeKey
   *   The name of the theme. Ex: 'greenwich'.
   * @param array $theme
   *   The original theme definition of the theme (per CRM_Utils_Hook::themes).
   * @return array
   *   The full theme definition of the theme (per CRM_Utils_Hook::themes).
   * @see CRM_Utils_Hook::themes
   */
  protected function build($themeKey, $theme) {
    $defaults = array(
      'name' => $themeKey,
      'url_callback' => '\Civi\Core\Theme\Formats::hierarchical',
      'extends' => array(self::DEFAULT_THEME),
      'base_dir' => Civi::resources()->getPath($theme['ext']),
      'base_url' => Civi::resources()->getUrl($theme['ext']),
    );
    return array_merge($defaults, $theme);
  }

}
