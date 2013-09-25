<?php
/**
 * Test Generated example of using profile getfields API
 * demonstrates retrieving profile fields passing in an id *
 */
function profile_getfields_example(){
$params = array(
  'action' => 'submit',
  'profile_id' => 27,
);

try{
  $result = civicrm_api3('profile', 'getfields', $params);
}
catch (CiviCRM_API3_Exception $e) {
  // handle error here
  $errorMessage = $e->getMessage();
  $errorCode = $e->getErrorCode();
  $errorData = $e->getExtraParams();
  return array('error' => $errorMessage, 'error_code' => $errorCode, 'error_data' => $errorData);
}

return $result;
}

/**
 * Function returns array of result expected from previous function
 */
function profile_getfields_expectedresult(){

  $expectedResult = array(
  'is_error' => 0,
  'version' => 3,
  'count' => 9,
  'values' => array(
      'first_name' => array(
          'api.required' => '1',
          'title' => 'First Name',
          'help_pre' => '',
          'help_post' => '',
          'entity' => 'contact',
          'name' => 'first_name',
          'type' => 2,
          'maxlength' => 64,
          'size' => 30,
          'import' => true,
          'where' => 'civicrm_contact.first_name',
          'headerPattern' => '/^first|(f(irst\s)?name)$/i',
          'dataPattern' => '/^\w+$/',
          'export' => true,
          'api.aliases' => array(),
        ),
      'last_name' => array(
          'api.required' => '1',
          'title' => 'Last Name',
          'help_pre' => '',
          'help_post' => '',
          'entity' => 'contact',
          'name' => 'last_name',
          'type' => 2,
          'maxlength' => 64,
          'size' => 30,
          'import' => true,
          'where' => 'civicrm_contact.last_name',
          'headerPattern' => '/^last|(l(ast\s)?name)$/i',
          'dataPattern' => '/^\w+(\s\w+)?+$/',
          'export' => true,
          'api.aliases' => array(),
        ),
      'email-primary' => array(
          'api.required' => 1,
          'title' => 'Email',
          'help_pre' => '',
          'help_post' => '',
          'entity' => 'email',
          'api.aliases' => array(
              '0' => 'email-Primary',
            ),
          'name' => 'email',
          'type' => 2,
          'maxlength' => 254,
          'size' => 20,
          'import' => true,
          'where' => 'civicrm_email.email',
          'headerPattern' => '/e.?mail/i',
          'dataPattern' => '/^[a-zA-Z][\w\.-]*[a-zA-Z0-9]@[a-zA-Z0-9][\w\.-]*[a-zA-Z0-9]\.[a-zA-Z][a-zA-Z\.]*[a-zA-Z]$/',
          'export' => true,
          'rule' => 'email',
        ),
      'phone-1-1' => array(
          'api.required' => 1,
          'title' => 'Phone',
          'help_pre' => '',
          'help_post' => '',
          'entity' => 'phone',
          'name' => 'phone',
          'type' => 2,
          'maxlength' => 32,
          'size' => 20,
          'import' => true,
          'where' => 'civicrm_phone.phone',
          'headerPattern' => '/phone/i',
          'dataPattern' => '/^[\d\(\)\-\.\s]+$/',
          'export' => true,
          'api.aliases' => array(),
        ),
      'country-1' => array(
          'api.required' => '1',
          'title' => 'Country',
          'help_pre' => '',
          'help_post' => '',
          'entity' => 'address',
          'name' => 'country_id',
          'type' => 1,
          'FKClassName' => 'CRM_Core_DAO_Country',
          'pseudoconstant' => array(
              'table' => 'civicrm_country',
              'keyColumn' => 'id',
              'labelColumn' => 'name',
              'nameColumn' => 'iso_code',
            ),
          'api.aliases' => array(),
        ),
      'state_province-1' => array(
          'api.required' => '1',
          'title' => 'State',
          'help_pre' => '',
          'help_post' => '',
          'entity' => 'address',
          'name' => 'state_province_id',
          'type' => 1,
          'FKClassName' => 'CRM_Core_DAO_StateProvince',
          'pseudoconstant' => array(
              'table' => 'civicrm_state_province',
              'keyColumn' => 'id',
              'labelColumn' => 'name',
            ),
          'api.aliases' => array(),
        ),
      'postal_code-1' => array(
          'api.required' => 0,
          'title' => 'Postal Code',
          'help_pre' => '',
          'help_post' => '',
          'entity' => 'address',
          'name' => 'postal_code',
          'type' => 2,
          'maxlength' => 12,
          'size' => 12,
          'import' => true,
          'where' => 'civicrm_address.postal_code',
          'headerPattern' => '/postal|zip/i',
          'dataPattern' => '/\d?\d{4}(-\d{4})?/',
          'export' => true,
          'api.aliases' => array(),
        ),
      'custom_1' => array(
          'api.required' => '1',
          'title' => 'first_name',
          'help_pre' => '',
          'help_post' => '',
          'entity' => 'contact',
          'label' => '_addCustomFieldToProfile',
          'groupTitle' => '_addCustomFie',
          'data_type' => 'String',
          'html_type' => 'Text',
          'default_value' => 'defaultValue',
          'text_length' => '',
          'options_per_line' => '',
          'custom_group_id' => '1',
          'extends' => 'Contact',
          'is_search_range' => 0,
          'extends_entity_column_value' => '',
          'extends_entity_column_id' => '',
          'is_view' => 0,
          'is_multiple' => 0,
          'option_group_id' => '',
          'date_format' => '',
          'time_format' => '',
          'name' => 'custom_1',
          'type' => 2,
          'api.aliases' => array(),
        ),
      'profile_id' => array(
          'api.required' => true,
        ),
    ),
);

  return $expectedResult;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testGetFields and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/ProfileTest.php
*
* You can see the outcome of the API tests at
* http://tests.dev.civicrm.org/trunk/results-api_v3
*
* To Learn about the API read
* http://book.civicrm.org/developer/current/techniques/api/
*
* and review the wiki at
* http://wiki.civicrm.org/confluence/display/CRMDOC/CiviCRM+Public+APIs
*
* Read more about testing here
* http://wiki.civicrm.org/confluence/display/CRM/Testing
*
* API Standards documentation:
* http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
*/