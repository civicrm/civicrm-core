<?php

require_once 'CiviTest/CiviUnitTestCase.php';
/**
 * Test that the API accepts the 'match' and 'match-mandatory' options.
 */
class CRM_Utils_API_MatchOptionTest extends CiviUnitTestCase {

  function setUp() {
    parent::setUp();
    $this->assertDBQuery(0, "SELECT count(*) FROM civicrm_contact WHERE first_name='Jeffrey' and last_name='Lebowski'");

    // Create noise to ensure we don't accidentally/coincidentally match the first record
    $this->individualCreate(array('email' => 'ignore1@example.com'));
  }

  /**
   * If there's no pre-existing record, then insert a new one.
   */
  function testMatch_none() {
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
  function testMatchMandatory_none() {
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

  function apiOptionNames() {
    return array(
      array('match'),
      array('match-mandatory'),
    );
  }

  /**
   * If there's one pre-existing record, then update it.
   *
   * @dataProvider apiOptionNames
   * @param string $apiOptionName e.g. "match" or "match-mandatory"
   */
  function testMatch_one($apiOptionName) {
    // create basic record
    $result1 = $this->callAPISuccess('contact', 'create', array(
      'contact_type' => 'Individual',
      'first_name' => 'Jeffrey',
      'last_name' => 'Lebowski',
      'nick_name' => '',
      'external_identifier' => '1',
    ));

    $this->individualCreate(array('email' => 'ignore2@example.com')); // more noise!

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
   * @param string $apiOptionName e.g. "match" or "match-mandatory"
   */
  function testMatch_many($apiOptionName) {
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

    $this->individualCreate(array('email' => 'ignore2@example.com')); // more noise!

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

}
