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
    ];
  }

  public static function alterContactLinks(GenericHookEvent $e): void {
    if (CoreUtil::isContact($e->entity)) {
      foreach ($e->links as $index => $link) {
        // Contacts are too cumbersome to view in a popup
        if (in_array($link['ui_action'], ['view', 'update'], TRUE)) {
          $e->links[$index]['target'] = '';
        }
      }
      // Unset the generic "add" link and replace it with links per contact-type and sub-type
      $addLinkIndex = self::getActionIndex($e->links, 'add');
      if ($addLinkIndex !== NULL) {
        $addTemplate = $e->links[$addLinkIndex];
        unset($e->links[$addLinkIndex]);
        // For contact entity, add links for every contact type
        if ($e->entity === 'Contact') {
          foreach (\CRM_Contact_BAO_ContactType::basicTypes() as $contactType) {
            self::addLinks($contactType, $addTemplate, $e);
          }
        }
        // For Individual, Organization, Household entity
        else {
          self::addLinks($e->entity, $addTemplate, $e);
        }
      }
    }
  }

  private static function addLinks(string $contactType, array $addTemplate, GenericHookEvent $e) {
    $addTemplate['path'] = str_replace('[contact_type]', $contactType, $addTemplate['path']);
    $link = $addTemplate;
    if ($e->entity === 'Contact') {
      $link['text'] = str_replace('%1', CoreUtil::getInfoItem($contactType, 'title'), $link['text']);
      $link['api_values'] = ['contact_type' => $contactType];
      $link['icon'] = CoreUtil::getInfoItem($contactType, 'icon');
    }
    $e->links[] = $link;
    $subTypes = \CRM_Contact_BAO_ContactType::subTypeInfo($contactType);
    $labels = array_column($subTypes, 'label');
    array_multisort($labels, SORT_NATURAL, $subTypes);
    foreach ($subTypes as $subType) {
      $addTemplate['weight']++;
      $link = $addTemplate;
      $link['path'] .= '&cst=' . $subType['name'];
      $link['icon'] = $subType['icon'] ?? $link['icon'];
      $link['text'] = str_replace('%1', $subType['label'], $link['text']);
      $link['api_values'] = ['contact_sub_type' => $subType['name']];
      $e->links[] = $link;
    }
  }

}
