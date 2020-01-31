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

use Civi\Api4\Event;

/**
 * @group headless
 */
class EventTest extends \api\v4\UnitTestCase {

  /**
   * Test that the event api filters out templates by default.
   *
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testTemplateFilterByDefault() {
    Event::create()->setValues(['template_title' => 'Big Event', 'is_template' => 1, 'start_date' => 'now', 'event_type_id' => 'Meeting'])->execute();
    Event::create()->setValues(['title' => 'Bigger Event', 'start_date' => 'now', 'event_type_id' => 'Meeting'])->execute();
    $this->assertEquals(1, Event::get()->selectRowCount()->execute()->count());
  }

}
