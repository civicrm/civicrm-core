<?php

namespace Civi;

use Civi\Core\Event\GenericHookEvent;
use Civi\Core\Service\AutoService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @service ckeditor4.kcfinder
 */
class Kcfinder extends AutoService implements EventSubscriberInterface {

  public static function getSubscribedEvents(): array {
    return [
      'hook_civicrm_buildAsset' => [['buildJs', 0], ['buildCss', 0]],
    ];
  }

  public function buildJs(GenericHookEvent $e): void {
    if ($e->asset !== 'kcfinder.js') {
      return;
    }
    $e->mimeType = 'text/javascript';

    $content = implode("\n", [
      $this->buildAsset('js', 'js'),
      $this->buildAsset('js', 'themes/default'),
      $this->getJsLocalize($e->params['lng']),
    ]);

    $content = $this->replaceUrls($content);

    $e->content = $content;
  }

  public function buildCss(GenericHookEvent $e): void {
    if ($e->asset !== 'kcfinder.css') {
      return;
    }

    $e->mimeType = 'text/css';

    $content = implode("\n", [
      $this->buildAsset('css', 'css'),
      $this->buildAsset('css', 'themes/default'),
    ]);

    $content = $this->replaceUrls($content);

    $e->content = $content;
  }

  protected function buildAsset(string $type, string $directory): string {
    $normalWorkingDir = \getcwd();
    $kcfinderDir = \Civi::paths()->getPath('[civicrm.packages]/kcfinder');
    chdir($kcfinderDir);
    require_once 'core/autoload.php';

    \ob_start();
    $minifier = new \kcfinder\minifier($type);
    $minifier->minify(NULL, $directory);
    $content = \ob_get_clean();

    chdir($normalWorkingDir);
    return $content;
  }

  protected function replaceUrls(string $content): string {
    $replacements = [];

    $imageBaseUrl = (string) \Civi::url('[civicrm.packages]/kcfinder/themes/default/img')->setPreferFormat('absolute');
    //$dynamicAssetUrl = \Civi::url('civicrm/kcfinder/asset');

    $replacements = [
      # php routes
      'browse.php' => (string) \Civi::url('civicrm/kcfinder/browse'),
      #static images
      'themes/default/img/loading.gif' => "img/loading.gif",
      'img/loading.gif' => "{$imageBaseUrl}/loading.gif",
      'img/icons/upload.png' => "{$imageBaseUrl}/icons/upload.png",
      'img/icons/refresh.png' => "{$imageBaseUrl}/icons/refresh.png",
      'img/icons/settings.png' => "{$imageBaseUrl}/icons/settings.png",
      'img/icons/maximize.png' => "{$imageBaseUrl}/icons/maximize.png",
      'img/icons/about.png' => "{$imageBaseUrl}/icons/about.png",
    ];

    foreach ($replacements as $old => $new) {
      $content = str_replace($old, $new, $content);
    }

    return $content;
  }

  protected function getJsLocalize(string $lang): string {
    if (!$lang || $lang === 'en') {
      return '';
    }

    $_GET['lng'] = $lang;
    $normalWorkingDir = \getcwd();
    $kcfinderDir = \Civi::paths()->getPath('[civicrm.packages]/kcfinder');
    chdir($kcfinderDir);

    \ob_start();
    require_once 'js_localize.php';
    $content = \ob_get_clean();

    chdir($normalWorkingDir);
    return $content;
  }

  public static function bootstrapPage() {
    $_SESSION['KCFINDER']['disabled'] = FALSE;
    $_SESSION['KCFINDER']['uploadURL'] = \CRM_Core_Config::singleton()->imageUploadURL;
    $_SESSION['KCFINDER']['uploadDir'] = \CRM_Core_Config::singleton()->imageUploadDir;

    $kcfinderPath = \Civi::paths()->getPath('[civicrm.packages]/kcfinder');
    chdir($kcfinderPath);
    require_once 'core/bootstrap.php';
  }

}
