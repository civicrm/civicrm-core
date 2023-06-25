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

  /**
   * Test the url on the profile edit form renders tokens
   *
   * @throws \CRM_Core_Exception
   */
  public function testProfileUrl(): void {
    $profileID = $this->createTestEntity('UFGroup', [
      'post_url' => 'civicrm/{contact.display_name}',
      'title' => 'title',
    ])['id'];
    UFJoin::create(FALSE)->setValues([
      'module' => 'Profile',
      'uf_group_id' => $profileID,
    ])->execute();
    $this->uFFieldCreate(['uf_group_id' => $profileID]);
    $contactID = $this->individualCreate();
    $form = $this->getFormObject('CRM_Profile_Form_Edit');
    $form->set('gid', $profileID);
    $form->set('id', $contactID);
    $form->buildForm();
    $form->postProcess();
    $this->assertEquals('civicrm/Mr. Anthony Anderson II', CRM_Core_Session::singleton()->popUserContext());
  }

  /**
   * Test that requiring tags on a profile works.
   *
   * @throws \CRM_Core_Exception
   */
  public function testProfileRequireTag(): void {
    $ufGroupParams = [
      'group_type' => 'Individual,Contact',
      'name' => 'test_individual_contact_tag_profile',
      'title' => 'Gimme a tag',
    ];

    $profile = $this->createTestEntity('UFGroup', $ufGroupParams);
    $profileID = $profile['id'];
    $this->createTestEntity('UFField', [
      'field_name' => 'first_name',
      'is_required' => 1,
      'visibility' => 'Public Pages and Listings',
      'field_type' => 'Individual',
      'label' => 'First Name',
      'uf_group_id' => $profileID,
    ]);
    $this->createTestEntity('UFField', [
      'field_name' => 'tag',
      'is_required' => 1,
      'visibility' => 'Public Pages and Listings',
      'field_type' => 'Contact',
      'label' => 'Tag',
      'uf_group_id' => $profileID,
    ]);

    // Configure the profile to be used as a standalone profile for data entry.
    UFJoin::create(FALSE)->setValues([
      'module' => 'Profile',
      'uf_group_id' => $profileID,
    ])->execute();

    // Populate the form.
    $formParams = [
      'first_name' => 'Foo',
      'last_name' => 'McGoo',
      'gid' => $profileID,
      'tag' => [],
    ];
    $form = $this->getFormObject('CRM_Profile_Form_Edit', $formParams);
    $form->set('gid', $profileID);
    $form->preProcess();
    $form->buildQuickForm();
    $this->assertFalse($form->validate(), 'Ensure tags can be required on a form.');
  }

}
