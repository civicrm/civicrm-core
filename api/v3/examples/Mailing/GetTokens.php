<?php
/**
 * Test Generated example demonstrating the Mailing.gettokens API.
 *
 * Demonstrates fetching tokens for one or more entities (in this case "Contact" and "Mailing").
 * Optionally pass sequential=1 to have output ready-formatted for the select2 widget.
 *
 * @return array
 *   API result array
 */
function mailing_gettokens_example() {
  $params = array(
    'entity' => array(
      '0' => 'Contact',
      '1' => 'Mailing',
    ),
  );

  try{
    $result = civicrm_api3('Mailing', 'gettokens', $params);
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
function mailing_gettokens_expectedresult() {

  $expectedResult = array(
    'is_error' => 0,
    'version' => 3,
    'count' => 82,
    'values' => array(
      '{action.unsubscribe}' => 'Unsubscribe via email',
      '{action.unsubscribeUrl}' => 'Unsubscribe via web page',
      '{action.resubscribe}' => 'Resubscribe via email',
      '{action.resubscribeUrl}' => 'Resubscribe via web page',
      '{action.optOut}' => 'Opt out via email',
      '{action.optOutUrl}' => 'Opt out via web page',
      '{action.forward}' => 'Forward this email (link)',
      '{action.reply}' => 'Reply to this email (link)',
      '{action.subscribeUrl}' => 'Subscribe via web page',
      '{domain.name}' => 'Domain name',
      '{domain.address}' => 'Domain (organization) address',
      '{domain.phone}' => 'Domain (organization) phone',
      '{domain.email}' => 'Domain (organization) email',
      '{mailing.name}' => 'Mailing name',
      '{mailing.group}' => 'Mailing group',
      '{mailing.viewUrl}' => 'Mailing permalink',
      '{contact.contact_type}' => 'Contact Type',
      '{contact.do_not_email}' => 'Do Not Email',
      '{contact.do_not_phone}' => 'Do Not Phone',
      '{contact.do_not_mail}' => 'Do Not Mail',
      '{contact.do_not_sms}' => 'Do Not Sms',
      '{contact.do_not_trade}' => 'Do Not Trade',
      '{contact.is_opt_out}' => 'No Bulk Emails (User Opt Out)',
      '{contact.external_identifier}' => 'External Identifier',
      '{contact.sort_name}' => 'Sort Name',
      '{contact.display_name}' => 'Display Name',
      '{contact.nick_name}' => 'Nickname',
      '{contact.image_URL}' => 'Image Url',
      '{contact.preferred_communication_method}' => 'Preferred Communication Method',
      '{contact.preferred_language}' => 'Preferred Language',
      '{contact.preferred_mail_format}' => 'Preferred Mail Format',
      '{contact.hash}' => 'Contact Hash',
      '{contact.contact_source}' => 'Contact Source',
      '{contact.first_name}' => 'First Name',
      '{contact.middle_name}' => 'Middle Name',
      '{contact.last_name}' => 'Last Name',
      '{contact.individual_prefix}' => 'Individual Prefix',
      '{contact.individual_suffix}' => 'Individual Suffix',
      '{contact.formal_title}' => 'Formal Title',
      '{contact.communication_style}' => 'Communication Style',
      '{contact.job_title}' => 'Job Title',
      '{contact.gender}' => 'Gender',
      '{contact.birth_date}' => 'Birth Date',
      '{contact.current_employer_id}' => 'Current Employer ID',
      '{contact.contact_is_deleted}' => 'Contact is in Trash',
      '{contact.created_date}' => 'Created Date',
      '{contact.modified_date}' => 'Modified Date',
      '{contact.addressee}' => 'Addressee',
      '{contact.email_greeting}' => 'Email Greeting',
      '{contact.postal_greeting}' => 'Postal Greeting',
      '{contact.current_employer}' => 'Current Employer',
      '{contact.location_type}' => 'Location Type',
      '{contact.street_address}' => 'Street Address',
      '{contact.street_number}' => 'Street Number',
      '{contact.street_number_suffix}' => 'Street Number Suffix',
      '{contact.street_name}' => 'Street Name',
      '{contact.street_unit}' => 'Street Unit',
      '{contact.supplemental_address_1}' => 'Supplemental Address 1',
      '{contact.supplemental_address_2}' => 'Supplemental Address 2',
      '{contact.supplemental_address_3}' => 'Supplemental Address 3',
      '{contact.city}' => 'City',
      '{contact.postal_code_suffix}' => 'Postal Code Suffix',
      '{contact.postal_code}' => 'Postal Code',
      '{contact.geo_code_1}' => 'Latitude',
      '{contact.geo_code_2}' => 'Longitude',
      '{contact.address_name}' => 'Address Name',
      '{contact.master_id}' => 'Master Address Belongs To',
      '{contact.county}' => 'County',
      '{contact.state_province}' => 'State',
      '{contact.country}' => 'Country',
      '{contact.phone}' => 'Phone',
      '{contact.phone_ext}' => 'Phone Extension',
      '{contact.email}' => 'Email',
      '{contact.on_hold}' => 'On Hold',
      '{contact.signature_text}' => 'Signature Text',
      '{contact.signature_html}' => 'Signature Html',
      '{contact.im_provider}' => 'IM Provider',
      '{contact.im}' => 'IM Screen Name',
      '{contact.openid}' => 'OpenID',
      '{contact.world_region}' => 'World Region',
      '{contact.url}' => 'Website',
      '{contact.checksum}' => 'Checksum',
      '{contact.contact_id}' => 'Internal Contact ID',
    ),
  );

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testMailGetTokens"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/MailingTest.php
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
