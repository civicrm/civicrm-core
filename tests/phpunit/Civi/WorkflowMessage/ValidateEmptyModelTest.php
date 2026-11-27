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

namespace Civi\WorkflowMessage;

use Civi\Test\HeadlessInterface;
use PHPUnit\Framework\TestCase;

/**
 * validate() reports what a model is missing, so it has to survive a model
 * that is missing everything.
 *
 * @group headless
 * @group msgtpl
 */
class ValidateEmptyModelTest extends TestCase implements HeadlessInterface {

  /**
   * @return \Civi\Test\CiviEnvBuilder
   */
  public function setUpHeadless() {
    return \Civi\Test::headless()->apply();
  }

  /**
   * @return array
   */
  public static function getWorkflowNames(): array {
    $cases = [];
    foreach (array_keys(WorkflowMessage::getWorkflowNameClassMap()) as $workflowName) {
      $cases[$workflowName] = [$workflowName];
    }
    return $cases;
  }

  /**
   * An unpopulated model is the case validate() exists to describe.
   *
   * It begins by exporting modelProps, which calls every getter, so a getter
   * that cannot cope with its own id being unset takes the whole call down
   * and the caller learns nothing about what to supply.
   *
   * @dataProvider getWorkflowNames
   *
   * @param string $workflowName
   */
  public function testValidateDoesNotRequireAPopulatedModel(string $workflowName): void {
    $this->assertIsArray(WorkflowMessage::create($workflowName)->validate());
  }

}
