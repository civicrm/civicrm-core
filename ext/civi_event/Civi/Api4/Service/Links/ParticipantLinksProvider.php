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
class ParticipantLinksProvider extends \Civi\Core\Service\AutoSubscriber {
  use LinksProviderTrait;

  public static function getSubscribedEvents(): array {
    return [
      'civi.api.respond' => ['alterParticipantLinksResult', -50],
    ];
  }

  /**
   * Customize event participant links
   *
   * @param \Civi\API\Event\RespondEvent $e
   * @return void
   * @throws \CRM_Core_Exception
   */
  public static function alterParticipantLinksResult(RespondEvent $e): void {
    $request = $e->getApiRequest();
    if ($request['version'] == 4 && $request->getEntityName() === 'Participant' && is_a($request, '\Civi\Api4\Action\GetLinks')) {
      $links = (array) $e->getResponse();
      $addLinkIndex = self::getActionIndex($links, 'add');
      $transferLinkIndex = self::getActionIndex($links, 'detach');
      if (isset($transferLinkIndex)) {
        if ($request->getCheckPermissions() && !\CRM_Core_Permission::check('edit event participants')) {
          unset($links[$transferLinkIndex]);
        }
      }
      if (isset($addLinkIndex)) {
        $contactId = $request->getValue('contact_id');
        if ($request->getCheckPermissions() && !\CRM_Core_Permission::check('edit event participants')) {
          unset($links[$addLinkIndex]);
        }
        elseif ($contactId) {
          // Update add link appropriate to the context of viewing a single contact
          $links[$addLinkIndex]['icon'] = 'fa-ticket';
          $links[$addLinkIndex]['text'] = ts('Register for Event');
          $links[$addLinkIndex]['path'] = "civicrm/contact/view/participant?reset=1&action=add&cid=$contactId&context=participant";
          if ($request->getExpandMultiple() && \CRM_Core_Config::isEnabledBackOfficeCreditCardPayments()) {
            // 2nd add link for credit card registrations
            $ccLink = $links[$addLinkIndex];
            $ccLink['text'] = ts('Submit Credit Card Event Registration');
            $ccLink['icon'] = 'fa-credit-card';
            $ccLink['path'] = "civicrm/contact/view/participant?reset=1&action=add&cid=$contactId&context=participant&mode=live";
            $links[] = $ccLink;
          }
        }
      }

      $e->getResponse()->exchangeArray(array_values($links));
    }
  }

}
