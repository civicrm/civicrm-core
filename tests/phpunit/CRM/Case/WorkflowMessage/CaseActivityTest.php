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

/**
 * Class CRM_Case_WorkflowMessage_CaseActivityTest
 */
class CRM_Case_WorkflowMessage_CaseActivityTest extends CiviUnitTestCase {
  use \Civi\Test\WorkflowMessageTestTrait;

  public function getWorkflowClass(): string {
    return CRM_Case_WorkflowMessage_CaseActivity::class;
  }

  public function testAdhocClassEquiv() {
    $examples = \Civi\Api4\ExampleData::get(0)
      ->setSelect(['name', 'data'])
      ->addWhere('name', 'IN', ['workflow/case_activity/CaseAdhocExample', 'workflow/case_activity/CaseModelExample'])
      ->execute()
      ->indexBy('name')
      ->column('data');
    $byAdhoc = Civi\WorkflowMessage\WorkflowMessage::create('case_activity', $examples['workflow/case_activity/CaseAdhocExample']);
    $byClass = new CRM_Case_WorkflowMessage_CaseActivity($examples['workflow/case_activity/CaseModelExample']);
    $this->assertSameWorkflowMessage($byClass, $byAdhoc, 'Compare byClass and byAdhoc: ');
  }

  /**
   * Ensure that various methods of constructing a WorkflowMessage all produce similar results.
   *
   * To see this, we take all the example data and use it with diff constructors.
   */
  public function testConstructorEquivalence() {
    $examples = $this->findExamples()->execute()->indexBy('name')->column('data');
    $this->assertTrue(count($examples) >= 1, 'Must have at least one example data-set');
    foreach ($examples as $example) {
      $this->assertConstructorEquivalence($example);
    }
  }

  /**
   * Basic canary test fetching a specific example.
   *
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testExampleGet() {
    $file = \Civi::paths()->getPath('[civicrm.root]/tests/phpunit/CRM/Case/WorkflowMessage/CaseActivity/CaseModelExample.ex.php');
    $name = 'workflow/case_activity/CaseModelExample';

    $this->assertTrue(file_exists($file), "Expect find canary file ($file)");

    $get = \Civi\Api4\ExampleData::get()
      ->addWhere('name', '=', $name)
      ->execute()
      ->single();
    $this->assertEquals($name, $get['name']);
    $this->assertTrue(!isset($get['data']));
    $this->assertTrue(!isset($get['asserts']));

    $get = \Civi\Api4\ExampleData::get()
      ->addWhere('name', '=', $name)
      ->addSelect('data')
      ->execute()
      ->single();
    $this->assertEquals($name, $get['name']);
    $this->assertEquals(100, $get['data']['modelProps']['contact']['contact_id']);
    $this->assertEquals('myrole', $get['data']['modelProps']['contact']['role']);
  }

}
