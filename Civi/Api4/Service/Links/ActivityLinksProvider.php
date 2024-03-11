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
use Civi\Api4\OptionValue;

/**
 * @service
 * @internal
 */
class ActivityLinksProvider extends \Civi\Core\Service\AutoSubscriber {
  use LinksProviderTrait;

  public static function getSubscribedEvents(): array {
    return [
      'civi.api.respond' => 'alterActivityLinksResult',
    ];
  }

  public static function alterActivityLinksResult(RespondEvent $e): void {
    $request = $e->getApiRequest();
    if ($request['version'] == 4 && $request->getEntityName() === 'Activity' && is_a($request, '\Civi\Api4\Action\GetLinks')) {
      $links = (array) $e->getResponse();
      $addLinkIndex = self::getActionIndex($links, 'add');
      $editLinkIndex = self::getActionIndex($links, 'update');
      $deleteLinkIndex = self::getActionIndex($links, 'delete');
      // Expand the "add" link to multiple activity types if it exists (otherwise the WHERE clause excluded it and we should too)
      if ($request->getExpandMultiple() && isset($addLinkIndex)) {
        // Expanding the "add" link requires a value for target_contact.
        // This might come back from SearchKit in a couple different ways,
        // either an implicit join on 'target_contact_id' or as an explicit join.
        $targetContactId = $request->getValue('target_contact_id');
        foreach ($request->getValues() as $valueKey => $value) {
          if (!$targetContactId && is_numeric($value) && preg_match('/^Activity_ActivityContact_Contact_\d\d\.id$/', $valueKey)) {
            $targetContactId = $value;
          }
        }
        if ($targetContactId) {
          // Ensure links contain exactly the return values requested in the SELECT clause
          $addLinks = self::getActivityTypeAddLinks($targetContactId, $request->getCheckPermissions());
          foreach ($addLinks as &$addLink) {
            $addLink += $links[$addLinkIndex];
            $addLink = array_intersect_key($addLink, $links[$addLinkIndex]);
          }
          // Replace the one generic "add" link with multiple per-activity-type links
          array_splice($links, $addLinkIndex, 1, $addLinks);
        }
      }
      // With an activity type provided, alter path of edit links appropriately
      $activityType = $request->getValue('activity_type_id:name');
      $activityId = $request->getValue('id');
      // Lookup activity type from id
      if (!$activityType && $activityId) {
        $activityTypeId = \CRM_Core_DAO::getFieldValue('CRM_Activity_DAO_Activity', $activityId, 'activity_type_id');
        $activityType = \CRM_Core_PseudoConstant::getName('CRM_Activity_DAO_Activity', 'activity_type_id', $activityTypeId);
      }
      if ($activityType) {
        $viewOnlyTypes = \CRM_Activity_BAO_Activity::getViewOnlyActivityTypeIDs($request->getCheckPermissions());
        // Remove edit & delete links for "view only" types
        if (isset($viewOnlyTypes[$activityType])) {
          unset($links[$editLinkIndex], $links[$deleteLinkIndex]);
        }
      }
      $e->getResponse()->exchangeArray(array_values($links));
    }
  }

  private static function getActivityTypeAddLinks($contactId, $checkPermissions): array {
    $addLinks = [];
    $activityTypeQuery = OptionValue::get(FALSE)
      ->addSelect('name', 'label', 'icon', 'value', 'filter', 'component_id')
      ->addWhere('option_group_id:name', '=', 'activity_type')
      ->addWhere('is_active', '=', TRUE)
      ->addOrderBy('weight');

    // TODO: Code block was moved from CRM_Activity_Form_ActivityLinks and could use further cleanup
    $urlParams = "action=add&reset=1&cid={$contactId}&selectedChild=activity&atype=";
    foreach ($activityTypeQuery->execute() as $act) {
      $url = 'civicrm/activity/add';
      if ($act['name'] === 'Email') {
        if (!\CRM_Utils_Mail::validOutBoundMail()) {
          continue;
        }
        [, $email, $doNotEmail, , $isDeceased] = \CRM_Contact_BAO_Contact::getContactDetails($contactId);
        if (!$doNotEmail && $email && !$isDeceased) {
          $url = 'civicrm/activity/email/add';
          $act['label'] = ts('Send an Email');
        }
        else {
          continue;
        }
      }
      elseif ($act['name'] === 'SMS') {
        if (!\CRM_SMS_BAO_SmsProvider::activeProviderCount() ||
          ($checkPermissions && !\CRM_Core_Permission::check('send SMS'))
        ) {
          continue;
        }
        // Check for existence of a mobile phone and ! do not SMS privacy setting
        try {
          $phone = civicrm_api3('Phone', 'getsingle', [
            'contact_id' => $contactId,
            'phone_type_id' => \CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Phone', 'phone_type_id', 'Mobile'),
            'return' => ['phone', 'contact_id'],
            'options' => ['limit' => 1, 'sort' => "is_primary DESC"],
            'api.Contact.getsingle' => [
              'id' => '$value.contact_id',
              'return' => 'do_not_sms',
            ],
          ]);
        }
        catch (\CRM_Core_Exception $e) {
          continue;
        }
        if (!$phone['api.Contact.getsingle']['do_not_sms'] && $phone['phone']) {
          $url = 'civicrm/activity/sms/add';
        }
        else {
          continue;
        }
      }
      elseif ($act['name'] == 'Print PDF Letter') {
        $url = 'civicrm/activity/pdf/add';
      }
      elseif (!empty($act['filter']) || (!empty($act['component_id']) && $act['component_id'] != '1')) {
        continue;
      }

      $act['icon'] = $act['icon'] ?? 'fa-plus-square-o';
      $act['path'] = "$url?$urlParams{$act['value']}";
      $act['text'] = $act['label'];
      $addLinks[] = $act;
    }

    return $addLinks;
  }

}
