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
      'custom_1' => array(
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
          'is_required' => 0,
          'name' => 'custom_1',
          'title' => 'first_name',
          'type' => 2,
          'api.required' => '1',
          'help_pre' => '',
          'help_post' => '',
          'entity' => 'contact',
          'weight' => '1',
          'api.aliases' => array(),
        ),
      'postal_code-1' => array(
          'name' => 'postal_code',
          'type' => 2,
          'title' => 'State Province',
          'maxlength' => 12,
          'size' => 12,
          'import' => true,
          'where' => 'civicrm_address.postal_code',
          'headerPattern' => '/postal|zip/i',
          'dataPattern' => '/\\d?\\d{4}(-\\d{4})?/',
          'export' => true,
          'api.required' => 0,
          'help_pre' => '',
          'help_post' => '',
          'entity' => 'address',
          'weight' => '2',
          'api.aliases' => array(),
        ),
      'state_province-1' => array(
          'name' => 'state_province_id',
          'type' => 1,
          'title' => 'State Province',
          'FKClassName' => 'CRM_Core_DAO_StateProvince',
          'html' => array(
              'type' => 'Select',
            ),
          'pseudoconstant' => array(
              'table' => 'civicrm_state_province',
              'keyColumn' => 'id',
              'labelColumn' => 'name',
            ),
          'api.required' => '1',
          'help_pre' => '',
          'help_post' => '',
          'entity' => 'address',
          'weight' => '3',
          'api.aliases' => array(),
        ),
      'country-1' => array(
          'name' => 'country_id',
          'type' => 1,
          'title' => 'Country',
          'FKClassName' => 'CRM_Core_DAO_Country',
          'html' => array(
              'type' => 'Select',
            ),
          'pseudoconstant' => array(
              'table' => 'civicrm_country',
              'keyColumn' => 'id',
              'labelColumn' => 'name',
              'nameColumn' => 'iso_code',
            ),
          'api.required' => '1',
          'help_pre' => '',
          'help_post' => '',
          'entity' => 'address',
          'weight' => '4',
          'api.aliases' => array(),
        ),
      'phone-1-1' => array(
          'name' => 'phone',
          'type' => 2,
          'title' => 'Phone',
          'maxlength' => 32,
          'size' => 20,
          'import' => true,
          'where' => 'civicrm_phone.phone',
          'headerPattern' => '/phone/i',
          'dataPattern' => '/^[\\d\\(\\)\\-\\.\\s]+$/',
          'export' => true,
          'api.required' => '1',
          'help_pre' => '',
          'help_post' => '',
          'entity' => 'phone',
          'weight' => '5',
          'api.aliases' => array(),
        ),
      'email-primary' => array(
          'name' => 'email',
          'type' => 2,
          'title' => 'Email',
          'maxlength' => 254,
          'size' => 20,
          'import' => true,
          'where' => 'civicrm_email.email',
          'headerPattern' => '/e.?mail/i',
          'dataPattern' => '/^[a-zA-Z][\\w\\.-]*[a-zA-Z0-9]@[a-zA-Z0-9][\\w\\.-]*[a-zA-Z0-9]\\.[a-zA-Z][a-zA-Z\\.]*[a-zA-Z]$/',
          'export' => true,
          'rule' => 'email',
          'html' => array(
              'type' => 'Text',
            ),
          'api.required' => '1',
          'help_pre' => '',
          'help_post' => '',
          'entity' => 'email',
          'weight' => '6',
          'api.aliases' => array(
              '0' => 'email-Primary',
            ),
        ),
      'last_name' => array(
          'name' => 'last_name',
          'type' => 2,
          'title' => 'Last Name',
          'maxlength' => 64,
          'size' => 30,
          'import' => true,
          'where' => 'civicrm_contact.last_name',
          'headerPattern' => '/^last|(l(ast\\s)?name)$/i',
          'dataPattern' => '/^\\w+(\\s\\w+)?+$/',
          'export' => true,
          'html' => array(
              'type' => 'Text',
            ),
          'api.required' => '1',
          'help_pre' => '',
          'help_post' => '',
          'entity' => 'contact',
          'weight' => '7',
          'api.aliases' => array(),
        ),
      'first_name' => array(
          'name' => 'first_name',
          'type' => 2,
          'title' => 'First Name',
          'maxlength' => 64,
          'size' => 30,
          'import' => true,
          'where' => 'civicrm_contact.first_name',
          'headerPattern' => '/^first|(f(irst\\s)?name)$/i',
          'dataPattern' => '/^\\w+$/',
          'export' => true,
          'html' => array(
              'type' => 'Text',
            ),
          'api.required' => '1',
          'help_pre' => '',
          'help_post' => '',
          'entity' => 'contact',
          'weight' => '8',
          'api.aliases' => array(),
        ),
      'profile_id' => array(
          'api.required' => true,
          'title' => 'Profile ID',
        ),
    ),
);

  return $expectedResult;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testGetFields and can be found in
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/ProfileTest.php
*
* You can see the outcome of the API tests at
* https://test.civicrm.org/job/CiviCRM-master-git/
*
* To Learn about the API read
* http://wiki.civicrm.org/confluence/display/CRMDOC/Using+the+API
*
* Browse the api on your own site with the api explorer
* http://MYSITE.ORG/path/to/civicrm/api/explorer
*
* Read more about testing here
* http://wiki.civicrm.org/confluence/display/CRM/Testing
*
* API Standards documentation:
* http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
*/
