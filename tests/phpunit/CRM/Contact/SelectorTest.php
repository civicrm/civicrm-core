<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *  Include parent class definition
 */

/**
 *  Test contact custom search functions
 *
 * @package CiviCRM
 * @group headless
 */
class CRM_Contact_Form_SelectorTest extends CiviUnitTestCase {

  public function tearDown() {

  }
  /**
   * Test the query from the selector class is consistent with the dataset expectation.
   *
   * @param array $dataSet
   *   The data set to be tested. Note that when adding new datasets often only form_values and expected where
   *   clause will need changing.
   *
   * @dataProvider querySets
   */
  public function testSelectorQuery($dataSet) {
    $params = CRM_Contact_BAO_Query::convertFormValues($dataSet['form_values'], 0, FALSE, NULL, array());
    foreach ($dataSet['settings'] as $setting) {
      $this->callAPISuccess('Setting', 'create', array($setting['name'] => $setting['value']));
    }
    $selector = new CRM_Contact_Selector(
      $dataSet['class'],
      $dataSet['form_values'],
      $params,
      $dataSet['return_properties'],
      $dataSet['action'],
      $dataSet['includeContactIds'],
      $dataSet['searchDescendentGroups'],
      $dataSet['context']
    );
    $queryObject = $selector->getQueryObject();
    $sql = $queryObject->query();
    $this->wrangleDefaultClauses($dataSet['expected_query']);
    foreach ($dataSet['expected_query'] as $index => $queryString) {
      $this->assertEquals($this->strWrangle($queryString), $this->strWrangle($sql[$index]));
    }
  }

  /**
   * Data sets for testing.
   */
  public function querySets() {
    return array(
      array(
        array(
          'description' => 'Normal default behaviour',
          'class' => 'CRM_Contact_Selector',
          'settings' => array(),
          'form_values' => array('email' => 'mickey@mouseville.com'),
          'params' => array(),
          'return_properties' => NULL,
          'context' => 'advanced',
          'action' => CRM_Core_Action::ADVANCED,
          'includeContactIds' => NULL,
          'searchDescendentGroups' => FALSE,
          'expected_query' => array(
            0 => 'default',
            1 => 'default',
            2 => "WHERE  ( civicrm_email.email LIKE '%mickey@mouseville.com%' )  AND (contact_a.is_deleted = 0)",
          ),
        ),
      ),
      array(
        array(
          'description' => 'Normal default + user added wildcard',
          'class' => 'CRM_Contact_Selector',
          'settings' => array(),
          'form_values' => array('email' => '%mickey@mouseville.com', 'sort_name' => 'Mouse'),
          'params' => array(),
          'return_properties' => NULL,
          'context' => 'advanced',
          'action' => CRM_Core_Action::ADVANCED,
          'includeContactIds' => NULL,
          'searchDescendentGroups' => FALSE,
          'expected_query' => array(
            0 => 'default',
            1 => 'default',
            2 => "WHERE  ( civicrm_email.email LIKE '%mickey@mouseville.com%'  AND ( ( ( contact_a.sort_name LIKE '%mouse%' ) OR ( civicrm_email.email LIKE '%mouse%' ) ) ) ) AND (contact_a.is_deleted = 0)",
          ),
        ),
      ),
      array(
        array(
          'description' => 'Site set to not pre-pend wildcard',
          'class' => 'CRM_Contact_Selector',
          'settings' => array(array('name' => 'includeWildCardInName', 'value' => FALSE)),
          'form_values' => array('email' => 'mickey@mouseville.com', 'sort_name' => 'Mouse'),
          'params' => array(),
          'return_properties' => NULL,
          'context' => 'advanced',
          'action' => CRM_Core_Action::ADVANCED,
          'includeContactIds' => NULL,
          'searchDescendentGroups' => FALSE,
          'expected_query' => array(
            0 => 'default',
            1 => 'default',
            2 => "WHERE  ( civicrm_email.email LIKE 'mickey@mouseville.com%'  AND ( ( ( contact_a.sort_name LIKE 'mouse%' ) OR ( civicrm_email.email LIKE 'mouse%' ) ) ) ) AND (contact_a.is_deleted = 0)",
          ),
        ),
      ),
      array(
        array(
          'description' => 'Use of quotes for exact string',
          'use_case_comments' => 'This is something that was in the code but seemingly not working. No UI info on it though!',
          'class' => 'CRM_Contact_Selector',
          'settings' => array(array('name' => 'includeWildCardInName', 'value' => FALSE)),
          'form_values' => array('email' => '"mickey@mouseville.com"', 'sort_name' => 'Mouse'),
          'params' => array(),
          'return_properties' => NULL,
          'context' => 'advanced',
          'action' => CRM_Core_Action::ADVANCED,
          'includeContactIds' => NULL,
          'searchDescendentGroups' => FALSE,
          'expected_query' => array(
            0 => 'default',
            1 => 'default',
            2 => "WHERE  ( civicrm_email.email = 'mickey@mouseville.com'  AND ( ( ( contact_a.sort_name LIKE 'mouse%' ) OR ( civicrm_email.email LIKE 'mouse%' ) ) ) ) AND (contact_a.is_deleted = 0)",
          ),
        ),
      ),
    );
  }

  /**
   * Get the default select string since this is generally consistent.
   */
  public function getDefaultSelectString() {
    return 'SELECT contact_a.id as contact_id, contact_a.contact_type  as `contact_type`, contact_a.contact_sub_type  as `contact_sub_type`, contact_a.sort_name  as `sort_name`,'
    . ' contact_a.display_name  as `display_name`, contact_a.do_not_email  as `do_not_email`, contact_a.do_not_phone as `do_not_phone`, contact_a.do_not_mail  as `do_not_mail`,'
    . ' contact_a.do_not_sms  as `do_not_sms`, contact_a.do_not_trade as `do_not_trade`, contact_a.is_opt_out  as `is_opt_out`, contact_a.legal_identifier  as `legal_identifier`,'
    . ' contact_a.external_identifier  as `external_identifier`, contact_a.nick_name  as `nick_name`, contact_a.legal_name  as `legal_name`, contact_a.image_URL  as `image_URL`,'
    . ' contact_a.preferred_communication_method  as `preferred_communication_method`, contact_a.preferred_language  as `preferred_language`,'
    . ' contact_a.preferred_mail_format  as `preferred_mail_format`, contact_a.first_name  as `first_name`, contact_a.middle_name  as `middle_name`, contact_a.last_name  as `last_name`,'
    . ' contact_a.prefix_id  as `prefix_id`, contact_a.suffix_id  as `suffix_id`, contact_a.formal_title  as `formal_title`, contact_a.communication_style_id  as `communication_style_id`,'
    . ' contact_a.job_title  as `job_title`, contact_a.gender_id  as `gender_id`, contact_a.birth_date  as `birth_date`, contact_a.is_deceased  as `is_deceased`,'
    . ' contact_a.deceased_date  as `deceased_date`, contact_a.household_name  as `household_name`,'
    . ' IF ( contact_a.contact_type = \'Individual\', NULL, contact_a.organization_name ) as organization_name, contact_a.sic_code  as `sic_code`, contact_a.is_deleted  as `contact_is_deleted`,'
    . ' IF ( contact_a.contact_type = \'Individual\', contact_a.organization_name, NULL ) as current_employer, civicrm_address.id as address_id,'
    . ' civicrm_address.street_address as `street_address`, civicrm_address.supplemental_address_1 as `supplemental_address_1`, '
    . 'civicrm_address.supplemental_address_2 as `supplemental_address_2`, civicrm_address.city as `city`, civicrm_address.postal_code_suffix as `postal_code_suffix`, '
    . 'civicrm_address.postal_code as `postal_code`, civicrm_address.geo_code_1 as `geo_code_1`, civicrm_address.geo_code_2 as `geo_code_2`, '
    . 'civicrm_address.state_province_id as state_province_id, civicrm_address.country_id as country_id, civicrm_phone.id as phone_id, civicrm_phone.phone_type_id as phone_type_id, '
    . 'civicrm_phone.phone as `phone`, civicrm_email.id as email_id, civicrm_email.email as `email`, civicrm_email.on_hold as `on_hold`, civicrm_im.id as im_id, '
    . 'civicrm_im.provider_id as provider_id, civicrm_im.name as `im`, civicrm_worldregion.id as worldregion_id, civicrm_worldregion.name as `world_region`';
  }

  /**
   * Get the default from string since this is generally consistent.
   */
  public function getDefaultFromString() {
    return ' FROM civicrm_contact contact_a LEFT JOIN civicrm_address ON ( contact_a.id = civicrm_address.contact_id AND civicrm_address.is_primary = 1 )'
    . ' LEFT JOIN civicrm_email ON (contact_a.id = civicrm_email.contact_id AND civicrm_email.is_primary = 1)'
    . ' LEFT JOIN civicrm_phone ON (contact_a.id = civicrm_phone.contact_id AND civicrm_phone.is_primary = 1)'
    . ' LEFT JOIN civicrm_im ON (contact_a.id = civicrm_im.contact_id AND civicrm_im.is_primary = 1) '
    . 'LEFT JOIN civicrm_country ON civicrm_address.country_id = civicrm_country.id LEFT JOIN civicrm_worldregion ON civicrm_country.region_id = civicrm_worldregion.id ';
  }

  /**
   * Strangle strings into a more matchable format.
   *
   * @param string $string
   * @return string
   */
  public function strWrangle($string) {
    return str_replace('  ', ' ', $string);
  }

  /**
   * Swap out default parts of the query for the actual string.
   *
   * Note that it seems to make more sense to resolve this earlier & pass it in from a clean code point of
   * view, but the output on fail includes long sql statements that are of low relevance then.
   *
   * @param array $expectedQuery
   */
  public function wrangleDefaultClauses(&$expectedQuery) {
    if ($expectedQuery[0] == 'default') {
      $expectedQuery[0] = $this->getDefaultSelectString();
    }
    if ($expectedQuery[1] == 'default') {
      $expectedQuery[1] = $this->getDefaultFromString();
    }
  }

}
