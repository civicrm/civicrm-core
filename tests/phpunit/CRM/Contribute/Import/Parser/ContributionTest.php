<?php
/**
 * @file
 * File for the CRM_Contribute_Import_Parser_ContributionTest class.
 */
/**
 *  Test Contribution import parser.
 *
 * @package CiviCRM
 * @group headless
 */
class CRM_Contribution_Import_Parser_ContributionTest extends CiviUnitTestCase {
  protected $_tablesToTruncate = array();
  /**
   * Setup function.
   */
  public function setUp() {
    parent::setUp();
  }
  /**
   * Test import parser will add contribution and soft contribution each for different contact.
   *
   * In this case primary contact and secondary contact both are identified by external identifier.
   *
   * @throws \Exception
   */
  public function testImportParserWithSoftCreditsByExternalIdentifier() {
    $contact1Params = array(
      'first_name' => 'Contact',
      'last_name' => 'One',
      'external_identifier' => 'ext-1',
      'contact_type' => 'Individual',
    );
    $contact2Params = array(
      'first_name' => 'Contact',
      'last_name' => 'Two',
      'external_identifier' => 'ext-2',
      'contact_type' => 'Individual',
    );
    $contact1Id = $this->individualCreate($contact1Params);
    $contact2Id = $this->individualCreate($contact2Params);
    $values = array(
      "total_amount" => 10,
      "financial_type" => "Donation",
      "external_identifier" => "ext-1",
      "soft_credit" => "ext-2",
    );
    $mapperSoftCredit = array(NULL, NULL, NULL, "external_identifier");
    $mapperSoftCreditType = array(NULL, NULL, NULL, "1");
    $this->runImport($values, CRM_Import_Parser::DUPLICATE_UPDATE, CRM_Contribute_Import_Parser::SOFT_CREDIT, $mapperSoftCredit, NULL, $mapperSoftCreditType);
    $params = array(
      "contact_id" => $contact1Id,
    );
    $values = array();
    $contributionsOfMainContact = CRM_Contribute_BAO_Contribution::retrieve($params, $values, $values);
    $params["contact_id"] = $contact2Id;
    $contributionsOfSoftContact = CRM_Contribute_BAO_ContributionSoft::retrieve($params, $values);
    $this->assertEquals(1, count($contributionsOfMainContact), 'Contribution not added for primary contact');
    $this->assertEquals(1, count($contributionsOfSoftContact), 'Soft Contribution not added for secondary contact');
  }
  /**
   * Run the import parser.
   *
   * @param array $originalValues
   *
   * @param int $onDuplicateAction
   * @param int $expectedResult
   * @param array|null $mapperSoftCredit
   * @param array|null $mapperPhoneType
   * @param array|null $mapperSoftCreditType
   * @param array|null $fields
   *   Array of field names. Will be calculated from $originalValues if not passed in.
   */
  protected function runImport($originalValues, $onDuplicateAction, $expectedResult, $mapperSoftCredit = NULL, $mapperPhoneType = NULL, $mapperSoftCreditType = NULL, $fields = NULL) {
    if (!$fields) {
      $fields = array_keys($originalValues);
    }
    $values = array_values($originalValues);
    $parser = new CRM_Contribute_Import_Parser_Contribution($fields, $mapperSoftCredit, $mapperPhoneType, $mapperSoftCreditType);
    $parser->_contactType = 'Individual';
    $parser->init();
    $this->assertEquals($expectedResult, $parser->import($onDuplicateAction, $values), 'Return code from parser import was not as expected');
  }

}
