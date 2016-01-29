<?php
/*
  +--------------------------------------------------------------------+
  | CiviCRM version 4.7                                                |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * Test class for CRM_Contact_BAO_Group BAO
 *
 * @package   CiviCRM
 */
class CRM_Contact_BAO_GroupTest extends CiviUnitTestCase {

  /**
   * Sets up the fixture, for example, opens a network connection.
   *
   * This method is called before a test is executed.
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   *
   * This method is called after a test is executed.
   */
  protected function tearDown() {
    $this->quickCleanup(array('civicrm_mapping_field', 'civicrm_mapping', 'civicrm_group', 'civicrm_saved_search'));
  }

  /**
   * Test case for add( ).
   */
  public function testAddSimple() {

    $checkParams = $params = array(
      'title' => 'Group Uno',
      'description' => 'Group One',
      'visibility' => 'User and User Admin Only',
      'is_active' => 1,
    );

    $group = CRM_Contact_BAO_Group::create($params);

    $this->assertDBCompareValues(
      'CRM_Contact_DAO_Group',
      array('id' => $group->id),
      $checkParams
    );
  }

  /**
   * Test adding a smart group.
   */
  public function testAddSmart() {

    $checkParams = $params = array(
      'title' => 'Group Dos',
      'description' => 'Group Two',
      'visibility' => 'User and User Admin Only',
      'is_active' => 1,
      'formValues' => array('sort_name' => 'Adams'),
    );

    $group = CRM_Contact_BAO_Group::createSmartGroup($params);

    unset($checkParams['formValues']);
    $this->assertDBCompareValues(
      'CRM_Contact_DAO_Group',
      array('id' => $group->id),
      $checkParams
    );
  }

  /**
   * Load all sql data sets & return an array of saved searches.
   *
   * @return array
   */
  public function dataProviderSavedSearch() {

    $this->loadSavedSearches();
    $results = CRM_Core_DAO::singleValueQuery('SELECT GROUP_CONCAT(id) FROM civicrm_group WHERE saved_search_id IS NOT NULL');
    return array(explode(',', $results));
  }

  /**
   * Load saved search sql files into the DB.
   */
  public function loadSavedSearches() {
    $dsn = CRM_Core_Config::singleton()->dsn;
    foreach (glob(dirname(__FILE__) . "/SavedSearchDataSets/*.sql") as $file) {
      CRM_Utils_File::sourceSQLFile($dsn, $file);
    }
  }

  /**
   * Check we can load smart groups based on config from 'real DBs' without fatal errors.
   *
   * Note that we are only testing lack of errors at this stage
   * @todo - for some reason the data was getting truncated from the group table using dataprovider - would be preferable to get that working
   * //@notdataProvider dataProviderSavedSearch
   * //@notparam integer $groupID
   *
   * To add to this dataset do
   *
   *  SET @groupID = x;
   *  SELECT mapping_id FROM civicrm_group g LEFT JOIN civicrm_saved_search s ON saved_search_id = s.id WHERE g.id = @groupID INTO @mappingID;
   * SELECT * FROM civicrm_mapping WHERE id = @mappingID;
   * SELECT * FROM civicrm_mapping_field WHERE mapping_id = @mappingID;
   * SELECT * FROM civicrm_saved_search WHERE mapping_id = @mappingID;
   * SELECT g.* FROM civicrm_saved_search s LEFT JOIN civicrm_group g ON g.saved_search_id =  s.id WHERE  mapping_id = @mappingID;
   *
   *  Copy the output to a single sql file and place in the SavedSearchDataSets folder - use the group number as the prefix.
   *  Try to keep as much of the real world irregular glory as you can! Don't change the table ids to be number 1 as this can hide errors
   */
  public function testGroupData() {
    $groups = $this->dataProviderSavedSearch();
    foreach ($groups[0] as $groupID) {
      $group = new CRM_Contact_BAO_Group();
      $group->id = $groupID;
      $group->find(TRUE);

      CRM_Contact_BAO_GroupContactCache::load($group, TRUE);
    }
  }

}
