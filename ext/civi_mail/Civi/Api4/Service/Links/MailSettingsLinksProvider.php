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

namespace Civi\Api4\Service\Links;

use Civi\API\Event\RespondEvent;

/**
 * @service
 * @internal
 */
class MailSettingsLinksProvider extends \Civi\Core\Service\AutoSubscriber {
  use LinksProviderTrait;

  public static function getSubscribedEvents(): array {
    return [
      'civi.api.respond' => 'alterLinksResult',
    ];
  }

  public static function alterLinksResult(RespondEvent $e): void {
    $request = $e->getApiRequest();
    if ($request['version'] == 4 && is_a($request, '\Civi\Api4\Action\GetLinks') && $request->getEntityName() === 'MailSettings') {
      $links = (array) $e->getResponse();
      $addLinkIndex = self::getActionIndex($links, 'add');
      // Unset the generic "add" link and replace it with links to each mailSetup account
      // @see \CRM_Utils_Hook::mailSetupActionsx
      if (isset($addLinkIndex) && $request->getExpandMultiple()) {
        // Use the single add link from the schema as a template
        $addTemplate = $links[$addLinkIndex];
        $newLinks = [];
        foreach (\CRM_Core_BAO_MailSettings::getSetupActions() as $key => $action) {
          $link = $addTemplate;
          $link['text'] = $action['title'];
          // The standard link is fine as-is. Others use a redirect:
          if ($key !== 'standard') {
            $link['path'] = "civicrm/ajax/setupMailAccount?type=$key";
            $link['target'] = '_blank';
          }
          $newLinks[] = $link;
          if (isset($addTemplate['weight'])) {
            $addTemplate['weight']++;
          }
        }
        array_splice($links, $addLinkIndex, 1, $newLinks);
      }
      $e->getResponse()->exchangeArray(array_values($links));
    }
  }

}
