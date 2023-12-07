<?php
declare(strict_types = 1);

use Civi\Api4\CustomField;

final class CRM_Core_DAO_Helper_ContactReferenceField {

  /**
   * Add custom ContactReference fields to the list of contact references.
   *
   * This includes active and inactive fields/groups.
   *
   * @phpstan-param array<string, array<string>> $cidRefs
   *   Mapping of table name to column names.
   *
   * @throws \CRM_Core_Exception
   */
  public static function appendCustomContactEntityReferenceFields(array &$cidRefs): void {
    $fields = CustomField::get(FALSE)
      ->setSelect(['column_name', 'custom_group_id.table_name'])
      ->addWhere('data_type', '=', 'EntityReference')
      ->addWhere('fk_entity', 'IN', ['Contact', 'Household', 'Individual', 'Organization'])
      ->execute();
    foreach ($fields as $field) {
      $cidRefs[$field['custom_group_id.table_name']][] = $field['column_name'];
    }
  }

  /**
   * Add custom ContactReference fields to the list of contact references
   *
   * This includes active and inactive fields/groups
   *
   * @phpstan-param array<string, array<string>> $cidRefs
   *   Mapping of table name to column names.
   *
   * @throws \CRM_Core_Exception
   */
  public static function appendCustomContactReferenceFields(array &$cidRefs): void {
    $fields = civicrm_api3('CustomField', 'get', [
      'return' => ['column_name', 'custom_group_id.table_name'],
      'data_type' => 'ContactReference',
      'options' => ['limit' => 0],
    ])['values'];
    foreach ($fields as $field) {
      $cidRefs[$field['custom_group_id.table_name']][] = $field['column_name'];
    }
  }

  /**
   * Add custom tables that extend contacts to the list of contact references.
   *
   * CRM_Core_BAO_CustomGroup::getAllCustomGroupsByBaseEntity seems like a safe-ish
   * function to be sure all are retrieved & we don't miss subtypes or inactive or multiples
   * - the down side is it is not cached.
   *
   * Further changes should be include tests in the CRM_Core_MergerTest class
   * to ensure that disabled, subtype, multiple etc groups are still captured.
   *
   * @phpstan-param array<string, array<string>> $cidRefs
   *   Mapping of table name to column names.
   */
  public static function appendCustomTablesExtendingContacts(array &$cidRefs): void {
    $customValueTables = CRM_Core_BAO_CustomGroup::getAllCustomGroupsByBaseEntity('Contact');
    $customValueTables->find();
    while ($customValueTables->fetch()) {
      $cidRefs[$customValueTables->table_name][] = 'entity_id';
    }
  }

}
