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

namespace Civi\ChartKit;

use Civi;
use CRM_Core_Resources_Bundle;
use CRM_ChartKit_ExtensionUtil as E;
use CRM_Utils_Hook;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * This class defines the `chart_kit.js` assets, which combines `dc.js`,
 * `d3.js`, and `crossfilter.js` into one asset -- and puts the services
 * in the `CRM.chart_kit` namespace.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 * @service bundle.chart_kit
 */
class Bundle extends CRM_Core_Resources_Bundle implements EventSubscriberInterface, Civi\Core\Service\AutoServiceInterface {

  use Civi\Core\Service\AutoServiceTrait;

  const NAME = 'chart_kit';
  const JS_FILE = 'chart_kit.js';
  const CSS_FILE = 'chart_kit.css';

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
   *
   * @see CRM_Utils_hook::buildAsset()
   * @see \Civi\Core\AssetBuilder
   */
  public static function buildAssetJs($event) {
    if ($event->asset !== static::JS_FILE) {
      return;
    }

    $files = [
      'crossfilter' => E::path('packages/crossfilter/crossfilter.min.js'),
      'd3' => E::path('packages/d3/d3.min.js'),
      'dc' => E::path('packages/dc/dist/dc.min.js'),
    ];

    $content = [];
    $content[] = "(function(){";
    $content[] = "var backups = {d3: window.d3, crossfilter: window.crossfilter, dc: window.dc}";
    $content[] = 'window.CRM = window.CRM || {};';
    $content[] = 'CRM.chart_kit = CRM.chart_kit || {};';
    foreach ($files as $var => $file) {
      $content[] = "// File: $file";
      $content[] = file_get_contents($file);
    }
    foreach ($files as $var => $file) {
      $content[] = "CRM.chart_kit.$var = $var;";
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
   *
   * @see CRM_Utils_hook::buildAsset()
   * @see \Civi\Core\AssetBuilder
   */
  public static function buildAssetCss($event) {
    if ($event->asset !== static::CSS_FILE) {
      return;
    }

    $files = [
      E::path('packages/dc/dist/style/dc.min.css'),
    ];

    $content = [];
    foreach ($files as $file) {
      $content[] = "// File: $file";
      $content[] = file_get_contents($file);
    }

    $event->mimeType = 'text/css';
    $event->content = implode("\n", $content);
  }

}
