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
class CRM_Contribute_Import_Parser_ContributionTest extends CiviUnitTestCase {
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
   * @dataProvider getThousandSeparators
   *
   * @param string $thousandSeparator
   *
   * @throws \Exception
   */
  public function testImportParserWithSoftCreditsByExternalIdentifier($thousandSeparator) {
    $this->setCurrencySeparators($thousandSeparator);
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
      "total_amount" => $this->formatMoneyInput(1230.99),
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
    $this->assertEquals(1230.99, $contributionsOfMainContact->total_amount);
    $this->assertEquals(1230.99, $contributionsOfMainContact->net_amount);
    $this->assertEquals(0, $contributionsOfMainContact->fee_amount);

    $params["contact_id"] = $contact2Id;
    $contributionsOfSoftContact = CRM_Contribute_BAO_ContributionSoft::retrieve($params, $values);
    $this->assertEquals(1, $contributionsOfMainContact->N, 'Contribution not added for primary contact');
    $this->assertEquals(1, $contributionsOfSoftContact->N, 'Soft Contribution not added for secondary contact');
    $this->callAPISuccess('ContributionSoft', 'Delete', ['id' => $contributionsOfSoftContact->id]);
    $this->callAPISuccess('Contribution', 'Delete', ['id' => $contributionsOfMainContact->id]);
  }

  /**
   * Test dates are parsed
   */
  public function testParsedDates() {
    $mapperKeys = [];
    $form = new CRM_Contribute_Import_Parser_Contribution($mapperKeys);
    $params = ['receive_date' => '20/10/2019'];
    CRM_Core_Session::singleton()->set('dateTypes', 32);
    $form->formatDateFields($params);
    $this->assertEquals('20191020', $params['receive_date']);

    $params = ['receive_date' => '20/10/2019'];
    CRM_Core_Session::singleton()->set('dateTypes', 32);
    $form->formatInput($params);
    $this->assertEquals('20191020', $params['receive_date']);
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
