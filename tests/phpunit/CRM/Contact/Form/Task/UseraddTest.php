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
  * @group headless
  */
class CRM_Contact_Form_Task_UseraddTest extends CiviUnitTestCase {

  /**
   * Test postProcess failure.
   *
   * In unit tests, the CMS user creation will always fail, but that's
   * ok because that's what we're testing here.
   */
  public function testUserCreateFail() {
    $form = new CRM_Contact_Form_Task_Useradd();
    // We don't need to set params or anything because we're testing fail,
    // which the user creation will do in unit tests no matter what we set.
    // But before the patch, the status messages were always success no
    // matter what.
    try {
      $form->postProcess();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
    }
    $statuses = CRM_Core_Session::singleton()->getStatus(TRUE);
    $this->assertEquals('alert', $statuses[0]['type']);
  }

}
