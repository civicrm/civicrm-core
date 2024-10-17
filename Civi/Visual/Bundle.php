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

namespace Civi\Visual;

use Civi;
use CRM_Core_Resources_Bundle;
use CRM_Utils_Hook;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * This class defines the `visual-bundle.js` asset, which combines `dc.js`,
 * `d3.js`, and `crossfilter.js` into one asset -- and puts the services
 * in the `CRM.visual` namespace.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 *
 * @service bundle.visual
 */
class Bundle extends CRM_Core_Resources_Bundle implements EventSubscriberInterface, Civi\Core\Service\AutoServiceInterface {

  use Civi\Core\Service\AutoServiceTrait;

  const NAME = 'visual';
  const JS_FILE = 'visual-bundle.js';
  const CSS_FILE = 'visual-bundle.css';

  protected $initialized;

  public static function getSubscribedEvents() {
    return [
      'hook_civicrm_buildAsset' => [['buildAssetJs', 0], ['buildAssetCss', 0]],
    ];
  }

  public function __construct() {
    parent::__construct(static::NAME,
      ['script', 'scriptFile', 'scriptUrl', 'settings', 'style', 'styleFile', 'styleUrl', 'markup']);
  }

  public function getAll(): iterable {
    if (!$this->initialized) {
      $this->addScriptUrl(Civi::service('asset_builder')->getUrl(static::JS_FILE));
      $this->addStyleUrl(Civi::service('asset_builder')->getUrl(static::CSS_FILE));

      CRM_Utils_Hook::alterBundle($this);
      $this->fillDefaults();
    }
    return parent::getAll();
  }

  /**
   * Generate asset content (when accessed via AssetBuilder).
   *
   * @param \Civi\Core\Event\GenericHookEvent $event
   * @see CRM_Utils_hook::buildAsset()
   * @see \Civi\Core\AssetBuilder
   */
  public static function buildAssetJs($event) {
    if ($event->asset !== static::JS_FILE) {
      return;
    }

    $files = [
      'crossfilter' => '[civicrm.bower]/crossfilter-1.4.x/crossfilter.min.js',
      'd3' => '[civicrm.bower]/d3-3.5.x/d3.min.js',
      'dc' => '[civicrm.bower]/dc-2.1.x/dc.min.js',
    ];

    $content = [];

    $content[] = "(function(){";

    // ensure our namespace objects exist
    $content[] = 'window.CRM = window.CRM || {};';
    $content[] = 'CRM.visual = CRM.visual || {};';

    // backup any existing objects in the global namespace
    $content[] = "var backups = {";
    foreach ($files as $var => $file) {
      $content[] = "$var: window.$var,";
    }
    $content[] = "};";

    // include external scripts
    foreach ($files as $var => $file) {
      $content[] = "// File: $file";
      $content[] = file_get_contents(Civi::paths()->getPath($file));
    }

    foreach ($files as $var => $file) {
      $content[] = "CRM.visual.$var = $var;";
    }
    // restore backups to the global namespace
    foreach ($files as $var => $file) {
      $content[] = "window.$var = backups.$var;";
    }
    $content[] = "})();";

    // add CRM.visual.createChart function
    $content[] = file_get_contents(Civi::paths()->getPath('[civicrm.root]/js/crm.visual.createChart.js'));

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
    if ($event->asset !== static::CSS_FILE) {
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
