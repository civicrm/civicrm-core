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

use Civi\Api4\UFJoin;

/**
 * Test class for CRM_Price_BAO_PriceSet.
 * @group headless
 */
class CRM_Profile_Form_EditTest extends CiviUnitTestCase {

  public function tearDown(): void {
    $this->quickCleanup(['civicrm_uf_field', 'civicrm_uf_group']);
    parent::tearDown();
  }

  /**
   * Test the url on the profile edit form renders tokens
   *
   * @throws \API_Exception
   */
  public function testProfileUrl(): void {
    $profileID = Civi\Api4\UFGroup::create(FALSE)->setValues([
      'post_URL' => 'civicrm/{contact.display_name}',
      'title' => 'title',
    ])->execute()->first()['id'];
    UFJoin::create(FALSE)->setValues([
      'module' => 'Profile',
      'uf_group_id' => $profileID,
    ])->execute();
    $this->uFFieldCreate(['uf_group_id' => $profileID]);
    $id = $this->individualCreate();
    $form = $this->getFormObject('CRM_Profile_Form_Edit');
    $form->set('gid', $profileID);
    $form->set('id', $id);
    $form->buildForm();
    $form->postProcess();
    $this->assertEquals('civicrm/Mr. Anthony Anderson II', CRM_Core_Session::singleton()->popUserContext());
  }

}
