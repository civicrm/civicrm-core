<?php
declare(strict_types = 1);

namespace Civi\Custom\EventSubscriber;

use Civi\Api4\CustomField;
use Civi\Api4\Generic\Result;
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
    foreach ($this->getCustomFields($fkEntity, $onDelete, $onlySoftDelete) as $customField) {
      yield $customField['custom_group_id.extends'] => $customField['custom_group_id.name'] . '.' . $customField['name'];
    }
  }

  private function getCustomFields(string $fkEntity, string $onDelete, bool $onlySoftDelete): Result {
    $action = CustomField::get(FALSE)
      ->setSelect([
        'custom_group_id.extends',
        'custom_group_id.name',
        'name',
      ])->addWhere('data_type', '=', 'EntityReference')
      ->addWhere('fk_entity_on_delete', '=', $onDelete);

    if (in_array($fkEntity, ['Individual', 'Organization', 'Household'], TRUE)) {
      $action->addWhere('fk_entity', 'IN', [$fkEntity, 'Contact']);
    }
    else {
      $action->addWhere('fk_entity', '=', $fkEntity);
    }

    if ($onlySoftDelete) {
      $action->addWhere('is_on_delete_includes_soft_delete', '=', TRUE);
    }

    return $action->execute();
  }

}
