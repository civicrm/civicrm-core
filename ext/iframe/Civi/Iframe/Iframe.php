<?php
namespace Civi\Iframe;

use Civi\Core\Url;
use Civi\Core\Service\AutoService;
use CRM_Iframe_ExtensionUtil as E;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @service iframe
 */
class Iframe extends AutoService implements EventSubscriberInterface {

  public static function getSubscribedEvents() {
    return ['&civi.url.render.iframe' => 'onRenderUrl'];
  }

  /**
   * Is there a driver that supports IFRAMEs for this environment?
   *
   * @return bool
   */
  public function isSupported(): bool {
    return CIVICRM_UF === 'WordPress' || class_exists($this->getTemplate());
  }

  /**
   * Determine which template to use for the iframe entry-point.
   *
   * @return string
   *   Ex: 'Civi\Iframe\EntryPoint\Drupal8'
   */
  public function getTemplate(): string {
    return 'Civi\\Iframe\\EntryPoint\\' . CIVICRM_UF;
  }

  /**
   * Generate physical URLs for `iframe://` links
   * (per `civi.url.render.iframe`).
   *
   * @param \Civi\Core\Url $url
   * @param string|null $result
   * @throws \CRM_Core_Exception
   */
  public function onRenderUrl(Url $url, ?string &$result) {
    if (CIVICRM_UF === 'WordPress') {
      $result = \Civi::url('frontend://', 'a')
        ->merge($url, ['path', 'query', 'fragment', 'fragmentQuery', 'flags'])
        ->addQuery('_cvwpif=1');
      return;
    }

    $result = \Civi::url('[civicrm.iframe]', 'a')->merge($url, ['path', 'query', 'fragment', 'fragmentQuery', 'flags']);
  }

}
