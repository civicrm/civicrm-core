<?php

/**
 * Class CRM_Contact_BAO_QueryStateNameTest
 * @group headless
 */
class CRM_Contact_BAO_QueryStateNameTest extends CiviUnitTestCase {

  /**
   * Test case for state_province_name pseudofield
   *
   * See CRM-15505: Mailing labels show the state/province name as the abbreviation rather than the full state/province name
   * Change to CRM_Contact_BAO_query::convertToPseudoNames()
   */
  public function testStateName() {
    $state_name = 'Norfolk';
    $state_abbreviation = 'NFK';
    $create_params = [
      'contact_type' => 'Individual',
      'first_name' => 'John',
      'last_name' => 'Doe',
      'api.Address.create' => [
        'location_type_id' => 'Home',
        'state_province_id' => $state_name,
      ],
    ];
    $create_res = civicrm_api3('Contact', 'Create', $create_params);

    $get_params = [
      'id' => $create_res['id'],
      'sequential' => 1,
    ];
    $get_res = civicrm_api3('Contact', 'get', $get_params);
    $this->assertEquals($state_name, $get_res['values'][0]['state_province_name']);
    // Lock in that state_provice should equal that of the abbreviation.
    $this->assertEquals($state_abbreviation, $get_res['values'][0]['state_province']);
  }

}
