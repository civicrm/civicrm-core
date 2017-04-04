<?php
/**
 * Test Generated example demonstrating the Profile.getfields API.
 *
 * demonstrates retrieving profile fields passing in an id
 *
 * @return array
 *   API result array
 */
function profile_getfields_example() {
  $params = array(
    'action' => 'submit',
    'profile_id' => 27,
  );

  try{
    $result = civicrm_api3('Profile', 'getfields', $params);
  }
  catch (CiviCRM_API3_Exception $e) {
    // Handle error here.
    $errorMessage = $e->getMessage();
    $errorCode = $e->getErrorCode();
    $errorData = $e->getExtraParams();
    return array(
      'is_error' => 1,
      'error_message' => $errorMessage,
      'error_code' => $errorCode,
      'error_data' => $errorData,
    );
  }

  return $result;
}

/**
 * Function returns array of result expected from previous function.
 *
 * @return array
 *   API result array
 */
function profile_getfields_expectedresult() {

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
        'table_name' => 'civicrm_value__addcustomfie_1',
        'column_name' => '_addcustomfieldtoprofile_1',
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
        'description' => 'Store both US (zip5) AND international postal codes. App is responsible for country/region appropriate validation.',
        'maxlength' => 64,
        'size' => 6,
        'import' => TRUE,
        'where' => 'civicrm_address.postal_code',
        'headerPattern' => '/postal|zip/i',
        'dataPattern' => '/\\d?\\d{4}(-\\d{4})?/',
        'export' => TRUE,
        'table_name' => 'civicrm_address',
        'entity' => 'address',
        'bao' => 'CRM_Core_BAO_Address',
        'html' => array(
          'type' => 'Text',
          'maxlength' => 64,
          'size' => 6,
        ),
        'api.required' => 0,
        'help_pre' => '',
        'help_post' => '',
        'weight' => '2',
        'api.aliases' => array(),
      ),
      'state_province-1' => array(
        'name' => 'state_province_id',
        'type' => 1,
        'title' => 'State Province',
        'description' => 'Which State_Province does this address belong to.',
        'table_name' => 'civicrm_address',
        'entity' => 'address',
        'bao' => 'CRM_Core_BAO_Address',
        'FKClassName' => 'CRM_Core_DAO_StateProvince',
        'html' => array(
          'type' => 'ChainSelect',
          'size' => 6,
          'maxlength' => 14,
        ),
        'pseudoconstant' => array(
          'table' => 'civicrm_state_province',
          'keyColumn' => 'id',
          'labelColumn' => 'name',
        ),
        'FKApiName' => 'StateProvince',
        'api.required' => '1',
        'help_pre' => '',
        'help_post' => '',
        'weight' => '3',
        'api.aliases' => array(),
      ),
      'country-1' => array(
        'name' => 'country_id',
        'type' => 1,
        'title' => 'Country',
        'description' => 'Which Country does this address belong to.',
        'table_name' => 'civicrm_address',
        'entity' => 'address',
        'bao' => 'CRM_Core_BAO_Address',
        'FKClassName' => 'CRM_Core_DAO_Country',
        'html' => array(
          'type' => 'Select',
          'size' => 6,
          'maxlength' => 14,
        ),
        'pseudoconstant' => array(
          'table' => 'civicrm_country',
          'keyColumn' => 'id',
          'labelColumn' => 'name',
          'nameColumn' => 'iso_code',
        ),
        'FKApiName' => 'Country',
        'api.required' => '1',
        'help_pre' => '',
        'help_post' => '',
        'weight' => '4',
        'api.aliases' => array(),
      ),
      'phone-1-1' => array(
        'name' => 'phone',
        'type' => 2,
        'title' => 'Phone',
        'description' => 'Complete phone number.',
        'maxlength' => 32,
        'size' => 20,
        'import' => TRUE,
        'where' => 'civicrm_phone.phone',
        'headerPattern' => '/phone/i',
        'dataPattern' => '/^[\\d\\(\\)\\-\\.\\s]+$/',
        'export' => TRUE,
        'table_name' => 'civicrm_phone',
        'entity' => 'phone',
        'bao' => 'CRM_Core_BAO_Phone',
        'html' => array(
          'type' => 'Text',
          'maxlength' => 32,
          'size' => 20,
        ),
        'api.required' => '1',
        'help_pre' => '',
        'help_post' => '',
        'weight' => '5',
        'api.aliases' => array(),
      ),
      'email-primary' => array(
        'name' => 'email',
        'type' => 2,
        'title' => 'Email',
        'description' => 'Email address',
        'maxlength' => 254,
        'size' => 30,
        'import' => TRUE,
        'where' => 'civicrm_email.email',
        'headerPattern' => '/e.?mail/i',
        'dataPattern' => '/^[a-zA-Z][\\w\\.-]*[a-zA-Z0-9]@[a-zA-Z0-9][\\w\\.-]*[a-zA-Z0-9]\\.[a-zA-Z][a-zA-Z\\.]*[a-zA-Z]$/',
        'export' => TRUE,
        'rule' => 'email',
        'table_name' => 'civicrm_email',
        'entity' => 'email',
        'bao' => 'CRM_Core_BAO_Email',
        'html' => array(
          'type' => 'Text',
          'maxlength' => 254,
          'size' => 30,
        ),
        'api.required' => '1',
        'help_pre' => '',
        'help_post' => '',
        'weight' => '6',
        'api.aliases' => array(
          '0' => 'email-Primary',
        ),
      ),
      'last_name' => array(
        'name' => 'last_name',
        'type' => 2,
        'title' => 'Last Name',
        'description' => 'Last Name.',
        'maxlength' => 64,
        'size' => 30,
        'import' => TRUE,
        'where' => 'civicrm_contact.last_name',
        'headerPattern' => '/^last|(l(ast\\s)?name)$/i',
        'dataPattern' => '/^\\w+(\\s\\w+)?+$/',
        'export' => TRUE,
        'table_name' => 'civicrm_contact',
        'entity' => 'contact',
        'bao' => 'CRM_Contact_BAO_Contact',
        'html' => array(
          'type' => 'Text',
          'maxlength' => 64,
          'size' => 30,
        ),
        'api.required' => '1',
        'help_pre' => '',
        'help_post' => '',
        'weight' => '7',
        'api.aliases' => array(),
      ),
      'first_name' => array(
        'name' => 'first_name',
        'type' => 2,
        'title' => 'First Name',
        'description' => 'First Name.',
        'maxlength' => 64,
        'size' => 30,
        'import' => TRUE,
        'where' => 'civicrm_contact.first_name',
        'headerPattern' => '/^first|(f(irst\\s)?name)$/i',
        'dataPattern' => '/^\\w+$/',
        'export' => TRUE,
        'table_name' => 'civicrm_contact',
        'entity' => 'contact',
        'bao' => 'CRM_Contact_BAO_Contact',
        'html' => array(
          'type' => 'Text',
          'maxlength' => 64,
          'size' => 30,
        ),
        'api.required' => '1',
        'help_pre' => '',
        'help_post' => '',
        'weight' => '8',
        'api.aliases' => array(),
      ),
      'profile_id' => array(
        'api.required' => TRUE,
        'title' => 'Profile ID',
        'name' => 'profile_id',
      ),
    ),
  );

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testGetFields"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/ProfileTest.php
*
* You can see the outcome of the API tests at
* https://test.civicrm.org/job/CiviCRM-master-git/
*
* To Learn about the API read
* http://wiki.civicrm.org/confluence/display/CRMDOC/Using+the+API
*
* Browse the api on your own site with the api explorer
* http://MYSITE.ORG/path/to/civicrm/api
*
* Read more about testing here
* http://wiki.civicrm.org/confluence/display/CRM/Testing
*
* API Standards documentation:
* http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
*/
