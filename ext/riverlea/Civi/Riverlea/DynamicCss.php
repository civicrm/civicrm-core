<?php

namespace Civi\Riverlea;

use CRM_riverlea_ExtensionUtil as E;

/**
 * This class generates a `river.css` file for Riverlea streams containing
 * dynamically generated css content
 *
 * At the moment this is used to serve the right vars for a given dark mode setting
 *
 * In the future it might allow other dynamic tweaks
 *
 * @service riverlea.dynamic_css
 */
class DynamicCss implements \Symfony\Component\EventDispatcher\EventSubscriberInterface, \Civi\Core\Service\AutoServiceInterface {

  use \Civi\Core\Service\AutoServiceTrait;

  public const CSS_FILE = 'river.css';

  public static function getSubscribedEvents() {
    return [
      'hook_civicrm_buildAsset' => ['buildAssetCss', 0],
    ];
  }

  public function getAvailableStreamMeta(): array {
    $streams = \Civi::$statics['riverlea_streams'] ?? NULL;

    if (is_null($streams)) {
      try {
        $streams = (array) \Civi\Api4\RiverleaStream::get(FALSE)
          ->addSelect('name', 'label', 'extension', 'file_prefix', 'parent_id', 'id', 'modified_date')
          ->execute()
          ->indexBy('name');

        \Civi::$statics['riverlea_streams'] = $streams;
      }
      catch (\CRM_Core_Exception $e) {
        \Civi::log()->warning('Error loading Riverlea stream meta');
        return [];
      }
    }

    return $streams;
  }

  public function getCssParams(): array {
    $stream = \Civi::service('themes')->getActiveThemeKey();

    // we add the stream modified date to asset params as a cache buster
    $streamMeta = self::getAvailableStreamMeta()[$stream] ?? [];
    $streamModified = $streamMeta['modified_date'] ?? NULL;

    return [
      'stream' => $stream,
      'modified' => $streamModified,
      'is_frontend' => \CRM_Utils_System::isFrontendPage(),
    ];
  }

  /**
   * Generate asset content (when accessed via AssetBuilder).
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   *
   * @see CRM_Utils_hook::buildAsset()
   * @see \Civi\Core\AssetBuilder
   */
  public function buildAssetCss($e) {
    if ($e->asset !== static::CSS_FILE) {
      return;
    }
    $e->mimeType = 'text/css';

    $render = \Civi\Api4\RiverleaStream::render(FALSE)
      ->addWhere('name', '=', $e->params['stream'])
      ->setIsFrontend($e->params['is_frontend'])
      ->execute()
      ->first();

    $e->content = $render['content'] ?? '';
  }

}
