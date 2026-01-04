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
 * Define some common, global lists of resources.
 */
class CRM_Core_Resources_Common {

  const REGION = 'html-header';

  /**
   * Create a "basic" (generic) bundle.
   *
   * The bundle goes through some lifecycle events (like `hook_alterBundle`).
   *
   * To define default content for a basic bundle, you may either give an
   * `$init` function or subscribe to `hook_alterBundle`.
   *
   * @param string $name
   *   Symbolic name of the bundle.
   * @param callable|null $init
   *   Optional initialization function. Populate default resources.
   *   Signature: `function($bundle): void`
   *   Example: `function myinit($b) { $b->addScriptFile(...)->addStyleFile(...); }`
   * @param string|string[] $types
   *   List of resource-types to permit in this bundle. NULL for a default list.
   *   Example: ['styleFile', 'styleUrl']
   *   The following aliases are allowed: '*all*', '*default*', '*script*', '*style*'
   * @return CRM_Core_Resources_Bundle
   */
  public static function createBasicBundle($name, $init = NULL, $types = NULL) {
    $bundle = new CRM_Core_Resources_Bundle($name, $types);
    if ($init !== NULL) {
      $init($bundle);
    }
    CRM_Utils_Hook::alterBundle($bundle);
    $bundle->fillDefaults();
    return $bundle;
  }

  /**
   * The 'bundle.bootstrap3' service is a collection of resources which are
   * loaded when a page needs to support Boostrap CSS v3.
   *
   * @param string $name
   *   i.e. 'bootstrap3'
   * @return \CRM_Core_Resources_Bundle
   */
  public static function createBootstrap3Bundle($name) {
    $bundle = new CRM_Core_Resources_Bundle($name, ['script', 'scriptFile', 'scriptUrl', 'settings', 'style', 'styleFile', 'styleUrl', 'markup']);
    // Leave it to the theme/provider to register specific resources.
    // $bundle->addStyleFile('civicrm', 'css/bootstrap3.css');
    // $bundle->addScriptFile('civicrm', 'js/bootstrap3.js', [
    //  'translate' => FALSE,
    //]);

    //  This warning will show if bootstrap is unavailable. Normally it will be hidden by the bootstrap .collapse class.
    $bundle->addMarkup('
      <div id="bootstrap-theme">
        <div class="messages warning no-popup collapse">
          <p>
            <i class="crm-i fa-exclamation-triangle" role="img" aria-hidden="true"></i>
            <strong>' . ts('Bootstrap theme not found.') . '</strong>
          </p>
          <p>' . ts('This screen may not work correctly without a bootstrap-based theme such as Shoreditch installed.') . '</p>
        </div>
      </div>',
      ['region' => 'page-header']
    );

    CRM_Utils_Hook::alterBundle($bundle);
    $bundle->fillDefaults();
    return $bundle;
  }

  /**
   * The 'bundle.coreStyles' service is a collection of resources used on some
   * non-Civi pages (wherein Civi may be mixed-in).
   *
   * @param string $name
   *   i.e. 'coreStyles'
   * @return \CRM_Core_Resources_Bundle
   * @see \Civi\Core\Container::createContainer()
   */
  public static function createStyleBundle($name) {
    $bundle = new CRM_Core_Resources_Bundle($name);

    // Load custom or core css
    $config = CRM_Core_Config::singleton();
    if (!empty($config->customCSSURL)) {
      $customCSSURL = Civi::resources()->addCacheCode($config->customCSSURL);
      $bundle->addStyleUrl($customCSSURL, ['weight' => 99, 'name' => 'civicrm:css/custom.css']);
    }
    if (!Civi::settings()->get('disable_core_css')) {
      $bundle->addStyleFile('civicrm', 'css/civicrm.css', -99);
    }
    // crm-i.css added ahead of other styles so it can be overridden by FA.
    $bundle->addStyleFile('civicrm', 'css/crm-i.css', -101);

    CRM_Utils_Hook::alterBundle($bundle);
    $bundle->fillDefaults();
    return $bundle;
  }

  /**
   * The 'bundle.coreResources' service is a collection of resources
   * shared by Civi pages (ie pages where Civi controls rendering).
   *
   * @param string $name
   *   i.e. 'coreResources'
   * @return \CRM_Core_Resources_Bundle
   * @see \Civi\Core\Container::createContainer()
   */
  public static function createFullBundle($name) {
    $bundle = new CRM_Core_Resources_Bundle($name);
    $config = CRM_Core_Config::singleton();

    // Add resources from coreResourceList
    $jsWeight = -9999;
    foreach (self::coreResourceList(self::REGION) as $item) {
      if (is_array($item)) {
        $bundle->addSetting($item);
      }
      elseif (preg_match('/(\.css$)|(\.css[?&])/', $item)) {
        Civi::resources()->isFullyFormedUrl($item) ? $bundle->addStyleUrl($item, -100) : $bundle->addStyleFile('civicrm', $item, -100);
      }
      elseif (Civi::resources()->isFullyFormedUrl($item)) {
        $bundle->addScriptUrl($item, $jsWeight++);
      }
      else {
        // Don't bother  looking for ts() calls in packages, there aren't any
        $translate = (substr($item, 0, 3) == 'js/');
        $bundle->addScriptFile('civicrm', $item, [
          'weight' => $jsWeight++,
          'translate' => $translate,
        ]);
      }
    }
    // Add global settings
    $settings = [
      'config' => [
        'isFrontend' => \CRM_Utils_System::isFrontEndPage(),
        'includeWildCardInName' => $config->includeWildCardInName,
      ],
    ];
    // Disable profile creation if user lacks permission
    if (!CRM_Core_Permission::check('edit all contacts') && !CRM_Core_Permission::check('add contacts')) {
      $settings['config']['entityRef']['contactCreate'] = FALSE;
    }
    $bundle->addSetting($settings);

    // Give control of jQuery and _ back to the CMS - this loads last
    $bundle->addScriptFile('civicrm', 'js/noconflict.js', [
      'weight' => 9999,
      'translate' => FALSE,
    ]);

    CRM_Utils_Hook::alterBundle($bundle);
    $bundle->fillDefaults();
    return $bundle;
  }

  /**
   * List of core resources we add to every CiviCRM page.
   *
   * Note: non-compressed versions of .min files will be used in debug mode
   *
   * @param string $region
   * @return array
   */
  protected static function coreResourceList($region) {
    $settings = Civi::settings();
    $contactID = (int) CRM_Core_Session::getLoggedInContactID();

    // Scripts needed by everyone, everywhere
    // FIXME: This is too long; list needs finer-grained segmentation
    $items = [
      "bower_components/jquery/dist/jquery.min.js",
      "bower_components/jquery-ui/jquery-ui.min.js",
      "bower_components/jquery-ui/themes/smoothness/jquery-ui.min.css",
      "bower_components/lodash-compat/lodash.min.js",
      "packages/jquery/plugins/jquery.mousewheel.min.js",
      "bower_components/select2/select2.min.js",
      "bower_components/select2/select2.min.css",
      "bower_components/font-awesome/css/all.min.css",
      // shims for fontawesome 4 - webfonts are loaded from the package
      "bower_components/font-awesome/css/v4-font-face.min.css",
      // we load our own version of the shim with crm-i namespace
      "css/crm-i-v4-shims.css",
      "packages/jquery/plugins/jquery.form.min.js",
      "packages/jquery/plugins/jquery.timeentry.min.js",
      "packages/jquery/plugins/jquery.blockUI.min.js",
      "bower_components/datatables/media/js/jquery.dataTables.min.js",
      "bower_components/datatables/media/css/jquery.dataTables.min.css",
      "bower_components/jquery-validation/dist/jquery.validate.min.js",
      "bower_components/jquery-validation/dist/additional-methods.min.js",
      "js/Common.js",
      "js/crm.datepicker.js",
      "js/crm.ajax.js",
      "js/wysiwyg/crm.wysiwyg.js",
    ];

    // Dynamic localization script
    if (!CRM_Core_Config::isUpgradeMode()) {
      $items[] = Civi::service('asset_builder')->getUrl('crm-l10n.js', CRM_Core_Resources::getL10nJsParams());
    }

    // These scripts are only needed by back-office users
    if (CRM_Core_Permission::check('access CiviCRM')) {
      $items[] = "packages/jquery/plugins/jquery.tableHeader.js";
      $items[] = "packages/jquery/plugins/jquery.notify.min.js";
    }

    // Menubar
    $position = 'none';
    if (
      $contactID && !CRM_Core_Config::singleton()->userFrameworkFrontend
      && CRM_Core_Permission::check('access CiviCRM')
      && !CRM_Utils_Constant::value('CIVICRM_DISABLE_DEFAULT_MENU')
      && !CRM_Core_Config::isUpgradeMode()
    ) {
      $position = $settings->get('menubar_position') ?: 'over-cms-menu';
    }
    if ($position !== 'none') {
      $items[] = 'bower_components/smartmenus/dist/jquery.smartmenus.min.js';
      $items[] = 'bower_components/smartmenus/dist/addons/keyboard/jquery.smartmenus.keyboard.min.js';
      $items[] = 'js/crm.menubar.js';
      // @see CRM_Core_Resources::renderMenubarStylesheet
      $items[] = Civi::service('asset_builder')->getUrl('crm-menubar.css', [
        'menubarColor' => $settings->get('menubar_color'),
        'height' => 40,
        'breakpoint' => 768,
      ]);
      // Variables for crm.menubar.js
      $items[] = [
        'menubar' => [
          'position' => $position,
          'qfKey' => CRM_Core_Key::get('CRM_Contact_Controller_Search', TRUE),
          'cacheCode' => CRM_Core_BAO_Navigation::getCacheKey($contactID),
        ],
      ];
    }

    // JS for multilingual installations
    $languageLimit = $settings->get('languageLimit');
    if (is_array($languageLimit) && count($languageLimit) > 1 && CRM_Core_Permission::check('translate CiviCRM')) {
      $items[] = "js/crm.multilingual.js";
    }

    // Enable administrators to edit option lists in a dialog
    if (CRM_Core_Permission::check('administer CiviCRM') && $settings->get('ajaxPopupsEnabled')) {
      $items[] = "js/crm.optionEdit.js";
    }

    $tsLocale = CRM_Core_I18n::getLocale();
    // Add localized jQuery UI files
    if ($tsLocale && $tsLocale != 'en_US') {
      // Search for i18n file in order of specificity (try fr-CA, then fr)
      [$lang] = explode('_', $tsLocale);
      $path = "bower_components/jquery-ui/ui/i18n";
      foreach ([str_replace('_', '-', $tsLocale), $lang] as $language) {
        $localizationFile = "$path/datepicker-{$language}.js";
        if (Civi::resources()->getPath('civicrm', $localizationFile)) {
          $items[] = $localizationFile;
          break;
        }
      }
    }

    // Allow hooks to modify this list
    CRM_Utils_Hook::coreResourceList($items, $region);

    // Oof, existing listeners would expect $items to typically begin with 'bower_components/' or 'packages/'
    // (using an implicit base of `[civicrm.root]`). We preserve the hook contract and cleanup $items post-hook.
    $map = [
      'bower_components' => rtrim(Civi::paths()->getUrl('[civicrm.bower]/.', 'absolute'), '/'),
      'packages' => rtrim(Civi::paths()->getUrl('[civicrm.packages]/.', 'absolute'), '/'),
    ];
    $filter = function($m) use ($map) {
      return $map[$m[1]] . $m[2];
    };
    $items = array_map(function($item) use ($filter) {
      return is_array($item) ? $item : preg_replace_callback(';^(bower_components|packages)(/.*);', $filter, $item);
    }, $items);

    return $items;
  }

}
