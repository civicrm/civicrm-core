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
class Themes {

  /**
   * The "default" theme adapts based on the latest recommendation from civicrm.org
   * by switching to DEFAULT_THEME at runtime.
   */
  const DEFAULT_THEME = 'greenwich';

  /**
   * Fallback is a pseudotheme which can be included in "search_order".
   * It locates files in the core/extension (non-theme) codebase.
   */
  const FALLBACK_THEME = '*fallback*';

  const PASSTHRU = 'PASSTHRU';

  /**
   * @var string
   *   Ex: 'judy', 'liza'.
   */
  private $activeThemeKey = NULL;

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
   * Get the definition of the theme.
   *
   * @param string $themeKey
   *   Ex: 'greenwich', 'shoreditch'.
   * @return array|NULL
   * @see CRM_Utils_Hook::themes
   */
  public function get($themeKey) {
    $all = $this->getAll();
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
   * @param string $active
   *   Active theme key.
   *   Ex: 'greenwich'.
   * @param string $cssExt
   *   Ex: 'civicrm'.
   * @param string $cssFile
   *   Ex: 'css/bootstrap.css' or 'css/civicrm.css'.
   * @return array
   *   List of URLs to display.
   *   Ex: array(string $url)
   */
  public function resolveUrls($active, $cssExt, $cssFile) {
    $all = $this->getAll();
    if (!isset($all[$active])) {
      return array();
    }

    $cssId = "$cssExt:$cssFile";

    foreach ($all[$active]['search_order'] as $themeKey) {
      if ($themeKey === self::FALLBACK_THEME) {
        $result = Civi\Core\Themes\Resolvers::fallback($this, $themeKey, $cssExt, $cssFile);
      }
      elseif (isset($all[$themeKey]['excludes']) && in_array($cssId, $all[$themeKey]['excludes'])) {
        $result = array();
      }
      else {
        $result = Civi\Core\Resolver::singleton()
          ->call($all[$active]['url_callback'], array($this, $themeKey, $cssExt, $cssFile));
      }

      if ($result !== self::PASSTHRU) {
        return $result;
      }
    }

    throw new \RuntimeException("Failed to resolve URL. Theme metadata may be incomplete.");
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
        // This is an alias. url_callback, search_order don't matter.
      ),
      'greenwich' => array(
        'ext' => 'civicrm',
        'title' => 'Greenwich',
        'help' => ts('CiviCRM 4.x look-and-feel'),
      ),
      'none' => array(
        'ext' => 'civicrm',
        'title' => 'Empty Theme',
        'help' => ts('Disable CiviCRM built-in CSS libraries.'),
        'search_order' => array('none', self::FALLBACK_THEME),
        'excludes' => array(
          "civicrm:css/civicrm.css",
          "civicrm:css/bootstrap.css",
        ),
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
      'url_callback' => '\Civi\Core\Themes\Resolvers::simple',
      'search_order' => array($themeKey, self::FALLBACK_THEME),
    );
    $theme = array_merge($defaults, $theme);

    return $theme;
  }

}
