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
use Civi\Api4\Utils\CoreUtil;
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
      $caseId = $request->getValue('case_id');
      if (!$caseId && $activityId) {
        $caseId = \CRM_Core_DAO::getFieldValue('CRM_Case_DAO_CaseActivity', 'case_id', $activityId, 'activity_id');
      }
      if (!$caseId) {
        return;
      }
      $links = (array) $e->getResponse();
      $viewLinkIndex = self::getActionIndex($links, 'view');
      $editLinkIndex = self::getActionIndex($links, 'update');
      $deleteLinkIndex = self::getActionIndex($links, 'delete');
      // Add link to manage case
      $links[] = [
        'ui_action' => 'advanced',
        'api_action' => 'update',
        'api_values' => NULL,
        'entity' => 'Case',
        'path' => "civicrm/contact/view/case?reset=1&id=$caseId&action=view",
        'text' => ts('Manage Case'),
        'icon' => CoreUtil::getInfoItem('Case', 'icon'),
        'weight' => 80,
        'target' => NULL,
      ];
      $idToken = $activityId ?: '[id]';
      // Change view/edit/delete links to the CiviCase version
      if (isset($links[$viewLinkIndex]['path'])) {
        $links[$viewLinkIndex]['path'] = "civicrm/case/activity/view?reset=1&caseid=$caseId&aid=$idToken";
      }
      if (isset($links[$editLinkIndex]['path'])) {
        $links[$editLinkIndex]['path'] = "civicrm/case/activity?reset=1&caseid=$caseId&id=$idToken&action=update";
      }
      if (isset($links[$deleteLinkIndex]['path'])) {
        $links[$deleteLinkIndex]['path'] = "civicrm/case/activity?reset=1&caseid=$caseId&id=$idToken&action=delete";
      }
      $e->getResponse()->exchangeArray(array_values($links));
    }
  }

}
