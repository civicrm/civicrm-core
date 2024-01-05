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
class MembershipLinksProvider extends \Civi\Core\Service\AutoSubscriber {
  use LinksProviderTrait;

  public static function getSubscribedEvents(): array {
    return [
      'civi.api.respond' => 'alterMembershipLinksResult',
    ];
  }

  public static function alterMembershipLinksResult(RespondEvent $e): void {
    $request = $e->getApiRequest();
    if ($request['version'] == 4 && $request->getEntityName() === 'Membership' && is_a($request, '\Civi\Api4\Action\GetLinks')) {
      $links = (array) $e->getResponse();
      $isUpdateBilling = $isCancelSupported = FALSE;
      $addTemplate = [
        'ui_action' => '',
        'entity' => 'Membership',
        'path' => '',
        'text' => '',
        'icon' => 'fa-external-link',
        'target' => 'crm-popup',
      ];
      self::addLinks($links, $addTemplate);

      if (!\CRM_Core_Config::isEnabledBackOfficeCreditCardPayments()) {
        self::unsetLinks($links, ['followup']);
      }

      $membershipId = $request->getValue('id');
      $ownerMembershipId = $request->getValue('owner_membership_id');
      if ($ownerMembershipId) {
        self::unsetLinks($links, ['update', 'delete', 'renew', 'followup', 'cancelrecur', 'changebilling']);
      }
      elseif ($membershipId) {
        $paymentObject = \CRM_Financial_BAO_PaymentProcessor::getProcessorForEntity($membershipId, 'membership', 'obj');
        if (!empty($paymentObject)) {
          $isUpdateBilling = $paymentObject->supports('updateSubscriptionBillingInfo');
        }
        if (!$isUpdateBilling) {
          self::unsetLinks($links, ['changebilling']);
        }
        $isCancelSupported = \CRM_Member_BAO_Membership::isCancelSubscriptionSupported($membershipId);
        if (!$isCancelSupported) {
          self::unsetLinks($links, ['cancelrecur']);
        }
      }

      // Unset renew and followup for deceased memberships.
      $membershipStatus = $request->getValue('status_id:name');
      if ($membershipStatus && $membershipStatus === 'Deceased') {
        self::unsetLinks($links, ['renew', 'followup']);
      }

      $membershipTypeId = $request->getValue('membership_type_id');
      if ($membershipTypeId && \CRM_Financial_BAO_FinancialType::isACLFinancialTypeStatus()) {
        $finType = \Civi\Api4\MembershipType::get(TRUE)
          ->addSelect('financial_type_id:name')
          ->addWhere('id', '=', $membershipTypeId)
          ->execute()
          ->first()['financial_type_id:name'] ?? NULL;
        if ($finType && !\CRM_Core_Permission::check('edit contributions of type ' . $finType)) {
          self::unsetLinks($links, ['update', 'renew', 'followup']);
        }
        if ($finType && !\CRM_Core_Permission::check('delete contributions of type ' . $finType)) {
          self::unsetLinks($links, ['delete']);
        }
      }

      $params = $request->getParams();
      if (!empty($params['where'][0][0]) && $params['where'][0][0] == 'ui_action') {
        $action = $params['where'][0][2];
        $actionLinkIndex = self::getActionIndex($links, $action);
        $links = [$links[$actionLinkIndex]];
      }

      $e->getResponse()->exchangeArray(array_values($links));
    }
  }

  private static function addLinks(array &$newLinks, array $addTemplate) {
    $actions = [
      'renew' => [
        'path' => 'civicrm/contact/view/membership?action=renew&reset=1&cid=[contact_id]&id=[id]&context=membership&selectedChild=member',
        'text' => ts('Renew Membership'),
      ],
      'followup' => [
        'path' => 'civicrm/contact/view/membership?action=renew&reset=1&cid=[contact_id]&id=[id]&context=membership&selectedChild=member&mode=live',
        'text' => ts('Renew-Credit Card Membership'),
      ],
      'cancelrecur' => [
        'path' => 'civicrm/contribute/unsubscribe?reset=1&cid=[contact_id]&mid=[id]&context=membership&selectedChild=member',
        'text' => ts('Cancel Auto-renewal'),
      ],
      'changebilling' => [
        'path' => 'civicrm/contribute/updatebilling?reset=1&cid=[contact_id]&mid=[id]&context=membership&selectedChild=member',
        'text' => ts('Change Billing Details'),
      ],
    ];
    foreach ($actions as $action => $values) {
      $addTemplate['ui_action'] = $action;
      $addTemplate['path'] = $values['path'];
      $addTemplate['text'] = $values['text'];
      $newLinks[] = $addTemplate;
    }
  }

  private static function unsetLinks(array &$links, array $actions) {
    foreach ($actions as $action) {
      $actionLinkIndex = self::getActionIndex($links, $action);
      unset($links[$actionLinkIndex]);
    }
  }

}
