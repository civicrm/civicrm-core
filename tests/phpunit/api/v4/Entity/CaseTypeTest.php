<?php

namespace api\v4\Entity;

use api\v4\Api4TestBase;
use Civi\Api4\Activity;
use Civi\Api4\CaseType;
use Civi\Api4\CiviCase;

/**
 * @group headless
 */
class CaseTypeTest extends Api4TestBase {

  public function setUp(): void {
    parent::setUp();
    \CRM_Core_Dao::executeQuery('DELETE FROM civicrm_case_type WHERE id > 2');
    \CRM_Core_BAO_ConfigSetting::enableComponent('CiviCase');
  }

  public function testGetCaseTypeDefinition(): void {
    $caseType = CaseType::get(FALSE)
      ->addSelect('definition')
      ->addWhere('name', '=', 'housing_support')
      ->execute()->single();

    $this->assertEquals('Open Case', $caseType['definition']['activityTypes'][0]['name']);
  }

  public function testGetCaseTypeDefinitionViaJoin(): void {
    $case = $this->createTestRecord('Case', [
      'case_type_id:name' => 'housing_support',
    ]);
    $activity = $this->createTestRecord('Activity', [
      'case_id' => $case['id'],
    ]);
    $get = Activity::get(FALSE)
      ->addJoin('Case AS case', 'INNER', 'CaseActivity', ['id', '=', 'case.activity_id'])
      // Only select caseType.definition, no other caseType fields to test the fallback in CaseTypeGetSpecProvider
      ->addSelect('case.case_type_id.definition')
      ->addWhere('id', '=', $activity['id'])
      ->execute()->first();

    $definition = $get['case.case_type_id.definition'];
    $this->assertEquals('Open Case', $definition['activityTypes'][0]['name']);
  }

  public function testGetStatusIdPerCaseType(): void {
    $this->createTestRecord('OptionValue', [
      'option_group_id:name' => 'case_status',
      'label' => 'Testing',
      'name' => 'Testing',
      'grouping' => 'Opened',
    ]);

    $caseType = $this->createTestRecord('CaseType', [
      'title' => 'Test Case Type',
      'name' => 'test_case_type2',
      'definition' => [
        'statuses' => ['Testing', 'Closed'],
      ],
    ]);

    // Ensure saved xml is well-formed
    $dbDefinition = \CRM_Core_DAO::singleValueQuery('SELECT definition FROM civicrm_case_type WHERE id = ' . $caseType['id']);
    $this->assertStringContainsString('<CaseType>', $dbDefinition);
    $this->assertStringNotContainsString('&lt;', $dbDefinition);
    $this->assertStringNotContainsString('&gt;', $dbDefinition);

    $field = CiviCase::getFields(FALSE)
      ->setLoadOptions(['id', 'label', 'name'])
      ->addValue('case_type_id:name', 'test_case_type2')
      ->addWhere('name', '=', 'status_id')
      ->execute()
      ->first();
    $options = array_column($field['options'], 'name');

    $this->assertEquals(['Closed', 'Testing'], $options);
  }

  public function testCgExtendsObjects(): void {
    $this->createTestRecord('CaseType', [
      'title' => 'Test Case Type',
      'name' => 'test_case_type1',
    ]);

    $field = \Civi\Api4\CustomGroup::getFields(FALSE)
      ->setLoadOptions(TRUE)
      ->addValue('extends', 'Case')
      ->addWhere('name', '=', 'extends_entity_column_value')
      ->execute()
      ->first();

    $this->assertContains('Test Case Type', $field['options']);
  }

  public function testCreateWithXmlString(): void {
    $xmlString = '<CaseType><name>TestXmlString</name><ActivityTypes><ActivityType><name>Open Case</name></ActivityType></ActivityTypes></CaseType>';

    $id = civicrm_api4('CaseType', 'create', [
      'checkPermissions' => FALSE,
      'values' => [
        'name' => 'TestXmlString',
        'title' => 'Test XML String',
        'definition' => $xmlString,
      ],
    ])->single()['id'];

    $dbDefinition = \CRM_Core_DAO::singleValueQuery('SELECT definition FROM civicrm_case_type WHERE id = ' . $id);
    $this->assertStringContainsString('<CaseType>', $dbDefinition);
    $this->assertStringNotContainsString('&lt;', $dbDefinition);
    $this->assertStringNotContainsString('&gt;', $dbDefinition);

    $caseType = CaseType::get(FALSE)
      ->addSelect('definition')
      ->addWhere('id', '=', $id)
      ->execute()->single();

    $this->assertEquals('Open Case', $caseType['definition']['activityTypes'][0]['name']);
  }

}
