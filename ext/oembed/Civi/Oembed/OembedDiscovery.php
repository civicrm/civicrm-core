<?php

namespace Civi\Oembed;

use Civi;
use Civi\Core\Service\AutoService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Emit tags so that downstream users know that pages are embeddable.
 *
 * @service oembed.discovery
 */
class OembedDiscovery extends AutoService implements EventSubscriberInterface {

  public static function getSubscribedEvents() {
    return [
      '&civi.invoke.auth' => ['onInvoke', 100],
    ];
  }

  public function onInvoke(array $path) {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
      return;
    }

    $pathStr = implode('/', $path);
    $oembed = Civi::service('oembed');
    if (!$oembed->isAllowedRoute($pathStr)) {
      return;
    }

    // In standard discovery, any supported page (aka public pages) will include <LINK>
    // tag to advertise its support for oEmbed.
    if (Civi::settings()->get('oembed_standard') || $pathStr === 'civicrm/share') {
      $query = $oembed->findPropagatedParams($_GET);
      \CRM_Core_Region::instance('html-header')->add([
        'name' => 'oembed',
        'markup' => $oembed->createLinkTags($pathStr, $query),
      ]);
    }

    // In explicit sharing, the administrator sees a panel with options for sharing.
    if (Civi::settings()->get('oembed_share') && \CRM_Core_Permission::check('administer oembed')) {
      $query = $oembed->findPropagatedParams($_GET);

      $data = [
        'page' => Civi::url('frontend://' . $pathStr, 'a')->addQuery($query),
        'iframe' => Civi::url('iframe://' . $pathStr, 'a')->addQuery($query),
        'share' => Civi::url('frontend://civicrm/share', 'a')->addQuery(['url' => Civi::url('frontend://' . $pathStr, 'a')->addQuery($query)]),
      ];
      $options = [
        'maxwidth' => Civi::service('oembed')->getDefaultWidth(),
        'maxheight' => Civi::service('oembed')->getDefaultHeight(),
      ];

      Civi::service('angularjs.loader')->addModules(['crmOembedSharing']);
      \CRM_Core_Region::instance('page-footer')->add([
        'name' => 'oembed-share',
        'markup' => sprintf('<crm-angular-js modules="crmOembedSharing"> <crm-oembed-sharing urls="%s" options="%s" /> </crm-angular-js>',
          htmlentities(\CRM_Utils_JS::encode($data)),
          htmlentities(\CRM_Utils_JS::encode($options)),
        ),
      ]);
    }
  }

}
