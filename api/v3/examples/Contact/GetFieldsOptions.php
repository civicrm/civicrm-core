<?php
/**
 * Test Generated example demonstrating the Contact.getfields API.
 *
 * Demonstrates retrieving metadata with custom field options.
 *
 * @return array
 *   API result array
 */
function contact_getfields_example() {
  $params = array(
    'options' => array(
      'get_options' => 'custom_1',
    ),
    'action' => 'create',
  );

  try{
    $result = civicrm_api3('Contact', 'getfields', $params);
  }
  catch (CiviCRM_API3_Exception $e) {
    // Handle error here.
    $errorMessage = $e->getMessage();
    $errorCode = $e->getErrorCode();
    $errorData = $e->getExtraParams();
    return array(
      'error' => $errorMessage,
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
function contact_getfields_expectedresult() {

  $expectedResult = array(
    'is_error' => 0,
    'version' => 3,
    'count' => 55,
    'values' => array(
      'id' => array(
        'name' => 'id',
        'type' => 1,
        'title' => 'Internal Contact ID',
        'required' => TRUE,
        'import' => TRUE,
        'where' => 'civicrm_contact.id',
        'headerPattern' => '/internal|contact?|id$/i',
        'export' => TRUE,
        'api.aliases' => array(
          '0' => 'contact_id',
        ),
      ),
      'contact_type' => array(
        'name' => 'contact_type',
        'type' => 2,
        'title' => 'Contact Type',
        'maxlength' => 64,
        'size' => 30,
        'export' => TRUE,
        'where' => 'civicrm_contact.contact_type',
        'html' => array(
          'type' => 'Select',
        ),
        'pseudoconstant' => array(
          'table' => 'civicrm_contact_type',
          'keyColumn' => 'name',
          'labelColumn' => 'label',
          'condition' => 'parent_id IS NULL',
        ),
        'api.required' => 1,
      ),
      'contact_sub_type' => array(
        'name' => 'contact_sub_type',
        'type' => 2,
        'title' => 'Contact Subtype',
        'maxlength' => 255,
        'size' => 45,
        'import' => TRUE,
        'where' => 'civicrm_contact.contact_sub_type',
        'headerPattern' => '/C(ontact )?(subtype|sub-type|sub type)/i',
        'export' => TRUE,
        'html' => array(
          'type' => 'Multi-Select',
        ),
        'pseudoconstant' => array(
          'table' => 'civicrm_contact_type',
          'keyColumn' => 'name',
          'labelColumn' => 'label',
          'condition' => 'parent_id IS NOT NULL',
        ),
      ),
      'do_not_email' => array(
        'name' => 'do_not_email',
        'type' => 16,
        'title' => 'Do Not Email',
        'import' => TRUE,
        'where' => 'civicrm_contact.do_not_email',
        'headerPattern' => '/d(o )?(not )?(email)/i',
        'dataPattern' => '/^\\d{1,}$/',
        'export' => TRUE,
        'html' => array(
          'type' => 'CheckBox',
        ),
      ),
      'do_not_phone' => array(
        'name' => 'do_not_phone',
        'type' => 16,
        'title' => 'Do Not Phone',
        'import' => TRUE,
        'where' => 'civicrm_contact.do_not_phone',
        'headerPattern' => '/d(o )?(not )?(call|phone)/i',
        'dataPattern' => '/^\\d{1,}$/',
        'export' => TRUE,
        'html' => array(
          'type' => 'CheckBox',
        ),
      ),
      'do_not_mail' => array(
        'name' => 'do_not_mail',
        'type' => 16,
        'title' => 'Do Not Mail',
        'import' => TRUE,
        'where' => 'civicrm_contact.do_not_mail',
        'headerPattern' => '/^(d(o\\s)?n(ot\\s)?mail)|(\\w*)?bulk\\s?(\\w*)$/i',
        'dataPattern' => '/^\\d{1,}$/',
        'export' => TRUE,
        'html' => array(
          'type' => 'CheckBox',
        ),
      ),
      'do_not_sms' => array(
        'name' => 'do_not_sms',
        'type' => 16,
        'title' => 'Do Not Sms',
        'import' => TRUE,
        'where' => 'civicrm_contact.do_not_sms',
        'headerPattern' => '/d(o )?(not )?(sms)/i',
        'dataPattern' => '/^\\d{1,}$/',
        'export' => TRUE,
        'html' => array(
          'type' => 'CheckBox',
        ),
      ),
      'do_not_trade' => array(
        'name' => 'do_not_trade',
        'type' => 16,
        'title' => 'Do Not Trade',
        'import' => TRUE,
        'where' => 'civicrm_contact.do_not_trade',
        'headerPattern' => '/d(o )?(not )?(trade)/i',
        'dataPattern' => '/^\\d{1,}$/',
        'export' => TRUE,
        'html' => array(
          'type' => 'CheckBox',
        ),
      ),
      'is_opt_out' => array(
        'name' => 'is_opt_out',
        'type' => 16,
        'title' => 'No Bulk Emails (User Opt Out)',
        'required' => TRUE,
        'import' => TRUE,
        'where' => 'civicrm_contact.is_opt_out',
        'export' => TRUE,
        'html' => array(
          'type' => 'CheckBox',
        ),
      ),
      'legal_identifier' => array(
        'name' => 'legal_identifier',
        'type' => 2,
        'title' => 'Legal Identifier',
        'maxlength' => 32,
        'size' => 20,
        'import' => TRUE,
        'where' => 'civicrm_contact.legal_identifier',
        'headerPattern' => '/legal\\s?id/i',
        'dataPattern' => '/\\w+?\\d{5,}/',
        'export' => TRUE,
        'html' => array(
          'type' => 'Text',
        ),
      ),
      'external_identifier' => array(
        'name' => 'external_identifier',
        'type' => 2,
        'title' => 'External Identifier',
        'maxlength' => 64,
        'size' => 8,
        'import' => TRUE,
        'where' => 'civicrm_contact.external_identifier',
        'headerPattern' => '/external\\s?id/i',
        'dataPattern' => '/^\\d{11,}$/',
        'export' => TRUE,
        'html' => array(
          'type' => 'Text',
        ),
      ),
      'sort_name' => array(
        'name' => 'sort_name',
        'type' => 2,
        'title' => 'Sort Name',
        'maxlength' => 128,
        'size' => 30,
        'export' => TRUE,
        'where' => 'civicrm_contact.sort_name',
        'html' => array(
          'type' => 'Text',
        ),
      ),
      'display_name' => array(
        'name' => 'display_name',
        'type' => 2,
        'title' => 'Display Name',
        'maxlength' => 128,
        'size' => 30,
        'export' => TRUE,
        'where' => 'civicrm_contact.display_name',
        'html' => array(
          'type' => 'Text',
        ),
      ),
      'nick_name' => array(
        'name' => 'nick_name',
        'type' => 2,
        'title' => 'Nickname',
        'maxlength' => 128,
        'size' => 30,
        'import' => TRUE,
        'where' => 'civicrm_contact.nick_name',
        'headerPattern' => '/n(ick\\s)name|nick$/i',
        'dataPattern' => '/^\\w+$/',
        'export' => TRUE,
        'html' => array(
          'type' => 'Text',
        ),
      ),
      'legal_name' => array(
        'name' => 'legal_name',
        'type' => 2,
        'title' => 'Legal Name',
        'maxlength' => 128,
        'size' => 30,
        'import' => TRUE,
        'where' => 'civicrm_contact.legal_name',
        'headerPattern' => '/^legal|(l(egal\\s)?name)$/i',
        'export' => TRUE,
        'html' => array(
          'type' => 'Text',
        ),
      ),
      'image_URL' => array(
        'name' => 'image_URL',
        'type' => 2,
        'title' => 'Image Url',
        'maxlength' => 255,
        'size' => 45,
        'import' => TRUE,
        'where' => 'civicrm_contact.image_URL',
        'export' => TRUE,
        'html' => array(
          'type' => 'Text',
        ),
      ),
      'preferred_communication_method' => array(
        'name' => 'preferred_communication_method',
        'type' => 2,
        'title' => 'Preferred Communication Method',
        'maxlength' => 255,
        'size' => 45,
        'import' => TRUE,
        'where' => 'civicrm_contact.preferred_communication_method',
        'headerPattern' => '/^p(ref\\w*\\s)?c(omm\\w*)|( meth\\w*)$/i',
        'dataPattern' => '/^\\w+$/',
        'export' => TRUE,
        'html' => array(
          'type' => 'Select',
        ),
        'pseudoconstant' => array(
          'optionGroupName' => 'preferred_communication_method',
        ),
      ),
      'preferred_language' => array(
        'name' => 'preferred_language',
        'type' => 2,
        'title' => 'Preferred Language',
        'maxlength' => 5,
        'size' => 6,
        'import' => TRUE,
        'where' => 'civicrm_contact.preferred_language',
        'headerPattern' => '/^lang/i',
        'export' => TRUE,
        'html' => array(
          'type' => 'Select',
        ),
        'pseudoconstant' => array(
          'optionGroupName' => 'languages',
          'keyColumn' => 'name',
        ),
      ),
      'preferred_mail_format' => array(
        'name' => 'preferred_mail_format',
        'type' => 2,
        'title' => 'Preferred Mail Format',
        'maxlength' => 8,
        'size' => 8,
        'import' => TRUE,
        'where' => 'civicrm_contact.preferred_mail_format',
        'headerPattern' => '/^p(ref\\w*\\s)?m(ail\\s)?f(orm\\w*)$/i',
        'export' => TRUE,
        'default' => 'Both',
        'html' => array(
          'type' => 'Select',
        ),
        'pseudoconstant' => array(
          'callback' => 'CRM_Core_SelectValues::pmf',
        ),
      ),
      'hash' => array(
        'name' => 'hash',
        'type' => 2,
        'title' => 'Contact Hash',
        'maxlength' => 32,
        'size' => 20,
        'export' => TRUE,
        'where' => 'civicrm_contact.hash',
      ),
      'api_key' => array(
        'name' => 'api_key',
        'type' => 2,
        'title' => 'Api Key',
        'maxlength' => 32,
        'size' => 20,
      ),
      'first_name' => array(
        'name' => 'first_name',
        'type' => 2,
        'title' => 'First Name',
        'maxlength' => 64,
        'size' => 30,
        'import' => TRUE,
        'where' => 'civicrm_contact.first_name',
        'headerPattern' => '/^first|(f(irst\\s)?name)$/i',
        'dataPattern' => '/^\\w+$/',
        'export' => TRUE,
        'html' => array(
          'type' => 'Text',
        ),
      ),
      'middle_name' => array(
        'name' => 'middle_name',
        'type' => 2,
        'title' => 'Middle Name',
        'maxlength' => 64,
        'size' => 20,
        'import' => TRUE,
        'where' => 'civicrm_contact.middle_name',
        'headerPattern' => '/^middle|(m(iddle\\s)?name)$/i',
        'dataPattern' => '/^\\w+$/',
        'export' => TRUE,
        'html' => array(
          'type' => 'Text',
        ),
      ),
      'last_name' => array(
        'name' => 'last_name',
        'type' => 2,
        'title' => 'Last Name',
        'maxlength' => 64,
        'size' => 30,
        'import' => TRUE,
        'where' => 'civicrm_contact.last_name',
        'headerPattern' => '/^last|(l(ast\\s)?name)$/i',
        'dataPattern' => '/^\\w+(\\s\\w+)?+$/',
        'export' => TRUE,
        'html' => array(
          'type' => 'Text',
        ),
      ),
      'prefix_id' => array(
        'name' => 'prefix_id',
        'type' => 1,
        'title' => 'Individual Prefix',
        'import' => TRUE,
        'where' => 'civicrm_contact.prefix_id',
        'headerPattern' => '/^(prefix|title)/i',
        'dataPattern' => '/^(mr|ms|mrs|sir|dr)\\.?$/i',
        'export' => TRUE,
        'html' => array(
          'type' => 'Select',
        ),
        'pseudoconstant' => array(
          'optionGroupName' => 'individual_prefix',
        ),
        'api.aliases' => array(
          '0' => 'individual_prefix',
          '1' => 'individual_prefix_id',
        ),
      ),
      'suffix_id' => array(
        'name' => 'suffix_id',
        'type' => 1,
        'title' => 'Individual Suffix',
        'import' => TRUE,
        'where' => 'civicrm_contact.suffix_id',
        'headerPattern' => '/^suffix$/i',
        'dataPattern' => '/^(sr|jr)\\.?|i{2,}$/',
        'export' => TRUE,
        'html' => array(
          'type' => 'Select',
        ),
        'pseudoconstant' => array(
          'optionGroupName' => 'individual_suffix',
        ),
        'api.aliases' => array(
          '0' => 'individual_suffix',
          '1' => 'individual_suffix_id',
        ),
      ),
      'formal_title' => array(
        'name' => 'formal_title',
        'type' => 2,
        'title' => 'Formal Title',
        'maxlength' => 64,
        'size' => 30,
        'import' => TRUE,
        'where' => 'civicrm_contact.formal_title',
        'headerPattern' => '/^title/i',
        'export' => TRUE,
      ),
      'communication_style_id' => array(
        'name' => 'communication_style_id',
        'type' => 1,
        'title' => 'Communication Style',
        'export' => TRUE,
        'where' => 'civicrm_contact.communication_style_id',
        'pseudoconstant' => array(
          'optionGroupName' => 'communication_style',
        ),
      ),
      'email_greeting_id' => array(
        'name' => 'email_greeting_id',
        'type' => 1,
        'title' => 'Email Greeting ID',
      ),
      'email_greeting_custom' => array(
        'name' => 'email_greeting_custom',
        'type' => 2,
        'title' => 'Email Greeting Custom',
        'maxlength' => 128,
        'size' => 45,
        'import' => TRUE,
        'where' => 'civicrm_contact.email_greeting_custom',
        'html' => array(
          'type' => 'Text',
        ),
      ),
      'email_greeting_display' => array(
        'name' => 'email_greeting_display',
        'type' => 2,
        'title' => 'Email Greeting',
        'maxlength' => 255,
        'size' => 45,
        'html' => array(
          'type' => 'Text',
        ),
      ),
      'postal_greeting_id' => array(
        'name' => 'postal_greeting_id',
        'type' => 1,
        'title' => 'Postal Greeting ID',
        'html' => array(
          'type' => 'Text',
        ),
      ),
      'postal_greeting_custom' => array(
        'name' => 'postal_greeting_custom',
        'type' => 2,
        'title' => 'Postal Greeting Custom',
        'maxlength' => 128,
        'size' => 45,
        'import' => TRUE,
        'where' => 'civicrm_contact.postal_greeting_custom',
        'html' => array(
          'type' => 'Text',
        ),
      ),
      'postal_greeting_display' => array(
        'name' => 'postal_greeting_display',
        'type' => 2,
        'title' => 'Postal Greeting',
        'maxlength' => 255,
        'size' => 45,
        'html' => array(
          'type' => 'Text',
        ),
      ),
      'addressee_id' => array(
        'name' => 'addressee_id',
        'type' => 1,
        'title' => 'Addressee ID',
      ),
      'addressee_custom' => array(
        'name' => 'addressee_custom',
        'type' => 2,
        'title' => 'Addressee Custom',
        'maxlength' => 128,
        'size' => 45,
        'import' => TRUE,
        'where' => 'civicrm_contact.addressee_custom',
        'html' => array(
          'type' => 'Text',
        ),
      ),
      'addressee_display' => array(
        'name' => 'addressee_display',
        'type' => 2,
        'title' => 'Addressee',
        'maxlength' => 255,
        'size' => 45,
        'html' => array(
          'type' => 'Text',
        ),
      ),
      'job_title' => array(
        'name' => 'job_title',
        'type' => 2,
        'title' => 'Job Title',
        'maxlength' => 255,
        'size' => 20,
        'import' => TRUE,
        'where' => 'civicrm_contact.job_title',
        'headerPattern' => '/^job|(j(ob\\s)?title)$/i',
        'dataPattern' => '//',
        'export' => TRUE,
        'html' => array(
          'type' => 'Text',
        ),
      ),
      'gender_id' => array(
        'name' => 'gender_id',
        'type' => 1,
        'title' => 'Gender',
        'import' => TRUE,
        'where' => 'civicrm_contact.gender_id',
        'headerPattern' => '/^gender$/i',
        'export' => TRUE,
        'html' => array(
          'type' => 'Select',
        ),
        'pseudoconstant' => array(
          'optionGroupName' => 'gender',
        ),
      ),
      'birth_date' => array(
        'name' => 'birth_date',
        'type' => 4,
        'title' => 'Birth Date',
        'import' => TRUE,
        'where' => 'civicrm_contact.birth_date',
        'headerPattern' => '/^birth|(b(irth\\s)?date)|D(\\W*)O(\\W*)B(\\W*)$/i',
        'dataPattern' => '/\\d{4}-?\\d{2}-?\\d{2}/',
        'export' => TRUE,
        'html' => array(
          'type' => 'Select Date',
        ),
      ),
      'is_deceased' => array(
        'name' => 'is_deceased',
        'type' => 16,
        'title' => 'Deceased',
        'import' => TRUE,
        'where' => 'civicrm_contact.is_deceased',
        'headerPattern' => '/i(s\\s)?d(eceased)$/i',
        'export' => TRUE,
        'html' => array(
          'type' => 'CheckBox',
        ),
      ),
      'deceased_date' => array(
        'name' => 'deceased_date',
        'type' => 4,
        'title' => 'Deceased Date',
        'import' => TRUE,
        'where' => 'civicrm_contact.deceased_date',
        'headerPattern' => '/^deceased|(d(eceased\\s)?date)$/i',
        'export' => TRUE,
        'html' => array(
          'type' => 'Select Date',
        ),
      ),
      'household_name' => array(
        'name' => 'household_name',
        'type' => 2,
        'title' => 'Household Name',
        'maxlength' => 128,
        'size' => 30,
        'import' => TRUE,
        'where' => 'civicrm_contact.household_name',
        'headerPattern' => '/^household|(h(ousehold\\s)?name)$/i',
        'dataPattern' => '/^\\w+$/',
        'export' => TRUE,
        'html' => array(
          'type' => 'Text',
        ),
      ),
      'primary_contact_id' => array(
        'name' => 'primary_contact_id',
        'type' => 1,
        'title' => 'Household Primary Contact ID',
        'FKClassName' => 'CRM_Contact_DAO_Contact',
        'html' => array(
          'type' => 'Select',
        ),
        'FKApiName' => 'Contact',
      ),
      'organization_name' => array(
        'name' => 'organization_name',
        'type' => 2,
        'title' => 'Organization Name',
        'maxlength' => 128,
        'size' => 30,
        'import' => TRUE,
        'where' => 'civicrm_contact.organization_name',
        'headerPattern' => '/^organization|(o(rganization\\s)?name)$/i',
        'dataPattern' => '/^\\w+$/',
        'export' => TRUE,
        'html' => array(
          'type' => 'Text',
        ),
      ),
      'sic_code' => array(
        'name' => 'sic_code',
        'type' => 2,
        'title' => 'Sic Code',
        'maxlength' => 8,
        'size' => 8,
        'import' => TRUE,
        'where' => 'civicrm_contact.sic_code',
        'headerPattern' => '/^sic|(s(ic\\s)?code)$/i',
        'export' => TRUE,
        'html' => array(
          'type' => 'Text',
        ),
      ),
      'user_unique_id' => array(
        'name' => 'user_unique_id',
        'type' => 2,
        'title' => 'Unique ID (OpenID)',
        'maxlength' => 255,
        'size' => 45,
        'import' => TRUE,
        'where' => 'civicrm_contact.user_unique_id',
        'headerPattern' => '/^Open\\s?ID|u(niq\\w*)?\\s?ID/i',
        'dataPattern' => '/^[\\w\\/\\:\\.]+$/',
        'export' => TRUE,
        'rule' => 'url',
        'html' => array(
          'type' => 'Text',
        ),
      ),
      'created_date' => array(
        'name' => 'created_date',
        'type' => 256,
        'title' => 'Created Date',
        'required' => '',
        'export' => TRUE,
        'where' => 'civicrm_contact.created_date',
        'default' => 'NULL',
      ),
      'modified_date' => array(
        'name' => 'modified_date',
        'type' => 256,
        'title' => 'Modified Date',
        'required' => '',
        'export' => TRUE,
        'where' => 'civicrm_contact.modified_date',
        'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
      ),
      'source' => array(
        'name' => 'source',
        'type' => 2,
        'title' => 'Contact Source',
        'maxlength' => 255,
        'size' => 30,
        'import' => TRUE,
        'where' => 'civicrm_contact.source',
        'headerPattern' => '/(S(ource\\s)?o(f\\s)?C(ontact\\s)?Data)$/i',
        'export' => TRUE,
        'html' => array(
          'type' => 'Text',
        ),
        'uniqueName' => 'contact_source',
      ),
      'employer_id' => array(
        'name' => 'employer_id',
        'type' => 1,
        'title' => 'Current Employer ID',
        'export' => TRUE,
        'where' => 'civicrm_contact.employer_id',
        'FKClassName' => 'CRM_Contact_DAO_Contact',
        'html' => array(
          'type' => 'Autocomplete-Select',
        ),
        'uniqueName' => 'current_employer_id',
        'FKApiName' => 'Contact',
      ),
      'is_deleted' => array(
        'name' => 'is_deleted',
        'type' => 16,
        'title' => 'Contact is in Trash',
        'required' => TRUE,
        'export' => TRUE,
        'where' => 'civicrm_contact.is_deleted',
        'html' => array(
          'type' => 'CheckBox',
        ),
        'uniqueName' => 'contact_is_deleted',
      ),
      'custom_1' => array(
        'label' => 'Our special field',
        'groupTitle' => 'select_test_g',
        'data_type' => 'String',
        'html_type' => 'Select',
        'default_value' => '',
        'text_length' => '',
        'options_per_line' => '',
        'custom_group_id' => '1',
        'extends' => 'Contact',
        'is_search_range' => 0,
        'extends_entity_column_value' => '',
        'extends_entity_column_id' => '',
        'is_view' => 0,
        'is_multiple' => 0,
        'option_group_id' => '106',
        'date_format' => '',
        'time_format' => '',
        'is_required' => '1',
        'name' => 'custom_1',
        'title' => 'Our special field',
        'type' => 2,
        'options' => array(
          '1' => 'Label1',
          '2' => 'Label2',
        ),
      ),
      'current_employer' => array(
        'title' => 'Current Employer',
        'description' => 'Name of Current Employer',
        'type' => 2,
        'name' => 'current_employer',
      ),
      'dupe_check' => array(
        'title' => 'Check for Duplicates',
        'description' => 'Throw error if contact create matches dedupe rule',
        'name' => 'dupe_check',
      ),
    ),
  );

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testCustomFieldCreateWithOptionValues"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/ContactTest.php
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
