<?php
/*
+--------------------------------------------------------------------+
| CiviCRM version 4.6                                                |
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
 * @file
 * File for the CRM_Contact_Imports_Parser_ContactTest class.
 */

require_once 'CiviTest/CiviUnitTestCase.php';

/**
 *  Test contact import parser.
 *
 * @package CiviCRM
 */
class CRM_Contact_Imports_Parser_ContactTest extends CiviUnitTestCase {
  protected $_tablesToTruncate = array();

  /**
   * Setup function.
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Test that the import parser will do an update when external identifier is set.
   *
   * @throws \Exception
   */
  public function testImportParserWithUpdateWithoutExternalIdentifier() {
    $originalValues = array(
      'first_name' => 'Bill',
      'last_name' => 'Gates',
      'email' => 'bill.gates@microsoft.com',
      'nick_name' => 'Billy-boy',
    );
    $this->runImport($originalValues, CRM_Import_Parser::DUPLICATE_UPDATE, CRM_Import_Parser::VALID);
    $result = $this->callAPISuccessGetSingle('Contact', $originalValues);
    $originalValues['nick_name'] = 'Old Bill';
    $this->runImport($originalValues, CRM_Import_Parser::DUPLICATE_UPDATE, CRM_Import_Parser::VALID);
    $originalValues['id'] = $result['id'];
    $this->assertEquals('Old Bill', $this->callAPISuccessGetValue('Contact', array('id' => $result['id'], 'return' => 'nick_name')));
    $this->callAPISuccessGetSingle('Contact', $originalValues);
  }

  /**
   * Test that the import parser will do an update when external identifier is set.
   *
   * @throws \Exception
   */
  public function testImportParserWithUpdateWithExternalIdentifier() {
    $originalValues = array(
      'first_name' => 'Bill',
      'last_name' => 'Gates',
      'email' => 'bill.gates@microsoft.com',
      'external_identifier' => 'windows',
      'nick_name' => 'Billy-boy',
    );
    $this->runImport($originalValues, CRM_Import_Parser::DUPLICATE_UPDATE, CRM_Import_Parser::VALID);
    $result = $this->callAPISuccessGetSingle('Contact', $originalValues);
    $this->assertEquals($result['id'], CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', 'windows', 'id', 'external_identifier', TRUE));
    $this->assertEquals('windows', $result['external_identifier']);
    $originalValues['nick_name'] = 'Old Bill';
    $this->runImport($originalValues, CRM_Import_Parser::DUPLICATE_UPDATE, CRM_Import_Parser::VALID);
    $originalValues['id'] = $result['id'];
    $this->assertEquals('Old Bill', $this->callAPISuccessGetValue('Contact', array('id' => $result['id'], 'return' => 'nick_name')));
    $this->callAPISuccessGetSingle('Contact', $originalValues);
  }

  /**
   * Test that the import parser updates when a new external identifier is set.
   *
   * @throws \Exception
   */
  public function testImportParserWithUpdateWithNewExternalIdentifier() {
    $originalValues = array(
      'first_name' => 'Bill',
      'last_name' => 'Gates',
      'email' => 'bill.gates@microsoft.com',
      'nick_name' => 'Billy-boy',
    );
    $this->runImport($originalValues, CRM_Import_Parser::DUPLICATE_UPDATE, CRM_Import_Parser::VALID);
    $result = $this->callAPISuccessGetSingle('Contact', $originalValues);
    $originalValues['nick_name'] = 'Old Bill';
    $originalValues['external_identifier'] = 'windows';
    $this->runImport($originalValues, CRM_Import_Parser::DUPLICATE_UPDATE, CRM_Import_Parser::VALID);
    $originalValues['id'] = $result['id'];
    $this->assertEquals('Old Bill', $this->callAPISuccessGetValue('Contact', array('id' => $result['id'], 'return' => 'nick_name')));
    $this->callAPISuccessGetSingle('Contact', $originalValues);
  }

  /**
   * Run the import parser.
   *
   * @param array $originalValues
   *
   * @param int $onDuplicateAction
   * @param int $expectedResult
   */
  protected function runImport($originalValues, $onDuplicateAction, $expectedResult) {
    $fields = array_keys($originalValues);
    $values = array_values($originalValues);
    $parser = new CRM_Contact_Import_Parser_Contact($fields);
    $parser->_contactType = 'Individual';
    $parser->_onDuplicate = $onDuplicateAction;
    $parser->init();
    $this->assertEquals($expectedResult, $parser->import($onDuplicateAction, $values));
  }
}
