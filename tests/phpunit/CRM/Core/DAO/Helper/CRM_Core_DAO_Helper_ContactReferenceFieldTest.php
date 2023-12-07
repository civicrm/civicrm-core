<?php
declare(strict_types = 1);

use Civi\Api4\CustomField;
use Civi\Api4\CustomGroup;

/**
 * @covers \CRM_Core_DAO_Helper_ContactReferenceField
 *
 * @group headless
 */
final class CRM_Core_DAO_Helper_ContactReferenceFieldTest extends CiviUnitTestCase {

  public function testAppendCustomContactEntityReferenceFields(): void {
    $customGroup1 = CustomGroup::create()
      ->setValues([
        'name' => 'cg1',
        'title' => 'Custom Group',
        'extends' => 'Group',
      ])
      ->execute()
      ->single();
    $customGroup2 = CustomGroup::create()
      ->setValues([
        'name' => 'cg2',
        'title' => 'Custom Group',
        'extends' => 'Contact',
      ])
      ->execute()
      ->single();

    $contactCustomField = $this->createCustomField($customGroup1['id'], 'Contact');
    $householdCustomField = $this->createCustomField($customGroup1['id'], 'Household');
    $individualCustomField = $this->createCustomField($customGroup2['id'], 'Individual');
    $organizationCustomField = $this->createCustomField($customGroup2['id'], 'Organization');
    $groupCustomField = $this->createCustomField($customGroup2['id'], 'Group');

    $expectedCidRefs = [
      $customGroup1['table_name'] => [
        $contactCustomField['column_name'],
        $householdCustomField['column_name'],
      ],
      $customGroup2['table_name'] => [
        $individualCustomField['column_name'],
        $organizationCustomField['column_name'],
      ],
    ];
    $cidRefs = [];
    CRM_Core_DAO_Helper_ContactReferenceField::appendCustomContactEntityReferenceFields($cidRefs);
    static::assertEquals($expectedCidRefs, $cidRefs);

    // cleanup
    $this->callAPISuccess('CustomField', 'delete', ['id' => $contactCustomField['id']]);
    $this->callAPISuccess('CustomField', 'delete', ['id' => $householdCustomField['id']]);
    $this->callAPISuccess('CustomField', 'delete', ['id' => $individualCustomField['id']]);
    $this->callAPISuccess('CustomField', 'delete', ['id' => $organizationCustomField['id']]);
    $this->callAPISuccess('CustomField', 'delete', ['id' => $groupCustomField['id']]);
    $this->callAPISuccess('CustomGroup', 'delete', ['id' => $customGroup1['id']]);
    $this->callAPISuccess('CustomGroup', 'delete', ['id' => $customGroup2['id']]);
  }

  private function createCustomField(int $customGroupId, string $fkEntity): array {
    static $count = 0;
    ++$count;

    return CustomField::create(FALSE)
      ->setValues([
        'custom_group_id' => $customGroupId,
        'name' => 'field' . $count,
        'data_type' => 'EntityReference',
        'fk_entity' => $fkEntity,
        'label' => 'Field' . $count,
        'html_type' => 'Select',
      ])
      ->execute()
      ->single();
  }

}
