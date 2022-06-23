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

use api\v4\Api4TestBase;
use Civi\Api4\ExampleData;
use Civi\Api4\WorkflowMessage;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class WorkflowMessageTest extends Api4TestBase implements TransactionalInterface {

  /**
   * Basic get test.
   *
   * @throws \API_Exception
   */
  public function testGet(): void {
    $result = WorkflowMessage::get(FALSE)
      ->addWhere('name', 'LIKE', 'case%')
      ->execute()
      ->indexBy('name');
    $this->assertTrue(isset($result['case_activity']));
  }

  public function testRenderDefaultTemplate(): void {
    $ex = ExampleData::get(FALSE)
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
    $ex = ExampleData::get(0)
      ->addWhere('name', '=', 'workflow/case_activity/CaseModelExample')
      ->addSelect('data')
      ->execute()
      ->single();
    $result = WorkflowMessage::render(0)
      ->setWorkflow('case_activity')
      ->setValues($ex['data']['modelProps'])
      ->setMessageTemplate([
        'msg_text' => 'The role is {$contact.role}.',
      ])
      ->execute()
      ->single();
    $this->assertRegExp('/The role is myrole./', $result['text']);
  }

  public function testRenderExamplesBaseline() {
    $examples = $this->getRenderExamples();
    $this->assertTrue(isset($examples['workflow/contribution_recurring_edit/AlexCancelled']));
  }

  public function getRenderExamples(): array {
    $metas = \Civi\Test::examples()->getMetas();
    $results = [];
    foreach ($metas as $name => $meta) {
      if (!empty($meta['data']['workflow'])) {
        continue;
      }
      if (empty($meta['tags']) || !in_array('phpunit', $meta['tags'])) {
        continue;
      }
      if ($exampleFilter = getenv('WORKFLOW_EXAMPLES')) {
        if (!preg_match($exampleFilter, $name)) {
          continue;
        }
      }
      $results[$name] = [$meta['name']];
    }
    return $results;
  }

  /**
   * Test examples render.
   *
   * Only examples tagged phpunit will be checked.
   *
   * @param string $name
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   * @dataProvider getRenderExamples
   */
  public function testRenderExamples(string $name): void {
    $example = ExampleData::get(FALSE)
      ->addWhere('name', '=', $name)
      ->addSelect('name', 'file', 'data', 'asserts')
      ->execute()
      ->single();

    $this->assertNotEmpty($example['data']['modelProps'], sprintf('Example (%s) is tagged phpunit. It should have modelProps.', $example['name']));
    $this->assertNotEmpty($example['asserts']['default'], sprintf('Example (%s) is tagged phpunit. It should have assertions.', $example['name']));
    $result = WorkflowMessage::render(FALSE)
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
