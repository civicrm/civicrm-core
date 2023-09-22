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

  public function getId() {
    return $this->getName();
  }

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

  public function getEntityTable(\CRM_Core_DAO_ActionSchedule $actionSchedule): string {
    return \CRM_Core_DAO_AllCoreTables::getTableForEntityName($this->getEntityName());
  }

  /**
   * Deprecated ambiguously-named function.
   * @deprecated
   * @return string
   */
  public function getEntity(): string {
    \CRM_Core_Error::deprecatedFunctionWarning('getEntityTable');
    return \CRM_Core_DAO_AllCoreTables::getTableForEntityName($this->getEntityName());
  }

  public function getLabel(): string {
    return CoreUtil::getInfoItem($this->getEntityName(), 'title') ?: ts('Unknown');
  }

  public static function getLimitToOptions(): array {
    return [
      [
        'id' => 1,
        'name' => 'limit',
        'label' => ts('Limit to'),
      ],
      [
        'id' => 2,
        'name' => 'add',
        'label' => ts('Also include'),
      ],
    ];
  }

  public function getRecipientListing($recipientType): array {
    return [];
  }

  public static function getRecipientTypes(): array {
    return [
      'manual' => ts('Choose Recipient(s)'),
      'group' => ts('Select Group'),
    ];
  }

  public function checkAccess(array $entityValue): bool {
    return FALSE;
  }

  public function getDateFields(?array $entityValue = NULL): array {
    return [];
  }

  public function resetOnTriggerDateChange($schedule): bool {
    return FALSE;
  }

  public function sendToAdditional($entityId): bool {
    return TRUE;
  }

  final public function applies(string $entity, string $action, array $values = []) {
    return $entity === 'ActionSchedule' &&
      in_array($action, ['create', 'get', 'update', 'save'], TRUE) &&
      $this->getId() == ($values['mapping_id'] ?? NULL);
  }

}
