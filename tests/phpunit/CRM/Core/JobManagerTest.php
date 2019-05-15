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
 * Class CRM_Core_JobManagerTest
 * @group headless
 */
class CRM_Core_JobManagerTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  public function testHookCron() {
    $mockFunction = $this->mockMethod;
    $hook = $this->$mockFunction('stdClass', array('civicrm_cron'));
    $hook->expects($this->once())
      ->method('civicrm_cron')
      ->with($this->isInstanceOf('CRM_Core_JobManager'));
    CRM_Utils_Hook::singleton()->setMock($hook);

    $jobManager = new CRM_Core_JobManager();
    $jobManager->execute(FALSE);
  }

}
