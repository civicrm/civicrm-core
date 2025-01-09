<?php

namespace Civi\riverlea;

use CRM_riverlea_ExtensionUtil as E;

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

  public const CSS_FILE = 'river.css';

  public static function getSubscribedEvents() {
    return [
      'hook_civicrm_buildAsset' => ['buildAssetCss', 0],
    ];
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
   * @param \Civi\Core\Event\GenericHookEvent $e
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

    $content = [];

    // add base vars for the stream
    $content[] = self::getCSSFromFile('_variables.css', $stream);

    switch ($params['dark'] ?? NULL) {
      case 'light':
        // tell OS we want light for system elements
        $content[] = ":root { color-scheme: light; }";
        break;

      case 'dark':
        // tell OS we want dark for system elements
        $content[] = ":root { color-scheme: dark; }";
        // add stream dark vars unconditionally
        $content[] = self::getCSSFromFile('_dark.css', $stream);
        break;

      case 'inherit':
      default:
        // tell OS we are happy with light or dark for system elements
        $content[] = ":root { color-scheme: light dark; }";
        // add stream dark vars wrapped inside a media query
        $content[] = '@media (prefers-color-scheme: dark) {';
        $content[] = self::getCSSFromFile('_dark.css', $stream);
        $content[] = '}';
        break;
    }

    $e->content = implode("\n", $content);
  }

  /**
   * Check file exists and return contents or empty string
   *
   * @param string $cssFileName The name of the css file (eg. _variables.css)
   * @param string $stream The name of the riverlea stream (eg. walbrook)
   *
   * @return string
   */
  private static function getCSSFromFile(string $cssFileName, string $stream): string {
    $res = \Civi::resources();
    $theme = \Civi::service('themes')->get($stream);
    $file = '';
    // For riverlea themes CSS should be located in stream/streamname/css - prefix="stream/streamname/"
    if (isset($theme['prefix'])) {
      $file .= $theme['prefix'];
    }
    // Append css dir and filename so we end up with stream/streamname/css/filename.css
    $file .= 'css/' . $cssFileName;
    $file = $res->filterMinify($theme['ext'], $file);

    // Now get the full path for the css file
    $filePath = $res->getPath($theme['ext'], $file);
    if (is_file($filePath)) {
      // File exists and is a file? Return it!
      return file_get_contents($filePath) ?? '';
    }
    return '';
  }

}
