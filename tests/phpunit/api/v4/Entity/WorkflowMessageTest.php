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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */


namespace api\v4\Entity;

use api\v4\UnitTestCase;
use Civi\Api4\WorkflowMessage;

/**
 * @group headless
 */
class WorkflowMessageTest extends UnitTestCase {

  public function testGet() {
    $result = \Civi\Api4\WorkflowMessage::get(0)
      ->addWhere('name', 'LIKE', 'case%')
      ->execute()
      ->indexBy('name');
    $this->assertTrue(isset($result['case_activity']));
  }

  public function testRenderDefaultTemplate() {
    $ex = \Civi\Api4\ExampleData::get(0)
      ->addWhere('name', '=', 'workflow/case_activity/CaseModelExample')
      ->addSelect('data')
      ->addChain('render', WorkflowMessage::render()
        ->setWorkflow('$data.workflow')
        ->setValues('$data.modelProps'))
      ->execute()
      ->single();
    $result = $ex['render'][0];
    $this->assertRegExp('/Case ID : 1234/', $result['text']);
  }

  public function testRenderCustomTemplate() {
    $ex = \Civi\Api4\ExampleData::get(0)
      ->addWhere('name', '=', 'workflow/case_activity/CaseModelExample')
      ->addSelect('data')
      ->execute()
      ->single();
    $result = \Civi\Api4\WorkflowMessage::render(0)
      ->setWorkflow('case_activity')
      ->setValues($ex['data']['modelProps'])
      ->setMessageTemplate([
        'msg_text' => 'The role is {$contact.role}.',
      ])
      ->execute()
      ->single();
    $this->assertRegExp('/The role is myrole./', $result['text']);
  }

  public function testRenderExamples() {
    $examples = \Civi\Api4\ExampleData::get(0)
      ->addWhere('tags', 'CONTAINS', 'phpunit')
      ->addSelect('name', 'data', 'asserts')
      ->execute();
    $this->assertTrue($examples->rowCount >= 1);
    foreach ($examples as $example) {
      $this->assertTrue(!empty($example['data']['modelProps']), sprintf("Example (%s) is tagged phpunit. It should have modelProps.", $example['name']));
      $this->assertTrue(!empty($example['asserts']['default']), sprintf("Example (%s) is tagged phpunit. It should have assertions.", $example['name']));
      $result = \Civi\Api4\WorkflowMessage::render(0)
        ->setWorkflow($example['data']['workflow'])
        ->setValues($example['data']['modelProps'])
        ->execute()
        ->single();
      foreach ($example['asserts']['default'] as $num => $assert) {
        $msg = sprintf('Check assertion(%s) on example (%s)', $num, $example['name']);
        if (isset($assert['regex'])) {
          $this->assertRegExp($assert['regex'], $result[$assert['for']], $msg);
        }
        else {
          $this->fail('Unrecognized assertion: ' . json_encode($assert));
        }
      }
    }
  }

}
