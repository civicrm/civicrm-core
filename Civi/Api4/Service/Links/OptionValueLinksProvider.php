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
class OptionValueLinksProvider extends \Civi\Core\Service\AutoSubscriber {
  use LinksProviderTrait;

  public static function getSubscribedEvents(): array {
    return [
      'civi.api.respond' => ['alterOptionValueLinksResult', -50],
    ];
  }

  /**
   * Customize OptionValue activity links
   *
   * @param \Civi\API\Event\RespondEvent $e
   * @return void
   * @throws \CRM_Core_Exception
   */
  public static function alterOptionValueLinksResult(RespondEvent $e): void {
    $request = $e->getApiRequest();
    if ($request['version'] == 4 && $request->getEntityName() === 'OptionValue' && is_a($request, '\Civi\Api4\Action\GetLinks')) {
      $optionValueId = $request->getValue('id');
      $optionGroupName = $request->getValue('option_group_id:name');
      if (!$optionGroupName && $optionValueId) {
        $optionGroupId = \CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue', 'option_group_id', $optionValueId, 'id');
        $optionGroupName = \CRM_Core_PseudoConstant::getName('CRM_Core_DAO_OptionGroup', 'option_group_id', $optionGroupId);
      }
      if (!$optionGroupName) {
        return;
      }
      $links = (array) $e->getResponse();
      $editLinkIndex = self::getActionIndex($links, 'update');
      $deleteLinkIndex = self::getActionIndex($links, 'delete');
      // Add link to manage OptionValue
      // /*$links[] = [
      //   'ui_action' => 'advanced',
      //   'api_action' => 'update',
      //   'api_values' => NULL,
      //   'entity' => 'OptionValue',
      //   'path' => "civicrm/contact/view/OptionValue?reset=1&id=$optionValueId&action=view",
      //   'text' => ts('Manage OptionValue'),
      //   'icon' => CoreUtil::getInfoItem('OptionValue', 'icon'),
      //   'weight' => 80,
      //   'target' => NULL,
      // ];*/
      $idToken = $optionValueId ?: '[id]';
      $optionValue = \Civi\Api4\OptionValue::get(FALSE)
        ->addSelect('value')
        ->addWhere('id', '=', $optionValueId)
        ->setLimit(1)
        ->execute()
        ->first();
      $optionValue = $optionValue['value'];
      //$optionValue = \CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue', 'value', $optionValueId, 'id');
      \Civi::log()->debug('CRM_Core_DAO_OptionValue -- ' . $optionValueId . ' -- ' . $optionValue);
      // Change view/edit/delete links depending on the option group
      if (isset($links[$editLinkIndex]['path'])) {
        if ($optionGroupName == 'event_badge') {
          $links[$editLinkIndex]['path'] = "civicrm/admin/badgelayout/add?action=update&id=$optionValue&reset=1";
        }
      }
      $e->getResponse()->exchangeArray(array_values($links));
    }
  }

}
