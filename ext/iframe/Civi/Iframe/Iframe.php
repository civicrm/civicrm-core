<?php
namespace Civi\Iframe;

use Civi\Core\Attribute\CiviEventListener;
use Civi\Core\Url;
use Civi\Core\Service\AutoService;
use CRM_Iframe_ExtensionUtil as E;

/**
 * @service iframe
 */
class Iframe extends AutoService {

  /**
   * Is there a driver that supports IFRAMEs for this environment?
   *
   * @return bool
   */
  public function isSupported(): bool {
    return in_array(CIVICRM_UF, ['WordPress', 'Standalone'], TRUE) || class_exists($this->getTemplate());
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
  #[CiviEventListener(event: '&civi.url.render.iframe')]
  public function onRenderUrl(Url $url, ?string &$result) {
    switch (CIVICRM_UF) {
      case 'WordPress':
        $result = \Civi::url('frontend://', 'a')
          ->merge($url, ['path', 'query', 'fragment', 'fragmentQuery', 'flags'])
          ->addQuery('_cvwpif=1');
        return;

      case 'Standalone':
        $result = \Civi::url('frontend://', 'a')
          ->merge($url, ['path', 'query', 'fragment', 'fragmentQuery', 'flags'])
          ->addQuery('iframe=1');
        return;

      default:
        $result = \Civi::url('[civicrm.iframe]', 'a')->merge($url, ['path', 'query', 'fragment', 'fragmentQuery', 'flags']);
    }

  }

}
