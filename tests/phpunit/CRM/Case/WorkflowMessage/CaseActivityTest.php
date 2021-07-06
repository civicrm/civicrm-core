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
    $examples = \Civi\Api4\WorkflowMessageExample::get(0)
      ->setSelect(['name', 'data'])
      ->addWhere('name', 'IN', ['case_activity.adhoc_1', 'case_activity.class_1'])
      ->execute()
      ->indexBy('name')
      ->column('data');
    $byAdhoc = Civi\WorkflowMessage\WorkflowMessage::create('case_activity', $examples['case_activity.adhoc_1']);
    $byClass = new CRM_Case_WorkflowMessage_CaseActivity($examples['case_activity.class_1']);
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
    $file = \Civi::paths()->getPath('[civicrm.root]/CRM/Case/WorkflowMessage/CaseActivity/class_1.ex.php');
    $workflow = 'case_activity';
    $name = 'case_activity.class_1';

    $this->assertTrue(file_exists($file), "Expect find canary file ($file)");

    $get = \Civi\Api4\WorkflowMessageExample::get()
      ->addWhere('name', '=', $name)
      ->execute()
      ->single();
    $this->assertEquals($workflow, $get['workflow']);
    $this->assertTrue(!isset($get['data']));
    $this->assertTrue(!isset($get['asserts']));

    $get = \Civi\Api4\WorkflowMessageExample::get()
      ->addWhere('name', '=', $name)
      ->addSelect('workflow', 'data')
      ->execute()
      ->single();
    $this->assertEquals($workflow, $get['workflow']);
    $this->assertEquals(100, $get['data']['modelProps']['contact']['contact_id']);
    $this->assertEquals('myrole', $get['data']['modelProps']['contact']['role']);
  }

}
