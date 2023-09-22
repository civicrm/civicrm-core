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

namespace api\v4\Action;

use api\v4\Api4TestBase;
use Civi\Api4\Event;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class EventTest extends Api4TestBase implements TransactionalInterface {

  /**
   * Test that the event api filters out templates by default.
   *
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testTemplateFilterByDefault(): void {
    $t = Event::create()->setValues(['template_title' => 'Big Event', 'is_template' => 1, 'start_date' => 'now', 'event_type_id:name' => 'Meeting'])->execute()->first();
    $e = Event::create()->setValues(['title' => 'Bigger Event', 'start_date' => 'now', 'event_type_id:name' => 'Meeting'])->execute()->first();
    $result = (array) Event::get()->execute()->column('id');
    $this->assertContains($e['id'], $result);
    $this->assertNotContains($t['id'], $result);
  }

}
