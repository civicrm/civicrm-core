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
    // Set NULL on real deletion is part of the database schema and does not
    // need to be handled here.
    if ('delete' === $event->action) {
      $this->performCascadeDeletions($event->entity, (int) $event->id);
    }
  }

  private function performCascadeDeletions(string $fkEntity, int $id): void {
    foreach ($this->getReferenceFieldNames($fkEntity, 'cascade') as $entity => $referenceFieldName) {
      civicrm_api4($entity, 'delete', [
        'where' => [[$referenceFieldName, '=', $id]],
        // Cascade deletion shall be performed independent of current user's permissions.
        'checkPermissions' => FALSE,
      ]);
    }
  }

  /**
   * @phpstan-return iterable<string, string>
   *   Entity name mapped to custom entity reference field name.
   */
  private function getReferenceFieldNames(string $fkEntity, string $onDelete): iterable {
    $fkEntities = $this->getFkEntityTypes($fkEntity);

    foreach (\CRM_Core_BAO_CustomGroup::getAll() as $group) {
      foreach ($group['fields'] as $field) {
        if ($field['data_type'] === 'EntityReference' && $field['fk_entity_on_delete'] === $onDelete
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
