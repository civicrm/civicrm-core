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
    $skip_tests = FALSE;
    if (in_array($table, array('civicrm_option_group', 'civicrm_event'))) {
      $skip_tests = TRUE;
    }
    global $dbLocale;
    $dbLocale = '_en_US';
    // Test problematic queriy as per CRM-20427
    $query = "Select * FROM {$table}";
    $new_query = CRM_Core_I18n_Schema::rewriteQuery($query);
    $this->assertEquals("Select * FROM {$expectedRewrite}", $new_query);
    // Test query where table is not at the end
    $query2 = "Select * FROM {$table} LIMIT 1";
    $new_query2 = CRM_Core_I18n_Schema::rewriteQuery($query2);
    $this->assertEquals("Select * FROM {$expectedRewrite} LIMIT 1", $new_query2);
    // Test query where there is a 2nd table that shouldn't be re-wrten
    $query3 = "SELECT * FROM {$table} JOIN civicrm_contact LIMIT 1";
    $new_query3 = CRM_Core_I18n_Schema::rewriteQuery($query3);
    $this->assertEquals("SELECT * FROM {$expectedRewrite} JOIN civicrm_contact LIMIT 1", $new_query3);
    // Test table when name is escaped
    $query4 = "SELECT * FROM `{$table}` WHERE id = 123";
    $new_query4 = CRM_Core_I18n_Schema::rewriteQuery($query4);
    $this->assertEquals("SELECT * FROM `{$expectedRewrite}` WHERE id = 123", $new_query4);
    // Test where translatable table is quoted
    // The `$table` appears in a string -- it should not be rewritten.
    $query5 = 'SELECT id FROM civicrm_activity WHERE subject = "civicrm_option_group"';
    $new_query5 = CRM_Core_I18n_Schema::rewriteQuery($query5);
    $this->assertEquals($query5, $new_query5);
    // Test where table is not the last thing to be in a quoted string
    // Test Currently skipped for civicrm_option_group and civicrm_event due to issues with the regex.
    // Agreed as not a blocker for CRM-20427 as an issue previously.
    if (!$skip_tests) {
      $query6 = "SELECT " . '"' . "Fixed the the {$table} ticket" . '"';
      $new_query6 = CRM_Core_I18n_Schema::rewriteQuery($query6);
      $this->assertEquals($query6, $new_query6);
    }
    // Test where table is part of a sub query
    $query7 = "SELECT * FROM civicrm_foo WHERE foo_id = (SELECT value FROM {$table})";
    $new_query7 = CRM_Core_I18n_Schema::rewriteQuery($query7);
    $this->assertEquals("SELECT * FROM civicrm_foo WHERE foo_id = (SELECT value FROM {$expectedRewrite})", $new_query7);
    // Test differern verbs
    $query8 = "DELETE FROM {$table}";
    $new_query8 = CRM_Core_I18n_Schema::rewriteQuery($query8);
    $this->assertEquals("DELETE FROM {$expectedRewrite}", $new_query8);
    // Test Currently skipped for civicrm_option_group and civicrm_event due to issues with the regex.
    // Agreed as not a blocker for CRM-20427 as an issue previously
    if (!$skip_tests) {
      $query9 = 'INSERT INTO ' . "{$table}" . ' (foo, bar) VALUES (123, "' . "Just a {$table} string" . '")';
      $new_query9 = CRM_Core_I18n_Schema::rewriteQuery($query9);
      $this->assertEquals('INSERT INTO ' . "{$expectedRewrite}" . ' (foo, bar) VALUES (123, "' . "Just a {$table} string" . '")', $new_query9);
    }
  }

}
