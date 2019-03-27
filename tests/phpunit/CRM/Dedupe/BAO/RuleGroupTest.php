<?php

/**
 * Class CRM_Dedupe_BAO_RuleGroupTest
 * @group headless
 */
class CRM_Dedupe_BAO_RuleGroupTest extends CiviUnitTestCase {

  /**
   * Test that sort_name is included in supported fields.
   *
   * This feels like kind of a brittle test but since I debated actually making it
   * importable in the schema & bottled out at least some degree of test support
   * to ensure the field remains 'hacked in' seems important.
   *
   * This will at least surface any changes that affect this function.
   *
   * In general we do have a bit of a problem with having overloaded the meaning of
   * importable & exportable fields.
   */
  public function testSupportedFields() {
    $fields = CRM_Dedupe_BAO_RuleGroup::supportedFields('Organization');

    $this->assertEquals([
      'civicrm_address' =>
        [
          'name' => 'Address Name',
          'city' => 'City',
          'country_id' => 'Country',
          'county_id' => 'County',
          'geo_code_1' => 'Latitude',
          'geo_code_2' => 'Longitude',
          'master_id' => 'Master Address Belongs To',
          'postal_code' => 'Postal Code',
          'postal_code_suffix' => 'Postal Code Suffix',
          'state_province_id' => 'State',
          'street_address' => 'Street Address',
          'supplemental_address_1' => 'Supplemental Address 1',
          'supplemental_address_2' => 'Supplemental Address 2',
          'supplemental_address_3' => 'Supplemental Address 3',
        ],
      'civicrm_contact' =>
        [
          'addressee_id' => 'Addressee',
          'addressee_custom' => 'Addressee Custom',
          'id' => 'Contact ID',
          'source' => 'Contact Source',
          'contact_sub_type' => 'Contact Subtype',
          'do_not_email' => 'Do Not Email',
          'do_not_mail' => 'Do Not Mail',
          'do_not_phone' => 'Do Not Phone',
          'do_not_sms' => 'Do Not Sms',
          'do_not_trade' => 'Do Not Trade',
          'email_greeting_id' => 'Email Greeting',
          'email_greeting_custom' => 'Email Greeting Custom',
          'external_identifier' => 'External Identifier',
          'image_URL' => 'Image Url',
          'legal_identifier' => 'Legal Identifier',
          'legal_name' => 'Legal Name',
          'nick_name' => 'Nickname',
          'is_opt_out' => 'No Bulk Emails (User Opt Out)',
          'organization_name' => 'Organization Name',
          'postal_greeting_id' => 'Postal Greeting',
          'postal_greeting_custom' => 'Postal Greeting Custom',
          'preferred_communication_method' => 'Preferred Communication Method',
          'preferred_language' => 'Preferred Language',
          'preferred_mail_format' => 'Preferred Mail Format',
          'sic_code' => 'Sic Code',
          'user_unique_id' => 'Unique ID (OpenID)',
          'sort_name' => 'Sort Name',
        ],
      'civicrm_email' =>
        [
          'email' => 'Email',
          'signature_html' => 'Signature Html',
          'signature_text' => 'Signature Text',
        ],
      'civicrm_im' =>
        [
          'name' => 'IM Screen Name',
        ],
      'civicrm_note' =>
        [
          'note' => 'Note',
        ],
      'civicrm_openid' =>
        [
          'openid' => 'OpenID',
        ],
      'civicrm_phone' =>
        [
          'phone_numeric' => 'Phone',
          'phone_ext' => 'Phone Extension',
        ],
    ], $fields);
  }

}
