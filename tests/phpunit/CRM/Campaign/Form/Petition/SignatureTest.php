<?php

use Civi\Test\FormTrait;
use Civi\Test\FormWrapper;

/**
 * @group headless
 */
class CRM_Campaign_Form_Petition_SignatureTest extends CiviUnitTestCase {

  use FormTrait;

  public function setUp(): void {
    parent::setUp();
    CRM_Core_BAO_ConfigSetting::enableComponent('CiviCampaign');
  }

  public function tearDown(): void {
    $this->quickCleanup(['civicrm_activity', 'civicrm_activity_contact', 'civicrm_survey', 'civicrm_uf_join'], TRUE);
    if (!empty($this->ids['UFGroup'])) {
      \Civi\Api4\UFGroup::delete(FALSE)->addWhere('id', 'IN', $this->ids['UFGroup'])->execute();
    }
    CRM_Core_BAO_ConfigSetting::disableComponent('CiviCampaign');
    parent::tearDown();
  }

  /**
   * A new signature does not pre-fill activity custom fields from the
   * activity sharing its ID with the signing contact.
   */
  public function testActivityCustomFieldDefaultsIgnoreOtherActivities(): void {
    $customGroup = $this->createTestEntity('CustomGroup', ['title' => 'Signature data', 'extends' => 'Activity']);
    $customField = $this->createTestEntity('CustomField', [
      'custom_group_id' => $customGroup['id'],
      'label' => 'Signature note',
      'data_type' => 'String',
      'html_type' => 'Text',
    ]);
    $fieldName = 'custom_' . $customField['id'];
    $contactID = $this->createLoggedInUser();

    // Another contact's activity gets the same ID as the signing contact.
    CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_activity AUTO_INCREMENT = ' . $contactID);
    $otherActivity = $this->createTestEntity('Activity', [
      'activity_type_id:name' => 'Meeting',
      'source_contact_id' => $this->individualCreate(),
      $fieldName => 'other activity',
    ]);
    $this->assertSame($contactID, $otherActivity['id']);

    $survey = $this->createTestEntity('Survey', ['title' => 'Petition', 'activity_type_id:name' => 'Petition']);
    $profiles = [
      // Weight 2 is the contact profile, weight 1 the activity profile.
      2 => ['group_type' => 'Individual,Contact', 'field_name' => 'email', 'field_type' => 'Contact'],
      1 => ['group_type' => 'Activity', 'field_name' => $fieldName, 'field_type' => 'Activity'],
    ];
    foreach ($profiles as $weight => $profile) {
      $ufGroup = $this->createTestEntity('UFGroup', ['title' => 'Petition profile ' . $weight, 'group_type' => $profile['group_type']], 'petition' . $weight);
      $this->createTestEntity('UFField', [
        'uf_group_id' => $ufGroup['id'],
        'field_name' => $profile['field_name'],
        'label' => $profile['field_name'],
        'field_type' => $profile['field_type'],
      ], 'petition' . $weight);
      $this->createTestEntity('UFJoin', [
        'module' => 'CiviCampaign',
        'weight' => $weight,
        'uf_group_id' => $ufGroup['id'],
        'entity_table' => 'civicrm_survey',
        'entity_id' => $survey['id'],
      ], 'petition' . $weight);
    }

    $form = $this->getTestForm('CRM_Campaign_Form_Petition_Signature', [], ['sid' => $survey['id']]);
    $form->processForm(FormWrapper::BUILT);
    $this->assertArrayNotHasKey($fieldName, array_filter($form->getDefaultValues()));
  }

}
