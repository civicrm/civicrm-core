<?php

namespace Civi\riverlea;

use \CRM_riverlea_ExtensionUtil as E;

/**
 * This class generates a `river.css` file for Riverlea themes containing
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

  protected const CSS_FILE = 'river.css';

  public static function getSubscribedEvents() {
    return [
      '&hook_civicrm_alterBundle' => ['alterCoreBundle', 0],
      'hook_civicrm_buildAsset' => ['buildAssetCss', 0],
    ];
  }

  public static function alterCoreBundle(\CRM_Core_Resources_Bundle $bundle) {
    if ($bundle->name !== 'coreResources') {
      return;
    }
    $bundle->addStyleUrl(\Civi::service('asset_builder')->getUrl(static::CSS_FILE, self::getCssParams()));
  }

  public static function getCssParams(): array {
    $darkModeSetting = \CRM_Utils_System::isFrontendPage() ?
      \Civi::settings()->get('riverlea_dark_mode_frontend') :
      \Civi::settings()->get('riverlea_dark_mode_backend');

    return [
      'stream' => \Civi::service('themes')->getActiveThemeKey(),
      'dark' => $darkModeSetting,
    ];
  }

  /**
   * Generate asset content (when accessed via AssetBuilder).
   *
   * @param \Civi\Core\Event\GenericHookEvent $event
   *
   * @see CRM_Utils_hook::buildAsset()
   * @see \Civi\Core\AssetBuilder
   */
  public static function buildAssetCss($e) {
    if ($e->asset !== static::CSS_FILE) {
      return;
    }
    $e->mimeType = 'text/css';

    $params = $e->params;

    $stream = $params['stream'] ?? 'empty';

    // Get the stream directory (could be in core, this extension or another extension that provides a riverlea stream)
    $theme = \Civi::service('themes')->get($stream);
    $extRootDir = \CRM_Core_Resources::singleton()->getPath($theme['ext']);
    $streamDir = $extRootDir . '/css';

    $content = [];

    // add base vars for the stream
    $content[] = self::getCSSFromFile($streamDir . '/_variables.css');

    switch ($params['dark'] ?? NULL) {
      case 'light':
        // nothing more to do
        break;

      case 'dark':
        // add dark vars unconditionally
        $content[] = self::getCSSFromFile($streamDir . '/_dark.css');
        break;

      case 'inherit':
      default:
        // add dark vars wrapped inside a media query
        $content[] = '@media (prefers-color-scheme: dark) {';
        $content[] = self::getCSSFromFile($streamDir . '/_dark.css');
        $content[] = '}';
        break;
    }

    $e->content = implode("\n", $content);
  }

  /**
   * Check file exists and return contents or empty string
   *
   * @param string $file
   *
   * @return string
   */
  private static function getCSSFromFile(string $file): string {
    if (is_file($file)) {
      return file_get_contents($file) ?? '';
    }
    return '';
  }

}
