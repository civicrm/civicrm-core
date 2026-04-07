<?php

/**
 * @package CiviCRM
 * @group headless
 */
class CRM_Utils_Check_SchemaTest extends CiviUnitTestCase {
  use \Civi\Test\Api4TestTrait;

  /**
   * File check test should fail if reached maximum timeout.
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function testCheckSmartGroupCustomFieldCriteria(): void {
    $customGroup = $this->createTestRecord('CustomGroup');

    $cfId = $this->saveTestRecords('CustomField', [
      'records' => [
        ['label' => 'Field was deleted'],
        ['label' => 'Field is active'],
        ['label' => 'Field is disabled', 'is_active' => 0],
      ],
      'defaults' => ['custom_group_id' => $customGroup['id']],
    ])->column('id');

    // Delete field 0
    \Civi\Api4\CustomField::delete(FALSE)
      ->addWhere('id', '=', $cfId[0])
      ->execute();

    $savedSearches = $this->saveTestRecords('SavedSearch', [
      'records' => [
        [
          'label' => 'SavedSearch With Deleted Custom Field',
          'form_values' => [
            "custom_{$cfId[0]}" => '123',
            "custom_{$cfId[1]}" => '456',
          ],
        ],
        [
          'label' => 'SavedSearch With No Problems',
          'form_values' => [
            'first_name' => 'John',
            "custom_{$cfId[1]}" => '456',
          ],
        ],
        [
          'label' => 'SavedSearch With Inactive Custom Field',
          'form_values' => [
            'first_name' => 'John',
            "custom_{$cfId[2]}" => '123',
          ],
        ],
      ],
    ]);

    foreach ($savedSearches as $savedSearch) {
      $this->createTestRecord('Group', [
        'title' => str_replace('SavedSearch', 'Group', $savedSearch['label']),
        'saved_search_id' => $savedSearch['id'],
      ]);
    }

    $check = new CRM_Utils_Check_Component_Schema();
    $checkResult = $check->checkSmartGroupCustomFieldCriteria();
    $this->assertCount(1, $checkResult);
    $message = $checkResult[0]->getMessage();

    $this->assertStringContainsString('Group With Deleted Custom Field', $message);
    $this->assertStringContainsString('Group With Inactive Custom Field', $message);
    $this->assertStringContainsString('Field is disabled (disabled)', $message);
    $this->assertStringContainsString('Deleted - Field ID ' . $cfId[0], $message);
    $this->assertStringNotContainsString('Field was deleted', $message);
    $this->assertStringNotContainsString('Field is active', $message);
    $this->assertStringNotContainsString('Group With No Problems', $message);
  }

}
