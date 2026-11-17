<?php
use Civi\Api4\Event\AuthorizeRecordEvent;
use Civi\Core\Attribute\CiviEventListener;
use CRM_Afform_ExtensionUtil as E;

class CRM_Afform_BAO_SearchParamSet extends CRM_Afform_DAO_SearchParamSet implements \Civi\Core\HookInterface {
  use CRM_Core_OwnershipAccessTrait;

  protected static function getOwnerContactIdField(): string {
    return 'created_by';
  }

  protected static function checkOwnerAdminPermission(?int $userID = NULL): bool {
    return CRM_Core_Permission::check([['administer afform', 'administer CiviCRM data']], $userID);
  }

  /**
   * Names of the forms the current user may open.
   *
   * @return string[]
   */
  private static function getAccessibleFormNames(): array {
    return \Civi\Api4\Afform::get(TRUE)->addSelect('name')->execute()->column('name');
  }

  /**
   * Parameter sets are listed only for forms the user may open.
   */
  public function addSelectWhereClause(?string $entityName = NULL, ?int $userId = NULL, array $conditions = []): array {
    $clauses = parent::addSelectWhereClause($entityName, $userId, $conditions);
    $formNames = array_map(fn($name) => '"' . CRM_Core_DAO::escapeString($name) . '"', self::getAccessibleFormNames());
    $clauses['afform_name'][] = 'IN (' . ($formNames ? implode(', ', $formNames) : 'NULL') . ')';
    return $clauses;
  }

  /**
   * A parameter set is written only by someone who may open its form.
   */
  public static function self_civi_api4_authorizeRecord(AuthorizeRecordEvent $e): void {
    $record = $e->getRecord();
    $formNames = array_filter([
      $record['afform_name'] ?? NULL,
      empty($record['id']) ? NULL : CRM_Core_DAO::getFieldValue(self::class, $record['id'], 'afform_name'),
    ]);
    if (array_diff($formNames, self::getAccessibleFormNames())) {
      $e->setAuthorized(FALSE);
      $e->stopPropagation();
    }
  }

}
