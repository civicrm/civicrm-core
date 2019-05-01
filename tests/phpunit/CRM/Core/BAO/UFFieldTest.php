<?php

/**
 * Class CRM_Core_BAO_UFFieldTest
 * @group headless
 */
class CRM_Core_BAO_UFFieldTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();

    $this->quickCleanup(array('civicrm_uf_group', 'civicrm_uf_field'));
  }

  /**
   * When passing in a GID, fields should be omitted if they already appear in the group.
   */
  public function testGetAvailable_byGid() {
    $ufGroupId = $this->createUFGroup(array(
      array(
        'field_name' => 'do_not_sms',
        'field_type' => 'Contact',
      ),
      array(
        'field_name' => 'first_name',
        'field_type' => 'Individual',
      ),
      array(
        'field_name' => 'amount_level',
        'field_type' => 'Contribution',
      ),
      array(
        'field_name' => 'participant_note',
        'field_type' => 'Participant',
      ),
      array(
        'field_name' => 'join_date',
        'field_type' => 'Membership',
      ),
      array(
        'field_name' => 'activity_date_time',
        'field_type' => 'Activity',
      ),
    ));
    $fields = CRM_Core_BAO_UFField::getAvailableFields($ufGroupId);

    // Make sure that each entity has 1+ present field and 1+ missing (already-used) field
    // already used
    $this->assertFalse(isset($fields['Contact']['do_not_sms']));
    $this->assertEquals('city', $fields['Contact']['city']['name']);

    // already used
    $this->assertFalse(isset($fields['Individual']['first_name']));
    $this->assertEquals('birth_date', $fields['Individual']['birth_date']['name']);

    $this->assertEquals('organization_name', $fields['Organization']['organization_name']['name']);
    $this->assertEquals('legal_name', $fields['Organization']['legal_name']['name']);

    // already used
    $this->assertFalse(isset($fields['Contribution']['amount_level']));
    $this->assertEquals('cancel_reason', $fields['Contribution']['cancel_reason']['name']);

    // already used
    $this->assertFalse(isset($fields['Participant']['participant_note']));
    $this->assertEquals('participant_role', $fields['Participant']['participant_role']['name']);

    // already used
    $this->assertFalse(isset($fields['Membership']['join_date']));
    $this->assertEquals('end_date', $fields['Membership']['membership_end_date']['name']);

    // already used
    $this->assertFalse(isset($fields['Activity']['activity_date_time']));
    $this->assertEquals('subject', $fields['Activity']['activity_subject']['name']);

    // Make sure that some of the blacklisted fields don't appear
    $this->assertFalse(isset($fields['Contribution']['is_pay_later']));
    $this->assertFalse(isset($fields['Participant']['participant_role_id']));
    $this->assertFalse(isset($fields['Membership']['membership_type_id']));

    // This behavior is not necessarily desirable, but it's the status quo
    $this->assertEquals('first_name', $fields['Staff']['first_name']['name']);
  }

  /**
   * When passing in $defaults, the currently selected field should still be included -- even if
   * it's already part of the profile.
   */
  public function testGetAvailable_byGidDefaults() {
    $ufGroupId = $this->createUFGroup(array(
      array(
        'field_name' => 'do_not_sms',
        'field_type' => 'Contact',
      ),
      array(
        'field_name' => 'first_name',
        'field_type' => 'Individual',
      ),
    ));
    $defaults = array('field_name' => array('Individual', 'first_name'));
    $fields = CRM_Core_BAO_UFField::getAvailableFields($ufGroupId, $defaults);

    // already used
    $this->assertFalse(isset($fields['Contact']['do_not_sms']));
    $this->assertEquals('city', $fields['Contact']['city']['name']);

    // used by me
    $this->assertEquals('first_name', $fields['Individual']['first_name']['name']);
    $this->assertEquals('birth_date', $fields['Individual']['birth_date']['name']);
  }

  /**
   * When omitting a GID, return a list of all fields.
   */
  public function testGetAvailable_full() {
    $fields = CRM_Core_BAO_UFField::getAvailableFields();

    // Make sure that each entity appears with at least one field
    $this->assertEquals('do_not_sms', $fields['Contact']['do_not_sms']['name']);
    $this->assertEquals('city', $fields['Contact']['city']['name']);

    $this->assertEquals('first_name', $fields['Individual']['first_name']['name']);
    $this->assertEquals('birth_date', $fields['Individual']['birth_date']['name']);

    $this->assertEquals('organization_name', $fields['Organization']['organization_name']['name']);
    $this->assertEquals('legal_name', $fields['Organization']['legal_name']['name']);

    $this->assertEquals('amount_level', $fields['Contribution']['amount_level']['name']);
    $this->assertEquals('cancel_reason', $fields['Contribution']['cancel_reason']['name']);

    $this->assertEquals('participant_note', $fields['Participant']['participant_note']['name']);
    $this->assertEquals('participant_role', $fields['Participant']['participant_role']['name']);

    $this->assertEquals('join_date', $fields['Membership']['join_date']['name']);
    $this->assertEquals('end_date', $fields['Membership']['membership_end_date']['name']);

    $this->assertEquals('activity_date_time', $fields['Activity']['activity_date_time']['name']);
    $this->assertEquals('subject', $fields['Activity']['activity_subject']['name']);

    // Make sure that some of the blacklisted fields don't appear
    $this->assertFalse(isset($fields['Contribution']['is_pay_later']));
    $this->assertFalse(isset($fields['Participant']['participant_role_id']));
    $this->assertFalse(isset($fields['Membership']['membership_type_id']));

    // This behavior is not necessarily desirable, but it's the status quo
    $this->assertEquals('first_name', $fields['Staff']['first_name']['name']);
  }

  /**
   * When omitting a GID, return a list of all fields.
   */
  public function testGetAvailableFlat() {
    $fields = CRM_Core_BAO_UFField::getAvailableFieldsFlat();

    // Make sure that each entity appears with at least one field
    $this->assertEquals('Contact', $fields['do_not_sms']['field_type']);
    $this->assertEquals('Contact', $fields['city']['field_type']);

    $this->assertEquals('Individual', $fields['first_name']['field_type']);
    $this->assertEquals('Individual', $fields['birth_date']['field_type']);

    $this->assertEquals('Organization', $fields['organization_name']['field_type']);
    $this->assertEquals('Organization', $fields['legal_name']['field_type']);

    $this->assertEquals('Contribution', $fields['amount_level']['field_type']);
    $this->assertEquals('Contribution', $fields['cancel_reason']['field_type']);

    $this->assertEquals('Participant', $fields['participant_note']['field_type']);
    $this->assertEquals('Participant', $fields['participant_role']['field_type']);

    $this->assertEquals('Membership', $fields['join_date']['field_type']);
    $this->assertEquals('Membership', $fields['membership_end_date']['field_type']);

    $this->assertEquals('Activity', $fields['activity_date_time']['field_type']);
    $this->assertEquals('Activity', $fields['activity_subject']['field_type']);

    // Make sure that some of the blacklisted fields don't appear
    $this->assertFalse(isset($fields['is_pay_later']));
    $this->assertFalse(isset($fields['participant_role_id']));
    $this->assertFalse(isset($fields['membership_type_id']));
  }

  /**
   * Make sure that the existence of a profile doesn't break listing all fields
   *
   * public function testGetAvailable_mixed() {
   * // FIXME
   * $this->testGetAvailable_full();
   * // $this->testGetAvailable_byGid();
   * $this->testGetAvailable_full();
   * // $this->testGetAvailable_byGid();
   * } // */

  /**
   * @param array $fields
   *   List of fields to include in the profile.
   * @return int
   *   field id
   */
  protected function createUFGroup($fields) {
    $ufGroup = CRM_Core_DAO::createTestObject('CRM_Core_DAO_UFGroup');
    $this->assertTrue(is_numeric($ufGroup->id));

    foreach ($fields as $field) {
      $defaults = array(
        'uf_group_id' => $ufGroup->id,
        'visibility' => 'Public Pages and Listings',
        'weight' => 1,
        'label' => 'Label for ' . $field['field_name'],
        'is_searchable' => 1,
        'is_active' => 1,
        'location_type_id' => NULL,
      );
      $params = array_merge($field, $defaults);
      $ufField = $this->callAPISuccess('UFField', 'create', $params);
      $this->assertAPISuccess($ufField);
    }

    return $ufGroup->id;
  }

}
