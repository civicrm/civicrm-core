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
 * Test class for CRM_Mailing_Form_Unsubscribe.
 * @group headless
 */
class CRM_Mailing_Form_UnsubscribeTest extends CiviUnitTestCase {

  public function testSubmit(): void {
    $this->getTestForm('CRM_Mailing_Form_Unsubscribe', [], [
      'jid' => 1,

    ])
    ->processForm();
  }

}
