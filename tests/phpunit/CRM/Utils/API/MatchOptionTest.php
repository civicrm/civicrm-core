<?php

/**
 * Test that the API accepts the 'match' and 'match-mandatory' options.
 * @group headless
 */
class CRM_Utils_API_MatchOptionTest extends CiviUnitTestCase {

  /**
   * @var array
   */
  public $noise;

  public function setUp() {
    parent::setUp();
    $this->assertDBQuery(0, "SELECT count(*) FROM civicrm_contact WHERE first_name='Jeffrey' and last_name='Lebowski'");

    // Create noise to ensure we don't accidentally/coincidentally match the first record
    $this->noise['individual'] = $this->individualCreate(array(
      'email' => 'ignore1@example.com',
      // 'street_address-1' => 'Irrelevant'
      'api.Address.create' => array(
        'location_type_id' => 1,
        'street_address' => '123 Irrelevant Str',
        'supplemental_address_1' => 'Room 987',
      ),
    ));
  }

  public function tearDown() {
    $noise = $this->callAPISuccess('Contact', 'get', array(
      'id' => $this->noise['individual'],
      'return' => array('email'),
      'api.Address.get' => 1,
    ));
    $this->assertEquals(1, count($noise['values']));
    foreach ($noise['values'] as $value) {
      $this->assertEquals('ignore1@example.com', $value['email']);
      $this->assertEquals(1, count($value['api.Address.get']['values']));
    }
    CRM_core_DAO::executeQuery('DELETE FROM civicrm_address WHERE contact_id=%1', array(
      1 => array($this->noise['individual'], 'Positive'),
    ));
    $this->callAPISuccess('Contact', 'delete', array(
      'id' => $this->noise['individual'],
    ));
    parent::tearDown();
  }

  /**
   * If there's no pre-existing record, then insert a new one.
   */
  public function testCreateMatch_none() {
    $result = $this->callAPISuccess('contact', 'create', array(
      'options' => array(
        'match' => array('first_name', 'last_name'),
      ),
      'contact_type' => 'Individual',
      'first_name' => 'Jeffrey',
      'last_name' => 'Lebowski',
      'nick_name' => '',
      'external_identifier' => '1',
    ));
    $this->assertEquals('Jeffrey', $result['values'][$result['id']]['first_name']);
    $this->assertEquals('Lebowski', $result['values'][$result['id']]['last_name']);
  }

  /**
   * If there's no pre-existing record, then throw an error.
   */
  public function testCreateMatchMandatory_none() {
    $this->callAPIFailure('contact', 'create', array(
      'options' => array(
        'match-mandatory' => array('first_name', 'last_name'),
      ),
      'contact_type' => 'Individual',
      'first_name' => 'Jeffrey',
      'last_name' => 'Lebowski',
      'nick_name' => '',
      'external_identifier' => '1',
    ), 'Failed to match existing record');
  }

  /**
   * @return array
   */
  public function apiOptionNames() {
    return array(
      array('match'),
      array('match-mandatory'),
    );
  }

  /**
   * If there's one pre-existing record, then update it.
   *
   * @dataProvider apiOptionNames
   * @param string $apiOptionName
   *   E.g. "match" or "match-mandatory".
   */
  public function testCreateMatch_one($apiOptionName) {
    // create basic record
    $result1 = $this->callAPISuccess('contact', 'create', array(
      'contact_type' => 'Individual',
      'first_name' => 'Jeffrey',
      'last_name' => 'Lebowski',
      'nick_name' => '',
      'external_identifier' => '1',
    ));

    // more noise!
    $this->individualCreate(array('email' => 'ignore2@example.com'));

    // update the record by matching first/last name
    $result2 = $this->callAPISuccess('contact', 'create', array(
      'options' => array(
        $apiOptionName => array('first_name', 'last_name'),
      ),
      'contact_type' => 'Individual',
      'first_name' => 'Jeffrey',
      'last_name' => 'Lebowski',
      'nick_name' => 'The Dude',
      'external_identifier' => '2',
    ));

    $this->assertEquals($result1['id'], $result2['id']);
    $this->assertEquals('Jeffrey', $result2['values'][$result2['id']]['first_name']);
    $this->assertEquals('Lebowski', $result2['values'][$result2['id']]['last_name']);
    $this->assertEquals('The Dude', $result2['values'][$result2['id']]['nick_name']);
    // Make sure it was a real update
    $this->assertDBQuery(1, "SELECT count(*) FROM civicrm_contact WHERE first_name='Jeffrey' and last_name='Lebowski' AND nick_name = 'The Dude'");
  }

  /**
   * If there's more than one pre-existing record, throw an error.
   *
   * @dataProvider apiOptionNames
   * @param string $apiOptionName
   *   E.g. "match" or "match-mandatory".
   */
  public function testCreateMatch_many($apiOptionName) {
    // create the first Lebowski
    $result1 = $this->callAPISuccess('contact', 'create', array(
      'contact_type' => 'Individual',
      'first_name' => 'Jeffrey',
      'last_name' => 'Lebowski',
      'nick_name' => 'The Dude',
      'external_identifier' => '1',
    ));

    // create the second Lebowski
    $result2 = $this->callAPISuccess('contact', 'create', array(
      'contact_type' => 'Individual',
      'first_name' => 'Jeffrey',
      'last_name' => 'Lebowski',
      'nick_name' => 'The Big Lebowski',
      'external_identifier' => '2',
    ));

    // more noise!
    $this->individualCreate(array('email' => 'ignore2@example.com'));

    // Try to update - but fail due to ambiguity
    $result3 = $this->callAPIFailure('contact', 'create', array(
      'options' => array(
        $apiOptionName => array('first_name', 'last_name'),
      ),
      'contact_type' => 'Individual',
      'first_name' => 'Jeffrey',
      'last_name' => 'Lebowski',
      'nick_name' => '',
      'external_identifier' => 'new',
    ), 'Ambiguous match criteria');
  }

  /**
   * When replacing one set with another set, match items within
   * the set using a key.
   */
  public function testReplaceMatch_Email() {
    // Create contact with two emails (j1,j2)
    $createResult = $this->callAPISuccess('contact', 'create', array(
      'contact_type' => 'Individual',
      'first_name' => 'Jeffrey',
      'last_name' => 'Lebowski',
      'api.Email.replace' => array(
        'options' => array('match' => 'location_type_id'),
        'values' => array(
          array('location_type_id' => 1, 'email' => 'j1-a@example.com', 'signature_text' => 'The Dude abides.'),
          array(
            'location_type_id' => 2,
            'email' => 'j2@example.com',
            'signature_text' => 'You know, a lotta ins, a lotta outs, a lotta what-have-yous.',
          ),
        ),
      ),
    ));
    $this->assertEquals(1, $createResult['count']);
    foreach ($createResult['values'] as $value) {
      $this->assertAPISuccess($value['api.Email.replace']);
      $this->assertEquals(2, $value['api.Email.replace']['count']);
      foreach ($value['api.Email.replace']['values'] as $v2) {
        $this->assertEquals($createResult['id'], $v2['contact_id']);
      }
      $createEmailValues = array_values($value['api.Email.replace']['values']);
    }

    // Update contact's emails -- specifically, modify j1, delete j2, add j3
    $updateResult = $this->callAPISuccess('contact', 'create', array(
      'id' => $createResult['id'],
      'nick_name' => 'The Dude',
      'api.Email.replace' => array(
        'options' => array('match' => 'location_type_id'),
        'values' => array(
          array('location_type_id' => 1, 'email' => 'j1-b@example.com'),
          array('location_type_id' => 3, 'email' => 'j3@example.com'),
        ),
      ),
    ));
    $this->assertEquals(1, $updateResult['count']);
    foreach ($updateResult['values'] as $value) {
      $this->assertAPISuccess($value['api.Email.replace']);
      $this->assertEquals(2, $value['api.Email.replace']['count']);
      foreach ($value['api.Email.replace']['values'] as $v2) {
        $this->assertEquals($createResult['id'], $v2['contact_id']);
      }
      $updateEmailValues = array_values($value['api.Email.replace']['values']);
    }

    // Re-read from DB
    $getResult = $this->callAPISuccess('Email', 'get', array(
      'contact_id' => $createResult['id'],
    ));
    $this->assertEquals(2, $getResult['count']);
    $getValues = array_values($getResult['values']);

    // The first email (j1@example.com) is updated (same ID#) because it matched on contact_id+location_type_id.
    $this->assertTrue(is_numeric($createEmailValues[0]['id']));
    $this->assertTrue(is_numeric($updateEmailValues[0]['id']));
    $this->assertTrue(is_numeric($getValues[0]['id']));
    $this->assertEquals($createEmailValues[0]['id'], $updateEmailValues[0]['id']);
    $this->assertEquals($createEmailValues[0]['id'], $getValues[0]['id']);
    $this->assertEquals('j1-b@example.com', $getValues[0]['email']);
    // preserved from original creation; proves that we updated existing record
    $this->assertEquals('The Dude abides.', $getValues[0]['signature_text']);

    // The second email (j2@example.com) is deleted because contact_id+location_type_id doesn't appear in new list.
    // The third email (j3@example.com) is inserted (new ID#) because it doesn't match an existing contact_id+location_type_id.
    $this->assertTrue(is_numeric($createEmailValues[1]['id']));
    $this->assertTrue(is_numeric($updateEmailValues[1]['id']));
    $this->assertTrue(is_numeric($getValues[1]['id']));
    $this->assertNotEquals($createEmailValues[1]['id'], $updateEmailValues[1]['id']);
    $this->assertEquals($updateEmailValues[1]['id'], $getValues[1]['id']);
    $this->assertEquals('j3@example.com', $getValues[1]['email']);
    $this->assertTrue(empty($getValues[1]['signature_text']));
  }

  /**
   * When replacing one set with another set, match items within
   * the set using a key.
   */
  public function testReplaceMatch_Address() {
    // Create contact with two addresses (j1,j2)
    $createResult = $this->callAPISuccess('contact', 'create', array(
      'contact_type' => 'Individual',
      'first_name' => 'Jeffrey',
      'last_name' => 'Lebowski',
      'api.Address.replace' => array(
        'options' => array('match' => 'location_type_id'),
        'values' => array(
          array(
            'location_type_id' => 1,
            'street_address' => 'j1-a Example Ave',
            'supplemental_address_1' => 'The Dude abides.',
          ),
          array(
            'location_type_id' => 2,
            'street_address' => 'j2 Example Ave',
            'supplemental_address_1' => 'You know, a lotta ins, a lotta outs, a lotta what-have-yous.',
          ),
        ),
      ),
    ));
    $this->assertEquals(1, $createResult['count']);
    foreach ($createResult['values'] as $value) {
      $this->assertAPISuccess($value['api.Address.replace']);
      $this->assertEquals(2, $value['api.Address.replace']['count']);
      foreach ($value['api.Address.replace']['values'] as $v2) {
        $this->assertEquals($createResult['id'], $v2['contact_id']);
      }
      $createAddressValues = array_values($value['api.Address.replace']['values']);
    }

    // Update contact's addresses -- specifically, modify j1, delete j2, add j3
    $updateResult = $this->callAPISuccess('contact', 'create', array(
      'id' => $createResult['id'],
      'nick_name' => 'The Dude',
      'api.Address.replace' => array(
        'options' => array('match' => 'location_type_id'),
        'values' => array(
          array('location_type_id' => 1, 'street_address' => 'j1-b Example Ave'),
          array('location_type_id' => 3, 'street_address' => 'j3 Example Ave'),
        ),
      ),
    ));
    $this->assertEquals(1, $updateResult['count']);
    foreach ($updateResult['values'] as $value) {
      $this->assertAPISuccess($value['api.Address.replace']);
      $this->assertEquals(2, $value['api.Address.replace']['count']);
      foreach ($value['api.Address.replace']['values'] as $v2) {
        $this->assertEquals($createResult['id'], $v2['contact_id']);
      }
      $updateAddressValues = array_values($value['api.Address.replace']['values']);
    }

    // Re-read from DB
    $getResult = $this->callAPISuccess('Address', 'get', array(
      'contact_id' => $createResult['id'],
    ));
    $this->assertEquals(2, $getResult['count']);
    $getValues = array_values($getResult['values']);

    // The first street_address (j1 Example Ave) is updated (same ID#) because it matched on contact_id+location_type_id.
    $this->assertTrue(is_numeric($createAddressValues[0]['id']));
    $this->assertTrue(is_numeric($updateAddressValues[0]['id']));
    $this->assertTrue(is_numeric($getValues[0]['id']));
    $this->assertEquals($createAddressValues[0]['id'], $updateAddressValues[0]['id']);
    $this->assertEquals($createAddressValues[0]['id'], $getValues[0]['id']);
    $this->assertEquals('j1-b Example Ave', $getValues[0]['street_address']);
    // preserved from original creation; proves that we updated existing record
    $this->assertEquals('The Dude abides.', $getValues[0]['supplemental_address_1']);

    // The second street_address (j2 Example Ave) is deleted because contact_id+location_type_id doesn't appear in new list.
    // The third street_address (j3 Example Ave) is inserted (new ID#) because it doesn't match an existing contact_id+location_type_id.
    $this->assertTrue(is_numeric($createAddressValues[1]['id']));
    $this->assertTrue(is_numeric($updateAddressValues[1]['id']));
    $this->assertTrue(is_numeric($getValues[1]['id']));
    $this->assertNotEquals($createAddressValues[1]['id'], $updateAddressValues[1]['id']);
    $this->assertEquals($updateAddressValues[1]['id'], $getValues[1]['id']);
    $this->assertEquals('j3 Example Ave', $getValues[1]['street_address']);
    $this->assertTrue(empty($getValues[1]['supplemental_address_1']));
  }

}
