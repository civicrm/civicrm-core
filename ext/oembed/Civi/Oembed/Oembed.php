<?php
namespace Civi\Oembed;

use Civi\Core\Url;
use Civi\Core\Service\AutoService;
use CRM_Oembed_ExtensionUtil as E;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @service oembed
 */
class Oembed extends AutoService implements EventSubscriberInterface {

  public static function getSubscribedEvents() {
    return ['&civi.url.render.oembed' => 'onRenderUrl'];
  }

  /**
   * Determine which template to use for the oembed entry-point.
   *
   * @return string
   *   Ex: 'Civi\Oembed\EntryPoint\Drupal8'
   */
  public function getTemplate(): string {
    return 'Civi\\Oembed\\EntryPoint\\' . CIVICRM_UF;
  }

  /**
   * Generate physical URLs for `oembed://` links
   * (per `civi.url.render.oembed`).
   *
   * @param \Civi\Core\Url $url
   * @param string|null $result
   * @throws \CRM_Core_Exception
   */
  public function onRenderUrl(Url $url, ?string &$result) {
    $result = \Civi::url('[civicrm.oembed]', 'a')->merge($url, ['path', 'query', 'fragment', 'fragmentQuery', 'flags']);
  }

}
