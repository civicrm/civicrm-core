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
use Civi\Test\Invasive;

/**
 * Test class for CRM_Price_BAO_PriceSet.
 * @group headless
 */
class CRM_Profile_Form_EditTest extends CiviUnitTestCase {

  /**
   * Clean up the profiles and groups the tests create.
   */
  protected function tearDown(): void {
    $this->quickCleanup(['civicrm_uf_field', 'civicrm_uf_join', 'civicrm_uf_group', 'civicrm_group']);
    parent::tearDown();
  }

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
    $this->assertSame('civicrm/Mr. Anthony Anderson II', CRM_Core_Session::singleton()->popUserContext());
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

  /**
   * Test the CMS registration form only lists the fields it builds.
   *
   * View-only fields are left out of the registration form (CRM-4632) and
   * the CMS manages the email field, so neither gets a row in the form. The
   * edit form of the same profile still shows the view-only field.
   */
  public function testRegistrationFormOmitsFieldsItDoesNotBuild(): void {
    $profileID = $this->createRegistrationProfile([], [
      'middle_name' => ['field_type' => 'Individual', 'is_view' => TRUE],
      'job_title' => ['field_type' => 'Individual', 'is_view' => FALSE],
      'email' => ['field_type' => 'Contact', 'is_view' => FALSE],
    ]);

    $html = CRM_Core_BAO_UFGroup::getEditHTML(NULL, '', NULL, TRUE, TRUE);

    $this->assertStringContainsString('id="editrow-job_title"', $html);
    $this->assertStringNotContainsString('editrow-middle_name', $html);
    $this->assertStringNotContainsString('editrow-email', $html);

    $html = CRM_Core_BAO_UFGroup::getEditHTML($this->individualCreate(), '', NULL, FALSE, TRUE, $profileID);

    $this->assertStringContainsString('id="editrow-middle_name"', $html);
  }

  /**
   * Test the registration form keeps the group of the fields it leaves out.
   *
   * A registration profile with only view-only and email fields still adds
   * new contacts to its group.
   */
  public function testRegistrationFormKeepsGroupOfOmittedFields(): void {
    $groupID = $this->createTestEntity('Group', [
      'name' => 'test_registration_group',
      'title' => 'Registration group',
    ])['id'];
    $this->createRegistrationProfile(['add_to_group_id' => $groupID], [
      'middle_name' => ['field_type' => 'Individual', 'is_view' => TRUE],
      'email' => ['field_type' => 'Contact', 'is_view' => FALSE],
    ]);

    $form = $this->getFormObject('CRM_Profile_Form_Dynamic');
    $form->set('register', 1);
    $form->set('skipPermission', 1);
    $form->buildForm();

    $this->assertSame($groupID, (int) (Invasive::get([$form, '_ufGroup'])['add_to_group_id'] ?? NULL));
  }

  /**
   * Create a profile used for User Registration.
   *
   * @param array $profileValues
   *   Extra UFGroup values.
   * @param array $fields
   *   UFField values keyed by field name.
   *
   * @return int
   *   The profile ID.
   */
  protected function createRegistrationProfile(array $profileValues, array $fields): int {
    $profileID = $this->createTestEntity('UFGroup', $profileValues + [
      'group_type' => 'Individual,Contact',
      'name' => 'test_registration_profile',
      'title' => 'Registration',
    ])['id'];
    $this->createTestEntity('UFJoin', [
      'module' => 'User Registration',
      'uf_group_id' => $profileID,
    ]);
    foreach ($fields as $fieldName => $field) {
      $this->createTestEntity('UFField', [
        'uf_group_id' => $profileID,
        'field_name' => $fieldName,
        'label' => $fieldName,
        'visibility' => 'Public Pages and Listings',
      ] + $field, $fieldName);
    }
    return $profileID;
  }

}
