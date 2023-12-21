<?php
namespace Civi\Oembed;

use Civi\Core\Url;
use Civi\Core\Service\AutoService;
use CRM_Oembed_ExtensionUtil as E;

/**
 * @service oembed
 */
class Oembed extends AutoService {

  /**
   * Get the local path to the oEmbed entry-point.
   *
   * @return string
   */
  public function getPath(): string {
    return \Civi::paths()->getPath('[cms.root]/oembed.php');
  }

  /**
   * Get the base URL of the oEmbed entry-point.
   *
   * @return \Civi\Core\Url
   */
  public function getUrl(): Url {
    return \Civi::url('[cms.root]/oembed.php', 'a');
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

}
