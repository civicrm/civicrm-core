<?php

/**
 * @file
 */

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
  $params = [
    'entity' => [
      '0' => 'Contact',
      '1' => 'Mailing',
    ],
  ];

  try {
    $result = civicrm_api3('Mailing', 'gettokens', $params);
  }
  catch (CRM_Core_Exception $e) {
    // Handle error here.
    $errorMessage = $e->getMessage();
    $errorCode = $e->getErrorCode();
    $errorData = $e->getExtraParams();
    return [
      'is_error' => 1,
      'error_message' => $errorMessage,
      'error_code' => $errorCode,
      'error_data' => $errorData,
    ];
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

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 90,
    'values' => [
      '{action.unsubscribe}' => 'Unsubscribe via email',
      '{action.unsubscribeUrl}' => 'Unsubscribe via web page',
      '{action.resubscribe}' => 'Resubscribe via email',
      '{action.resubscribeUrl}' => 'Resubscribe via web page',
      '{action.optOut}' => 'Opt out via email',
      '{action.optOutUrl}' => 'Opt out via web page',
      '{action.forward}' => 'Forward this email (link)',
      '{action.reply}' => 'Reply to this email (link)',
      '{action.subscribeUrl}' => 'Subscribe via web page',
      '{mailing.key}' => 'Mailing key',
      '{mailing.name}' => 'Mailing name',
      '{mailing.group}' => 'Mailing group',
      '{mailing.viewUrl}' => 'Mailing permalink',
      '{domain.name}' => 'Domain name',
      '{domain.address}' => 'Domain (organization) address',
      '{domain.phone}' => 'Domain (organization) phone',
      '{domain.email}' => 'Domain (organization) email',
      '{domain.id}' => 'Domain ID',
      '{domain.description}' => 'Domain Description',
      '{domain.now}' => 'Current time/date',
      '{domain.base_url}' => 'Domain absolute base url',
      '{domain.tax_term}' => 'Sales tax term (e.g VAT)',
      '{contact.checksum}' => 'Checksum',
      '{contact.current_employer}' => 'Current Employer',
      '{contact.world_region}' => 'World Region',
      '{contact.id}' => 'Contact ID',
      '{contact.contact_type:label}' => 'Contact Type',
      '{contact.do_not_email:label}' => 'Do Not Email',
      '{contact.do_not_phone:label}' => 'Do Not Phone',
      '{contact.do_not_mail:label}' => 'Do Not Mail',
      '{contact.do_not_sms:label}' => 'Do Not Sms',
      '{contact.do_not_trade:label}' => 'Do Not Trade',
      '{contact.is_opt_out:label}' => 'No Bulk Emails (User Opt Out)',
      '{contact.external_identifier}' => 'External Identifier',
      '{contact.sort_name}' => 'Sort Name',
      '{contact.display_name}' => 'Display Name',
      '{contact.nick_name}' => 'Nickname',
      '{contact.image_URL}' => 'Image Url',
      '{contact.preferred_communication_method:label}' => 'Preferred Communication Method',
      '{contact.preferred_language:label}' => 'Preferred Language',
      '{contact.preferred_mail_format:label}' => 'Preferred Mail Format',
      '{contact.hash}' => 'Contact Hash',
      '{contact.source}' => 'Contact Source',
      '{contact.first_name}' => 'First Name',
      '{contact.middle_name}' => 'Middle Name',
      '{contact.last_name}' => 'Last Name',
      '{contact.prefix_id:label}' => 'Individual Prefix',
      '{contact.suffix_id:label}' => 'Individual Suffix',
      '{contact.formal_title}' => 'Formal Title',
      '{contact.communication_style_id:label}' => 'Communication Style',
      '{contact.email_greeting_display}' => 'Email Greeting',
      '{contact.postal_greeting_display}' => 'Postal Greeting',
      '{contact.addressee_display}' => 'Addressee',
      '{contact.job_title}' => 'Job Title',
      '{contact.gender_id:label}' => 'Gender',
      '{contact.birth_date}' => 'Birth Date',
      '{contact.employer_id}' => 'Current Employer ID',
      '{contact.is_deleted:label}' => 'Contact is in Trash',
      '{contact.created_date}' => 'Created Date',
      '{contact.modified_date}' => 'Modified Date',
      '{contact.address_id}' => 'Address ID',
      '{contact.location_type_id:label}' => 'Address Location Type',
      '{contact.street_address}' => 'Street Address',
      '{contact.street_number}' => 'Street Number',
      '{contact.street_number_suffix}' => 'Street Number Suffix',
      '{contact.street_name}' => 'Street Name',
      '{contact.street_unit}' => 'Street Unit',
      '{contact.supplemental_address_1}' => 'Supplemental Address 1',
      '{contact.supplemental_address_2}' => 'Supplemental Address 2',
      '{contact.supplemental_address_3}' => 'Supplemental Address 3',
      '{contact.city}' => 'City',
      '{contact.county}' => 'County',
      '{contact.postal_code_suffix}' => 'Postal Code Suffix',
      '{contact.postal_code}' => 'Postal Code',
      '{contact.country}' => 'Country',
      '{contact.geo_code_1}' => 'Latitude',
      '{contact.geo_code_2}' => 'Longitude',
      '{contact.address_name}' => 'Address Name',
      '{contact.master_id}' => 'Master Address ID',
      '{contact.phone}' => 'Phone',
      '{contact.phone_ext}' => 'Phone Extension',
      '{contact.phone_type}' => 'Phone Type',
      '{contact.email}' => 'Email',
      '{contact.on_hold:label}' => 'On Hold',
      '{contact.signature_text}' => 'Signature Text',
      '{contact.signature_html}' => 'Signature Html',
      '{contact.url}' => 'Website',
      '{contact.openid}' => 'OpenID',
      '{contact.im}' => 'IM Screen Name',
      '{contact.provider_id:label}' => 'IM Provider',
      '{contact.state_province}' => 'State/Province',
    ],
  ];

  return $expectedResult;
}

/*
 * This example has been generated from the API test suite.
 * The test that created it is called "testMailGetTokens"
 * and can be found at:
 * https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/MailingTest.php
 *
 * You can see the outcome of the API tests at
 * https://test.civicrm.org/job/CiviCRM-Core-Matrix/
 *
 * To Learn about the API read
 * https://docs.civicrm.org/dev/en/latest/api/
 *
 * Browse the API on your own site with the API Explorer. It is in the main
 * CiviCRM menu, under: Support > Development > API Explorer.
 *
 * Read more about testing here
 * https://docs.civicrm.org/dev/en/latest/testing/
 *
 * API Standards documentation:
 * https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
 */
