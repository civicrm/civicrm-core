<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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
 * Class CRM_Core_I18n_SchemaTest
 * @group headless
 */
class CRM_Core_I18n_SchemaTest extends CiviUnitTestCase {

  /**
   * Test tables to translate
   * @return array
   */
  public static function translateTables() {
    $tables = array();
    $tables[] = array('civicrm_option_group', 'civicrm_option_group_en_US');
    $tables[] = array('civicrm_events_in_carts', 'civicrm_events_in_carts');
    $tables[] = array('civicrm_event', 'civicrm_event_en_US');
    return $tables;
  }

  public function setUp() {
    parent::setUp();
  }

  public function tearDown() {
    CRM_Core_I18n_Schema::makeSinglelingual('en_US');
    parent::tearDown();
  }

  /**
   * @param string $table
   * @param string $expectedRewrite
   *
   * @dataProvider translateTables
   */
  public function testI18nSchemaRewrite($table, $expectedRewrite) {
    CRM_Core_I18n_Schema::makeMultilingual('en_US');
    global $dbLocale;
    $dbLocale = '_en_US';
    $query = "Select * FROM {$table}";
    $new_query = CRM_Core_I18n_Schema::rewriteQuery($query);
    $this->assertEquals("Select * FROM {$expectedRewrite}", $new_query);
    $query2 = "Select * FROM {$table} LIMIT 1";
    $new_query2 = CRM_Core_I18n_Schema::rewriteQuery($query2);
    $this->assertEquals("Select * FROM {$expectedRewrite} LIMIT 1", $new_query2);
    $query3 = "SELECT * FROM {$table} JOIN civicrm_contact LIMIT 1";
    $new_query3 = CRM_Core_I18n_Schema::rewriteQuery($query3);
    $this->assertEquals("SELECT * FROM {$expectedRewrite} JOIN civicrm_contact LIMIT 1", $new_query3);
  }

}
