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
 *  Test APIv3 civicrm_contribute_* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Contribution
 * @group headless
 */
class CRM_Contribute_Form_Task_PDFLetterCommonTest extends CiviUnitTestCase {

  /**
   * Assume empty database with just civicrm_data.
   */
  protected $_individualId;

  protected $_docTypes = NULL;

  protected $_contactIds = NULL;

  protected function setUp() {
    parent::setUp();
    $this->_individualId = $this->individualCreate(array('first_name' => 'Anthony', 'last_name' => 'Collins'));
    $this->_docTypes = CRM_Core_SelectValues::documentApplicationType();
  }

  /**
   * Clean up after each test.
   */
  public function tearDown() {
    $this->quickCleanUpFinancialEntities();
    CRM_Utils_Hook::singleton()->reset();
  }

  /**
   * Test the buildContributionArray function.
   */
  public function testBuildContributionArray() {
    $this->_individualId = $this->individualCreate();

    $customGroup = $this->callAPISuccess('CustomGroup', 'create', array(
      'title' => 'Test Custom Set for Contribution',
      'extends' => 'Contribution',
      'is_active' => TRUE,
    ));
    $params = array(
      'custom_group_id' => $customGroup['id'],
      'label' => 'Text field',
      'html_type' => 'Text',
      'data_type' => 'String',
      'weight' => 1,
      'is_active' => 1,
    );
    $customField = $this->callAPISuccess('CustomField', 'create', $params);
    $customFieldKey = 'custom_' . $customField['id'];
    $campaignTitle = 'Test Campaign ' . substr(sha1(rand()), 0, 7);

    $params = array(
      'contact_id' => $this->_individualId,
      'total_amount' => 6,
      'campaign_id' => $this->campaignCreate(array('title' => $campaignTitle), FALSE),
      'financial_type_id' => 'Donation',
      $customFieldKey => 'Text_' . substr(sha1(rand()), 0, 7),
    );
    $contributionIDs = $returnProperties = array();
    $result = $this->callAPISuccess('Contribution', 'create', $params);
    $contributionIDs[] = $result['id'];
    $this->hookClass->setHook('civicrm_tokenValues', array($this, 'hookTokenValues'));

    // assume that there are two token {contribution.financial_type} and
    // {contribution.custom_N} in message content
    $messageToken = array(
      'contribution' => array(
        'financial_type',
        'payment_instrument',
        'campaign',
        $customFieldKey,
      ),
    );

    list($contributions, $contacts) = CRM_Contribute_Form_Task_PDFLetterCommon::buildContributionArray('contact_id', $contributionIDs, $returnProperties, TRUE, TRUE, $messageToken, 'test', '**', FALSE);

    $this->assertEquals('Anthony', $contacts[$this->_individualId]['first_name']);
    $this->assertEquals('emo', $contacts[$this->_individualId]['favourite_emoticon']);
    $this->assertEquals('Donation', $contributions[$result['id']]['financial_type']);
    $this->assertEquals($campaignTitle, $contributions[$result['id']]['campaign']);
    $this->assertEquals('Check', $contributions[$result['id']]['payment_instrument']);
    // CRM-20359: assert that contribution custom field token is rightfully replaced by its value
    $this->assertEquals($params[$customFieldKey], $contributions[$result['id']][$customFieldKey]);

    $this->customFieldDelete($customField['id']);
    $this->customGroupDelete($customGroup['id']);
  }

  /**
   * Implement token values hook.
   *
   * @param array $details
   * @param array $contactIDs
   * @param int $jobID
   * @param array $tokens
   * @param string $className
   */
  public function hookTokenValues(&$details, $contactIDs, $jobID, $tokens, $className) {
    foreach ($details as $index => $detail) {
      $details[$index]['favourite_emoticon'] = 'emo';
    }
  }

  /**
   * Test contribution token replacement in
   * html returned by postProcess function.
   */
  public function testPostProcess() {
    $this->_individualId = $this->individualCreate();
    foreach (array('docx', 'odt') as $docType) {
      $formValues = array(
        'is_unit_test' => TRUE,
        'group_by' => NULL,
        'document_file' => array(
          'name' => __DIR__ . "/sample_documents/Template.$docType",
          'type' => $this->_docTypes[$docType],
        ),
      );

      $contributionParams = array(
        'contact_id' => $this->_individualId,
        'total_amount' => 100,
        'financial_type_id' => 'Donation',
      );
      $contribution = $this->callAPISuccess('Contribution', 'create', $contributionParams);
      $contributionId = $contribution['id'];
      $form = new CRM_Contribute_Form_Task_PDFLetter();
      $form->setContributionIds(array($contributionId));
      $format = Civi::settings()->get('dateformatFull');
      $date = CRM_Utils_Date::getToday();
      $displayDate = CRM_Utils_Date::customFormat($date, $format);

      $html = CRM_Contribute_Form_Task_PDFLetterCommon::postProcess($form, $formValues);
      $expectedValues = array(
        'Hello Anthony Collins',
        '$ 100.00',
        $displayDate,
        'Donation',
      );

      foreach ($expectedValues as $val) {
        $this->assertTrue(strpos($html[$contributionId], $val) !== 0);
      }
    }
  }

}
