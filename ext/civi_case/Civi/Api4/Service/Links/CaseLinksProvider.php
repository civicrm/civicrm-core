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
use Civi\Core\Event\GenericHookEvent;

/**
 * @service
 * @internal
 */
class CaseLinksProvider extends \Civi\Core\Service\AutoSubscriber {
  use LinksProviderTrait;

  public static function getSubscribedEvents(): array {
    return [
      'civi.api4.getLinks' => 'alterCaseLinks',
      'civi.api.respond' => ['alterActivityLinksResult', -50],
    ];
  }

  /**
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @return void
   */
  public static function alterCaseLinks(GenericHookEvent $e): void {
    // Tweak case view/edit links
    if ($e->entity === 'Case') {
      foreach ($e->links as $index => $link) {
        // Cases are too cumbersome to view in a popup
        if (in_array($link['ui_action'], ['view', 'update'], TRUE)) {
          $e->links[$index]['target'] = '';
        }
      }
    }
  }

  /**
   * Customize case activity links
   *
   * @param \Civi\API\Event\RespondEvent $e
   * @return void
   * @throws \CRM_Core_Exception
   */
  public static function alterActivityLinksResult(RespondEvent $e): void {
    $request = $e->getApiRequest();
    if ($request['version'] == 4 && $request->getEntityName() === 'Activity' && is_a($request, '\Civi\Api4\Action\GetLinks')) {
      $activityId = $request->getValue('id');
      $values = $request->getValues();
      // These are the most common ways case_id might come through a SK query
      if (array_key_exists('case_id', $values) || array_key_exists('Activity_CaseActivity_Case_01.id', $values)) {
        $caseId = $values['case_id'] ?? $values['Activity_CaseActivity_Case_01.id'];
      }
      // If case id not present in query, look it up.
      elseif ($activityId) {
        $caseId = \CRM_Core_DAO::getFieldValue('CRM_Case_DAO_CaseActivity', $activityId, 'case_id', 'activity_id');
      }
      $links = (array) $e->getResponse();
      if (!isset($caseId)) {
        return;
      }
      $viewLinkIndex = self::getActionIndex($links, 'view');
      $editLinkIndex = self::getActionIndex($links, 'update');
      $deleteLinkIndex = self::getActionIndex($links, 'delete');

      $idToken = $activityId ?: '[id]';
      // Change view/edit/delete links to the CiviCase version
      if (isset($viewLinkIndex, $links[$viewLinkIndex]['path'])) {
        $links[$viewLinkIndex]['path'] = "civicrm/case/activity/view?reset=1&caseid=$caseId&aid=$idToken";
      }
      if (isset($editLinkIndex, $links[$editLinkIndex]['path'])) {
        $links[$editLinkIndex]['path'] = "civicrm/case/activity?reset=1&caseid=$caseId&id=$idToken&action=update";
      }
      if (isset($deleteLinkIndex, $links[$deleteLinkIndex]['path'])) {
        $links[$deleteLinkIndex]['path'] = "civicrm/case/activity?reset=1&caseid=$caseId&id=$idToken&action=delete";
      }
      // Suppress links for special case activity types
      $activityTypeId = $request->getValue('activity_type_id');
      // Lookup activity type from id
      if (!$activityTypeId && $request->getValue('id')) {
        $activityTypeId = \CRM_Core_DAO::getFieldValue('CRM_Activity_DAO_Activity', $request->getValue('id'), 'activity_type_id');
      }
      if ($activityTypeId) {
        foreach (['view' => $viewLinkIndex, 'edit' => $editLinkIndex, 'delete' => $deleteLinkIndex] as $caseAction => $linkIndex) {
          if (isset($linkIndex, $links[$linkIndex]['path']) && !\CRM_Case_BAO_Case::isActionAllowedForActivityType($activityTypeId, $caseAction)) {
            unset($links[$linkIndex]);
          }
        }
      }
      $e->getResponse()->exchangeArray(array_values($links));
    }
  }

}
