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

namespace Civi\ActionSchedule;

use Civi\Api4\Utils\CoreUtil;
use Civi\Core\Service\AutoSubscriber;

/**
 * Base implementation of MappingInterface.
 *
 * Extend this class to register a new type of ActionSchedule mapping.
 * Note: When choosing a value to return from `getId()`, use a "machine name" style string.
 */
abstract class MappingBase extends AutoSubscriber implements MappingInterface {

  public static function getSubscribedEvents(): array {
    return [
      'civi.actionSchedule.getMappings' => 'onRegisterActionMappings',
    ];
  }

  /**
   * Register this action mapping type with CRM_Core_BAO_ActionSchedule.
   *
   * @param \Civi\ActionSchedule\Event\MappingRegisterEvent $registrations
   */
  public static function onRegisterActionMappings(\Civi\ActionSchedule\Event\MappingRegisterEvent $registrations): void {
    $registrations->register(new static());
  }

  public function getEntityTable(): string {
    return \CRM_Core_DAO_AllCoreTables::getTableForEntityName($this->getEntityName());
  }

  /**
   * Deprecated ambiguously-named function.
   * @deprecated
   * @return string
   */
  public function getEntity(): string {
    \CRM_Core_Error::deprecatedFunctionWarning('getEntityTable');
    return $this->getEntityTable();
  }

  public function getLabel(): string {
    return CoreUtil::getInfoItem($this->getEntityName(), 'title') ?: ts('Unknown');
  }

  public function getValueHeader(): string {
    return $this->getLabel();
  }

  public function getRecipientListing($recipientType): array {
    return [];
  }

  public function getRecipientTypes(): array {
    return [];
  }

  public function validateSchedule($schedule): array {
    return [];
  }

  public function getDateFields(): array {
    return [];
  }

  public function resetOnTriggerDateChange($schedule): bool {
    return FALSE;
  }

  public function sendToAdditional($entityId): bool {
    return TRUE;
  }

}
