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

namespace Civi\Api4\Provider;

use Civi\Api4\CustomValue;
use Civi\Core\Event\GenericHookEvent;
use Civi\Core\Service\AutoService;
use Civi\Schema\EntityRepository;
use CRM_Core_BAO_CustomGroup;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @service
 * @internal
 */
class CustomEntityProvider extends AutoService implements EventSubscriberInterface {

  /**
   * @return array
   */
  public static function getSubscribedEvents(): array {
    return [
      'civi.api4.entityTypes' => ['addCustomEntities', 100],
    ];
  }

  /**
   * Get custom-field pseudo-entities
   */
  public static function addCustomEntities(GenericHookEvent $e) {
    $baseInfo = CustomValue::getInfo();
    foreach (\CRM_Core_BAO_CustomGroup::getAll() as $customGroup) {
      if (empty($customGroup['is_multiple']) || empty($customGroup['is_active'])) {
        continue;
      }
      $entityName = 'Custom_' . $customGroup['name'];
      $baseEntity = CRM_Core_BAO_CustomGroup::getEntityFromExtends($customGroup['extends']);
      if (!$baseEntity || !EntityRepository::entityExists($baseEntity)) {
        continue;
      }
      // Lookup base entity title without CoreUtil to avoid early-bootstrap issues
      $baseEntityTitle = \Civi::entity($baseEntity)->getMeta('title_plural') ?: \Civi::entity($baseEntity)->getMeta('title');
      $e->entities[$entityName] = [
        'name' => $entityName,
        'title' => $customGroup['title'],
        'title_plural' => $customGroup['title'],
        'table_name' => $customGroup['table_name'],
        'class_args' => [$customGroup['name']],
        'description' => ts('Custom group for %1', [1 => $baseEntityTitle]),
        'paths' => [
          'view' => "civicrm/contact/view/cd?reset=1&gid={$customGroup['id']}&recId=[id]&multiRecordDisplay=single",
        ],
      ] + $baseInfo;
      if (!empty($customGroup['icon'])) {
        $e->entities[$entityName]['icon'] = $customGroup['icon'];
      }
      if (!empty($customGroup['help_pre'])) {
        $e->entities[$entityName]['comment'] = self::plainTextify($customGroup['help_pre']);
      }
      if (!empty($customGroup['help_post'])) {
        $pre = empty($e->entities[$entityName]['comment']) ? '' : $e->entities[$entityName]['comment'] . "\n\n";
        $e->entities[$entityName]['comment'] = $pre . self::plainTextify($customGroup['help_post']);
      }
    }
  }

  /**
   * Convert html to plain text.
   *
   * @param $input
   * @return mixed
   */
  private static function plainTextify($input) {
    return html_entity_decode(strip_tags($input), ENT_QUOTES | ENT_HTML5, 'UTF-8');
  }

}
