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

namespace Civi\Core;

use Civi;

/**
 *
 * @package CiviCRM_Hook
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 * @service themes
 */
class Themes extends \Civi\Core\Service\AutoService {

  /**
   * The "default" theme adapts based on the latest recommendation from civicrm.org
   * by switching to DEFAULT_THEME at runtime.
   */
  const DEFAULT_THEME = 'greenwich';

  /**
   * Fallback is a pseudotheme which can be included in "search_order".
   * It locates files in the core/extension (non-theme) codebase.
   */
  const FALLBACK_THEME = '_fallback_';

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
    $this->cache = $cache ?: Civi::cache('long');
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
      $settingKey = $config->userSystem->isFrontEndPage() ? 'theme_frontend' : 'theme_backend';

      $themeKey = Civi::settings()->get($settingKey);
      if ($themeKey === 'default') {
        $themeKey = self::DEFAULT_THEME;
      }

      \CRM_Utils_Hook::activeTheme($themeKey, [
        'themes' => $this,
        'page' => \CRM_Utils_System::currentPath(),
      ]);

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
    return $all[$themeKey] ?? NULL;
  }

  /**
   * Get a list of all known themes, including hidden base themes.
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
   * Get a list of available themes, excluding hidden base themes.
   *
   * This is the same as getAll(), but abstract themes like "_fallback_"
   * or "_newyork_base_" are omitted.
   *
   * @return array
   *   List of themes.
   *   Ex: ['greenwich' => 'Greenwich', 'shoreditch' => 'Shoreditch'].
   * @see CRM_Utils_Hook::themes
   */
  public function getAvailable() {
    $result = [];
    foreach ($this->getAll() as $key => $theme) {
      if ($key[0] !== '_') {
        $result[$key] = $theme['title'];
      }
    }
    return $result;
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
      return [];
    }

    $cssId = $this->cssId($cssExt, $cssFile);

    foreach ($all[$active]['search_order'] as $themeKey) {
      if (isset($all[$themeKey]['excludes']) && in_array($cssId, $all[$themeKey]['excludes'])) {
        $result = [];
      }
      else {
        $result = Civi\Core\Resolver::singleton()
          ->call($all[$themeKey]['url_callback'], [$this, $themeKey, $cssExt, $cssFile]);
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
    $themes = [
      'default' => [
        'ext' => 'civicrm',
        'title' => ts('Automatic'),
        'help' => ts('Determine a system default automatically'),
        // This is an alias. url_callback, search_order don't matter.
      ],
      'greenwich' => [
        'ext' => 'civicrm',
        'title' => 'Greenwich',
        'help' => ts('CiviCRM 4.x look-and-feel'),
      ],
      'none' => [
        'ext' => 'civicrm',
        'title' => ts('None (Unstyled)'),
        'help' => ts('Disable CiviCRM\'s built-in CSS files.'),
        'search_order' => ['none', self::FALLBACK_THEME],
        'excludes' => [
          "css/civicrm.css",
          "css/bootstrap.css",
        ],
      ],
      self::FALLBACK_THEME => [
        'ext' => 'civicrm',
        'title' => 'Fallback (Abstract Base Theme)',
        'url_callback' => '\Civi\Core\Themes\Resolvers::fallback',
        'search_order' => [self::FALLBACK_THEME],
      ],
    ];

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
    $defaults = [
      'name' => $themeKey,
      'url_callback' => '\Civi\Core\Themes\Resolvers::simple',
      'search_order' => [$themeKey, self::FALLBACK_THEME],
    ];
    $theme = array_merge($defaults, $theme);

    return $theme;
  }

  /**
   * @param string $cssExt
   * @param string $cssFile
   * @return string
   */
  public function cssId($cssExt, $cssFile) {
    return ($cssExt === 'civicrm') ? $cssFile : "$cssExt-$cssFile";
  }

}
