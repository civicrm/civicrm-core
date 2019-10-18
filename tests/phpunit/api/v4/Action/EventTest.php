<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
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
