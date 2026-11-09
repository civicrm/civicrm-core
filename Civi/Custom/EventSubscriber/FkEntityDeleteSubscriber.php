<?php
declare(strict_types = 1);

namespace Civi\Custom\EventSubscriber;

use Civi\Api4\Utils\CoreUtil;
use Civi\Core\Attribute\CiviEventListener;
use Civi\Core\Event\PreEvent;
use Civi\Schema\EntityRepository;

final class FkEntityDeleteSubscriber {

  #[CiviEventListener(event: 'hook_civicrm_pre')]
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
    $parent = CoreUtil::resolveFacade($fkEntity);

    // A facade (e.g. "Individual") should include its parent ("Contact")
    if ($parent !== $fkEntity) {
      return [$parent, $fkEntity];
    }

    // An entity (e.g. "Contact") should include all its facades (Individual, Organization, Household)
    return array_merge([$fkEntity], EntityRepository::getFacadesOf($fkEntity));
  }

}
