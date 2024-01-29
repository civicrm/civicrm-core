<?php
declare(strict_types = 1);

namespace Civi\Custom\EventSubscriber;

use Civi\Core\Event\PreEvent;
use Civi\Core\Service\AutoSubscriber;

final class FkEntityDeleteSubscriber extends AutoSubscriber {

  public static function getSubscribedEvents(): array {
    return [
      'hook_civicrm_pre' => 'onPre',
    ];
  }

  public function onPre(PreEvent $event): void {
    if ('delete' === $event->action) {
      $this->performCascadeDeletions($event->entity, (int) $event->id, FALSE);
    }
    elseif ($event->params['is_deleted'] ?? FALSE) {
      $this->performCascadeDeletions($event->entity, (int) $event->id, TRUE);
      // Set NULL on real deletion is part of the database schema.
      $this->performSetNull($event->entity, (int) $event->id);
    }
  }

  private function performCascadeDeletions(string $fkEntity, int $id, bool $softDelete): void {
    foreach ($this->getReferenceFieldNames($fkEntity, 'cascade', $softDelete) as $entity => $referenceFieldName) {
      civicrm_api4($entity, 'delete', [
        'where' => [[$referenceFieldName, '=', $id]],
      ]);
    }
  }

  private function performSetNull(string $fkEntity, int $id): void {
    foreach ($this->getReferenceFieldNames($fkEntity, 'set_null', TRUE) as $entity => $referenceFieldName) {
      civicrm_api4($entity, 'update', [
        'values' => [$referenceFieldName => NULL],
        'where' => [[$referenceFieldName, '=', $id]],
      ]);
    }
  }

  /**
   * @phpstan-return iterable<string, string>
   *   Entity name mapped to custom entity reference field name.
   */
  private function getReferenceFieldNames(string $fkEntity, string $onDelete, bool $onlySoftDelete): iterable {
    $fkEntities = $this->getFkEntityTypes($fkEntity);

    foreach (\CRM_Core_BAO_CustomGroup::getAll() as $group) {
      foreach ($group['fields'] as $field) {
        if ($field['data_type'] === 'EntityReference' && $field['fk_entity_on_delete'] === $onDelete
          && (!$onlySoftDelete || $field['is_on_delete_includes_soft_delete'])
          && in_array($field['fk_entity'], $fkEntities, TRUE)
        ) {
          yield $group['extends'] => $group['name'] . '.' . $field['name'];
        }
      }
    }
  }

  /**
   * @phpstan-return list<string>
   */
  private function getFkEntityTypes(string $fkEntity): array {
    $contactTypes = \CRM_Contact_BAO_ContactType::basicTypes(TRUE);

    // "Contact" should include all contact types (Individual, Organization, Household)
    if ($fkEntity === 'Contact') {
      return array_merge(['Contact'], $contactTypes);
    }

    // A contact type (e.g. "Individual") should include "Contact"
    if (in_array($fkEntity, $contactTypes, TRUE)) {
      return ['Contact', $fkEntity];
    }

    return [$fkEntity];
  }

}
