<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
class CRM_Contact_SelectorTest extends CiviUnitTestCase {

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
    // Ensure that search builder return individual contact as per criteria
    if (!empty($dataSet['context'] == 'builder')) {
      $contactID = $this->individualCreate(['first_name' => 'James', 'last_name' => 'Bond']);
      if ('Search builder behaviour for Activity' == $dataSet['description']) {
        $this->callAPISuccess('Activity', 'create', [
          'activity_type_id' => 'Meeting',
          'subject' => "Test",
          'source_contact_id' => $contactID,
        ]);
        $rows = CRM_Core_DAO::executeQuery(implode(' ', $sql))->fetchAll();
        $this->assertEquals(1, count($rows));
        $this->assertEquals($contactID, $rows[0]['source_contact_id']);
      }
      else {
        $this->callAPISuccess('Address', 'create', [
          'contact_id' => $contactID,
          'location_type_id' => "Home",
          'is_primary' => 1,
          'country_id' => "IN",
        ]);
        $rows = $selector->getRows(CRM_Core_Action::VIEW, 0, 50, '');
        $this->assertEquals(1, count($rows));
        $sortChar = $selector->alphabetQuery()->fetchAll();
        // sort name is stored in '<last_name>, <first_name>' format, as per which the first character would be B of Bond
        $this->assertEquals('B', $sortChar[0]['sort_name']);
        $this->assertEquals($contactID, key($rows));
      }
    }
  }

  /**
   * Test the civicrm_prevnext_cache entry if it correctly stores the search query result
   */
  public function testPrevNextCache() {
    $contactID = $this->individualCreate(['email' => 'mickey@mouseville.com']);
    $dataSet = array(
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
    );
    $params = CRM_Contact_BAO_Query::convertFormValues($dataSet['form_values'], 0, FALSE, NULL, array());

    // create CRM_Contact_Selector instance and set desired query params
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
    // set cache key
    $key = substr(sha1(rand()), 0, 7);
    $selector->setKey($key);

    // fetch row and check the result
    $rows = $selector->getRows(CRM_Core_Action::VIEW, 0, 1, NULL);
    $this->assertEquals(1, count($rows));
    $this->assertEquals($contactID, key($rows));

    // build cache key and use to it to fetch prev-next cache record
    $cacheKey = 'civicrm search ' . $key;
    $contacts = CRM_Utils_SQL_Select::from('civicrm_prevnext_cache')
      ->select(['entity_id1', 'cacheKey'])
      ->where("cacheKey = @key")
      ->param('key', $cacheKey)
      ->execute()
      ->fetchAll();
    $this->assertEquals(1, count($contacts));
    // check the prevNext record matches
    $expectedEntry = [
      'entity_id1' => $contactID,
      'cacheKey' => $cacheKey,
    ];
    $this->checkArrayEquals($contacts[0], $expectedEntry);
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
            2 => "WHERE  ( civicrm_email.email LIKE '%mickey@mouseville.com%'  AND ( ( ( contact_a.sort_name LIKE '%Mouse%' ) OR ( civicrm_email.email LIKE '%Mouse%' ) ) ) ) AND (contact_a.is_deleted = 0)",
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
            2 => "WHERE  ( civicrm_email.email LIKE 'mickey@mouseville.com%'  AND ( ( ( contact_a.sort_name LIKE 'Mouse%' ) OR ( civicrm_email.email LIKE 'Mouse%' ) ) ) ) AND (contact_a.is_deleted = 0)",
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
            2 => "WHERE  ( civicrm_email.email = 'mickey@mouseville.com'  AND ( ( ( contact_a.sort_name LIKE 'Mouse%' ) OR ( civicrm_email.email LIKE 'Mouse%' ) ) ) ) AND (contact_a.is_deleted = 0)",
          ),
        ),
      ),
      array(
        array(
          'description' => 'Normal search builder behaviour',
          'class' => 'CRM_Contact_Selector',
          'settings' => array(),
          'form_values' => array('contact_type' => 'Individual', 'country' => array('IS NOT NULL' => 1)),
          'params' => array(),
          'return_properties' => array(
            'contact_type' => 1,
            'contact_sub_type' => 1,
            'sort_name' => 1,
          ),
          'context' => 'builder',
          'action' => CRM_Core_Action::NONE,
          'includeContactIds' => NULL,
          'searchDescendentGroups' => FALSE,
          'expected_query' => array(
            0 => 'SELECT contact_a.id as contact_id, contact_a.contact_type as `contact_type`, contact_a.contact_sub_type as `contact_sub_type`, contact_a.sort_name as `sort_name`, civicrm_address.id as address_id, civicrm_address.country_id as country_id',
            1 => ' FROM civicrm_contact contact_a LEFT JOIN civicrm_address ON ( contact_a.id = civicrm_address.contact_id AND civicrm_address.is_primary = 1 )',
            2 => 'WHERE ( contact_a.contact_type IN ("Individual") AND civicrm_address.country_id IS NOT NULL ) AND (contact_a.is_deleted = 0)',
          ),
        ),
      ),
      array(
        array(
          'description' => 'Search builder behaviour for Activity',
          'class' => 'CRM_Contact_Selector',
          'settings' => array(),
          'form_values' => array('source_contact_id' => array('IS NOT NULL' => 1)),
          'params' => array(),
          'return_properties' => array(
            'source_contact_id' => 1,
          ),
          'context' => 'builder',
          'action' => CRM_Core_Action::NONE,
          'includeContactIds' => NULL,
          'searchDescendentGroups' => FALSE,
          'expected_query' => array(
            0 => 'SELECT contact_a.id as contact_id, source_contact.id as source_contact_id',
            2 => 'WHERE ( source_contact.id IS NOT NULL ) AND (contact_a.is_deleted = 0)',
          ),
        ),
      ),
    );
  }

  /**
   * Test the contact ID query does not fail on country search.
   */
  public function testContactIDQuery() {
    $params = [
      [
        0 => 'country-1',
        1 => '=',
        2 => '1228',
        3 => 1,
        4 => 0,
      ],
    ];

    $searchOBJ = new CRM_Contact_Selector(NULL);
    $searchOBJ->contactIDQuery($params, '1_u');
  }

  /**
   * Test the Search Builder using Non ASCII location type for email filter
   */
  public function testSelectorQueryOnNonASCIIlocationType() {
    $contactID = $this->individualCreate();
    $locationType = $this->locationTypeCreate([
      'name' => 'Non ASCII Location Type',
      'display_name' => 'Дом Location type',
      'vcard_name' => 'Non ASCII Location Type',
      'is_active' => 1,
    ]);
    $this->callAPISuccess('Email', 'create', [
      'contact_id' => $contactID,
      'location_type_id' => $locationType->id,
      'email' => 'test@test.com',
    ]);

    $selector = new CRM_Contact_Selector(
      'CRM_Contact_Selector',
      ['email' => ['IS NOT NULL' => 1]],
      [
        [
          0 => 'email-' . $locationType->id,
          1 => 'IS NOT NULL',
          2 => NULL,
          3  => 1,
          4 => 0,
        ],
      ],
      [
        'contact_type' => 1,
        'contact_sub_type' => 1,
        'sort_name' => 1,
        'location' => [
          'Non ASCII Location Type' => [
            'location_type' => $locationType->id,
            'email' => 1,
          ],
        ],
      ],
      CRM_Core_Action::NONE,
      NULL,
      FALSE,
      'builder'
    );

    $sql = $selector->getQueryObject()->query();

    $expectedQuery = [
      0 => "SELECT contact_a.id as contact_id, contact_a.contact_type as `contact_type`, contact_a.contact_sub_type as `contact_sub_type`, contact_a.sort_name as `sort_name`, `Non_ASCII_Location_Type-location_type`.id as `Non_ASCII_Location_Type-location_type_id`, `Non_ASCII_Location_Type-location_type`.name as `Non_ASCII_Location_Type-location_type`, `Non_ASCII_Location_Type-email`.id as `Non_ASCII_Location_Type-email_id`, `Non_ASCII_Location_Type-email`.email as `Non_ASCII_Location_Type-email`",
      // @TODO these FROM clause doesn't matches due to extra spaces or special character
      2 => "WHERE  (  ( `Non_ASCII_Location_Type-email`.email IS NOT NULL )  )  AND (contact_a.is_deleted = 0)",
    ];
    foreach ($expectedQuery as $index => $queryString) {
      $this->assertEquals($this->strWrangle($queryString), $this->strWrangle($sql[$index]));
    }

    $rows = $selector->getRows(CRM_Core_Action::VIEW, 0, 1, NULL);
    $this->assertEquals(1, count($rows));
    $this->assertEquals($contactID, key($rows));
    $this->assertEquals('test@test.com', $rows[$contactID]['Non_ASCII_Location_Type-email']);
  }

  /**
   * Test the value use in where clause if it's case sensitive or not against each MySQL operators
   */
  public function testWhereClauseByOperator() {
    $contactID = $this->individualCreate(['first_name' => 'Adam']);

    $filters = [
      'IS NOT NULL' => 1,
      '=' => 'Adam',
      'LIKE' => '%Ad%',
      'RLIKE' => '^A[a-z]{3}$',
      'IN' => ['IN' => ['Adam']],
    ];
    $filtersByWhereClause = [
      // doesn't matter
      'IS NOT NULL' => '( contact_a.first_name IS NOT NULL )',
      // case sensitive check
      '=' => "( contact_a.first_name = 'Adam' )",
      // case insensitive check
      'LIKE' => "( contact_a.first_name LIKE '%Ad%' )",
      // case sensitive check
      'RLIKE' => "(  contact_a.first_name RLIKE BINARY '^A[a-z]{3}$'  )",
      // case sensitive check
      'IN' => '( contact_a.first_name IN ("Adam") )',
    ];
    foreach ($filters as $op => $filter) {
      $selector = new CRM_Contact_Selector(
        'CRM_Contact_Selector',
        ['first_name' => [$op => $filter]],
        [
          [
            0 => 'first_name',
            1 => $op,
            2 => $filter,
            3 => 1,
            4 => 0,
          ],
        ],
        [],
        CRM_Core_Action::NONE,
        NULL,
        FALSE,
        'builder'
      );

      $sql = $selector->getQueryObject()->query();
      $this->assertEquals(TRUE, strpos($sql[2], $filtersByWhereClause[$op]));

      $rows = $selector->getRows(CRM_Core_Action::VIEW, 0, 1, NULL);
      $this->assertEquals(1, count($rows));
      $this->assertEquals($contactID, key($rows));
    }
  }

  /**
   * Test if custom table is added in from clause when
   * search results are ordered by a custom field.
   */
  public function testSelectorQueryOrderByCustomField() {
    //Search for any params.
    $params = [
      [
        0 => 'country-1',
        1 => '=',
        2 => '1228',
        3 => 1,
        4 => 0,
      ],
    ];

    //Create a test custom group and field.
    $customGroup = $this->callAPISuccess('CustomGroup', 'create', array(
      'title' => "test custom group",
      'extends' => "Individual",
    ));
    $cgTableName = $customGroup['values'][$customGroup['id']]['table_name'];
    $customField = $this->callAPISuccess('CustomField', 'create', array(
      'custom_group_id' => $customGroup['id'],
      'label' => "test field",
      'html_type' => "Text",
    ));
    $customFieldId = $customField['id'];

    //Sort by the custom field created above.
    $sortParams = array(
      1 => array(
        'name' => 'test field',
        'sort' => "custom_{$customFieldId}",
      ),
    );
    $sort = new CRM_Utils_Sort($sortParams, '1_d');

    //Form a query to order by a custom field.
    $query = new CRM_Contact_BAO_Query($params,
      CRM_Contact_BAO_Query::NO_RETURN_PROPERTIES,
      NULL, FALSE, FALSE, 1,
      FALSE, TRUE, TRUE, NULL,
      'AND'
    );
    $query->searchQuery(0, 0, $sort,
      FALSE, FALSE, FALSE,
      FALSE, FALSE
    );
    //Check if custom table is included in $query->_tables.
    $this->assertTrue(in_array($cgTableName, array_keys($query->_tables)));
    //Assert if from clause joins the custom table.
    $this->assertTrue(strpos($query->_fromClause, $cgTableName) !== FALSE);
    $this->callAPISuccess('CustomField', 'delete', ['id' => $customField['id']]);
    $this->callAPISuccess('CustomGroup', 'delete', ['id' => $customGroup['id']]);
  }

  /**
   * Check where clause of a date custom field when 'IS NOT EMPTY' operator is used
   */
  public function testCustomDateField() {
    $contactID = $this->individualCreate();
    //Create a test custom group and field.
    $customGroup = $this->callAPISuccess('CustomGroup', 'create', array(
      'title' => "test custom group",
      'extends' => "Individual",
    ));
    $customTableName = $this->callAPISuccess('CustomGroup', 'getValue', ['id' => $customGroup['id'], 'return' => 'table_name']);
    $customGroupTableName = $customGroup['values'][$customGroup['id']]['table_name'];

    $createdField = $this->callAPISuccess('customField', 'create', [
      'data_type' => 'Date',
      'html_type' => 'Select Date',
      'date_format' => 'd M yy',
      'time_format' => 1,
      'label' => 'test field',
      'custom_group_id' => $customGroup['id'],
    ]);
    $customFieldColumnName = $createdField['values'][$createdField['id']]['column_name'];

    $this->callAPISuccess('Contact', 'create', [
      'id' => $contactID,
      'custom_' . $createdField['id'] => date('YmdHis'),
    ]);

    $selector = new CRM_Contact_Selector(
      'CRM_Contact_Selector',
      ['custom_' . $createdField['id'] => ['IS NOT EMPTY' => 1]],
      [
        [
          0 => 'custom_' . $createdField['id'],
          1 => 'IS NOT NULL',
          2 => 1,
          3 => 1,
          4 => 0,
        ],
      ],
      [],
      CRM_Core_Action::NONE,
      NULL,
      FALSE,
      'builder'
    );

    $whereClause = $selector->getQueryObject()->query()[2];
    $expectedClause = sprintf("( %s.%s IS NOT NULL )", $customGroupTableName, $customFieldColumnName);
    // test the presence of expected date clause
    $this->assertEquals(TRUE, strpos($whereClause, $expectedClause));

    $rows = $selector->getRows(CRM_Core_Action::VIEW, 0, 1, NULL);
    $this->assertEquals(1, count($rows));
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
    . 'civicrm_address.supplemental_address_2 as `supplemental_address_2`, civicrm_address.supplemental_address_3 as `supplemental_address_3`, civicrm_address.city as `city`, civicrm_address.postal_code_suffix as `postal_code_suffix`, '
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
    . ' LEFT JOIN civicrm_country ON ( civicrm_address.country_id = civicrm_country.id ) '
    . ' LEFT JOIN civicrm_email ON (contact_a.id = civicrm_email.contact_id AND civicrm_email.is_primary = 1)'
    . ' LEFT JOIN civicrm_phone ON (contact_a.id = civicrm_phone.contact_id AND civicrm_phone.is_primary = 1)'
    . ' LEFT JOIN civicrm_im ON (contact_a.id = civicrm_im.contact_id AND civicrm_im.is_primary = 1) '
    . 'LEFT JOIN civicrm_worldregion ON civicrm_country.region_id = civicrm_worldregion.id ';
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
    if (CRM_Utils_Array::value(0, $expectedQuery) == 'default') {
      $expectedQuery[0] = $this->getDefaultSelectString();
    }
    if (CRM_Utils_Array::value(1, $expectedQuery) == 'default') {
      $expectedQuery[1] = $this->getDefaultFromString();
    }
  }

}
