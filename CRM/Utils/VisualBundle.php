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
 * This class defines the `visual-bundle.js` asset, which combines `dc.js`,
 * `d3.js`, and `crossfilter.js` into one asset -- and puts the services
 * in the `CRM.visual` namespace.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 * $Id$
 *
 */
class CRM_Utils_VisualBundle {

  public static function register() {
    Civi::resources()->addScriptUrl(Civi::service('asset_manager')->getUrl('visual-bundle.js'));
    Civi::resources()->addStyleUrl(Civi::service('asset_manager')->getUrl('visual-bundle.css'));
  }

  /**
   * Generate asset content (when accessed via AssetBuilder).
   *
   * @param \Civi\Core\Event\GenericHookEvent $event
   * @see CRM_Utils_hook::buildAsset()
   * @see \Civi\Core\AssetBuilder
   */
  public static function buildAssetJs($event) {
    if ($event->asset !== 'visual-bundle.js') {
      return;
    }

    $files = [
      'crossfilter' => '[civicrm.bower]/crossfilter-1.3.x/crossfilter.min.js',
      'd3' => '[civicrm.bower]/d3-3.5.x/d3.min.js',
      'dc' => '[civicrm.bower]/dc-2.1.x/dc.min.js',
    ];

    $content = [];
    $content[] = "(function(){";
    $content[] = "var backups = {d3: window.d3, crossfilter: window.crossfilter, dc: window.dc}";
    $content[] = 'window.CRM = window.CRM || {};';
    $content[] = 'CRM.visual = CRM.visual || {};';
    foreach ($files as $var => $file) {
      $content[] = "// File: $file";
      $content[] = file_get_contents(Civi::paths()->getPath($file));
    }
    foreach ($files as $var => $file) {
      $content[] = "CRM.visual.$var = $var;";
    }
    foreach ($files as $var => $file) {
      $content[] = "window.$var = backups.$var;";
    }
    $content[] = "})();";

    $event->mimeType = 'application/javascript';
    $event->content = implode("\n", $content);
  }

  /**
   * Generate asset content (when accessed via AssetBuilder).
   *
   * @param \Civi\Core\Event\GenericHookEvent $event
   * @see CRM_Utils_hook::buildAsset()
   * @see \Civi\Core\AssetBuilder
   */
  public static function buildAssetCss($event) {
    if ($event->asset !== 'visual-bundle.css') {
      return;
    }

    $files = [
      '[civicrm.bower]/dc-2.1.x/dc.min.css',
    ];

    $content = [];
    foreach ($files as $file) {
      $content[] = "// File: $file";
      $content[] = file_get_contents(Civi::paths()->getPath($file));
    }

    $event->mimeType = 'text/css';
    $event->content = implode("\n", $content);
  }

}
