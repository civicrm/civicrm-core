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
class ContactLinksProvider extends \Civi\Core\Service\AutoSubscriber {
  use LinksProviderTrait;

  public static function getSubscribedEvents(): array {
    return [
      'civi.api4.getLinks' => 'alterContactLinks',
      'civi.api.respond' => 'alterContactLinksResult',
    ];
  }

  public static function alterContactLinks(GenericHookEvent $e): void {
    if (CoreUtil::isContact($e->entity)) {
      foreach ($e->links as $index => $link) {
        // Contacts are too cumbersome to view in a popup
        if (in_array($link['ui_action'], ['add', 'view', 'update'], TRUE)) {
          $e->links[$index]['target'] = '';
        }
      }
    }
  }

  public static function alterContactLinksResult(RespondEvent $e): void {
    $request = $e->getApiRequest();
    if ($request['version'] == 4 && is_a($request, '\Civi\Api4\Action\GetLinks') && CoreUtil::isContact($request->getEntityName())) {
      $links = (array) $e->getResponse();
      $addLinkIndex = self::getActionIndex($links, 'add');
      // Unset the generic "add" link and replace it with links per contact-type and sub-type
      if (isset($addLinkIndex) && $request->getExpandMultiple()) {
        // Use the single add link from the schema as a template
        $addTemplate = $links[$addLinkIndex];
        $newLinks = [];
        $contactType = $request->getValue('contact_type') ?: $request->getEntityName();
        // For contact entity, add links for every contact type
        if ($contactType === 'Contact') {
          foreach (\CRM_Contact_BAO_ContactType::basicTypes() as $type) {
            self::addLinks($newLinks, $type, $addTemplate, $request->getEntityName());
          }
        }
        // For Individual, Organization, Household entity
        else {
          self::addLinks($newLinks, $contactType, $addTemplate, $request->getEntityName());
        }
        array_splice($links, $addLinkIndex, 1, $newLinks);
      }
      $e->getResponse()->exchangeArray(array_values($links));
    }
  }

  private static function addLinks(array &$newLinks, string $contactType, array $addTemplate, string $apiEntity) {
    // Since this runs after api processing, not all fields may be returned,
    // depending on the SELECT clause, so avoid undefined indexes.
    if (!empty($addTemplate['path'])) {
      $addTemplate['path'] = str_replace('[contact_type]', $contactType, $addTemplate['path']);
    }
    // Link to contact type
    if ($apiEntity === 'Contact') {
      $addTemplate['api_values'] = ['contact_type' => $contactType];
    }
    $link = $addTemplate;
    $link['text'] = ts('Add %1', [1 => \CRM_Contact_BAO_ContactType::getLabel($contactType)]);
    if (array_key_exists('icon', $addTemplate)) {
      $link['icon'] = CoreUtil::getInfoItem($contactType, 'icon');
    }
    $newLinks[] = $link;
    // Links to contact sub-types
    $subTypes = \CRM_Contact_BAO_ContactType::subTypeInfo($contactType);
    $labels = array_column($subTypes, 'label');
    array_multisort($labels, SORT_NATURAL, $subTypes);
    foreach ($subTypes as $subType) {
      if (isset($addTemplate['weight'])) {
        $addTemplate['weight']++;
      }
      $link = $addTemplate;
      if (!empty($link['path'])) {
        $link['path'] .= '&cst=' . $subType['name'];
      }
      if (array_key_exists('icon', $addTemplate)) {
        $link['icon'] = $subType['icon'] ?? $link['icon'];
      }
      $link['text'] = ts('Add %1', [1 => $subType['label']]);
      $link['api_values']['contact_sub_type'] = $subType['name'];
      $newLinks[] = $link;
    }
  }

}
