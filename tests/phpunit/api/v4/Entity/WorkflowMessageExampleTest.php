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

/**
 * @group headless
 */
class WorkflowMessageExampleTest extends UnitTestCase {

  /**
   * Basic canary test fetching a specific example.
   *
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testGet() {
    $file = \Civi::paths()->getPath('[civicrm.root]/Civi/WorkflowMessage/GenericWorkflowMessage/alex.ex.php');
    $workflow = 'generic';
    $name = 'generic.alex';

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
  }

}
