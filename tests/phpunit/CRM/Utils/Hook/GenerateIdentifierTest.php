<?php

/**
 * Class CRM_Utils_Hook_Generate_Identifier_Test
 * @group headless
 */
class CRM_Utils_Hook_Generate_Identifier_Test extends CiviUnitTestCase {

  protected $_last_context = '';

  public function setUp() {
    $this->_last_context = '';
    parent::setUp();
  }

  public function tearDown() {
    parent::tearDown();
  }


  /**
   * Verify the generateIdentifier hook for contact's external_identifier
   */
  public function testContactExternalIdentifierHook() {
    $contactA = civicrm_api3('Contact', 'create', array(
      'first_name'   => 'Captain',
      'last_name'    => 'Hook',
      'contact_type' => 'Individual'
    ));
    $this->assertAPISuccess($contactA);
    $contactA = civicrm_api3('Contact', 'getsingle', array('id' => $contactA['id']));
    $this->assertEquals('', $contactA['external_identifier'], "A new contact should not have an external identifier");

    // now register the hook
    $this->hookClass->setHook('civicrm_generateIdentifier', array($this, 'hooktest_civicrm_generateIdentifier'));

    // ...and try again
    $contactB = civicrm_api3('Contact', 'create', array(
      'first_name'   => 'Jake',
      'last_name'    => 'Hook',
      'contact_type' => 'Individual'
    ));
    $this->assertAPISuccess($contactB);
    $contactB = civicrm_api3('Contact', 'getsingle', array('id' => $contactB['id']));
    $this->assertEquals('HOOKAH', $contactB['external_identifier'], "Hook did not set external_identifier");
    $this->assertEquals('contact_external_identifier', $this->_last_context, "Hook was not called with the right context");

    $this->hookClass->reset();
  }

  /**
   * Verify the generateIdentifier hook for campaign's external_identifier
   */
  public function testCampaignExternalIdentifierHook() {
    $campaignA = civicrm_api3('Campaign', 'create', array(
      'title' => 'Hooking Up',
    ));
    $this->assertNotNull($campaignA);
    $this->assertAPISuccess($campaignA);
    $campaignA = civicrm_api3('Campaign', 'getsingle', array('id' => $campaignA['id']));
    $this->assertEquals('', CRM_Utils_Array::value('external_identifier', $campaignA), "A new campaign should not have an external identifier");

    // now register the hook
    $this->hookClass->setHook('civicrm_generateIdentifier', array($this, 'hooktest_civicrm_generateIdentifier'));

    // ...and try again
    $campaignB = civicrm_api3('Campaign', 'create', array(
      'title' => 'Hookie Day',
    ));
    $this->assertAPISuccess($campaignB);
    $campaignB = civicrm_api3('Campaign', 'getsingle', array('id' => $campaignB['id']));
    $this->assertEquals('HOOKAH', CRM_Utils_Array::value('external_identifier', $campaignB), "A new campaign should not have an external identifier");
    $this->assertEquals('campaign_external_identifier', $this->_last_context, "Hook was not called with the right context");

    $this->hookClass->reset();
  }

  /**
   * Verify the generateIdentifier hook for contribution's creditnote_id
   */
  public function testCreditnoteHook() {
    $this->hookClass->setHook('civicrm_generateIdentifier', array($this, 'hooktest_civicrm_generateIdentifier'));

    // create a contribution
    $this->callAPISuccess('Contribution', 'create', array(
      'total_amount'           => '11.11',
      'financial_type_id'      => 1,
      'contact_id'             => 1,
      'payment_instrument_id'  => 1,
      'contribution_status_id' => 1,
      'trxn_id'                => 'HOOKAH2'
    ));
    // reload
    $contribution = $this->callAPISuccess('Contribution', 'getsingle', array('trxn_id' => 'HOOKAH2', 'return' => 'creditnote_id'));

    // this should have no creditnote
    $this->assertEquals('', CRM_Utils_Array::value('creditnote_id', $contribution), "A new contribution should not have a creditnote ID");

    // now cancel the contribution
    $contribution = $this->callAPISuccess('Contribution', 'create', array(
      'id' => $contribution['id'],
      'contribution_status_id' => 3, // Cancelled
      'cancel_reason'          => 'test'));
    // reload
    $contribution = $this->callAPISuccess('Contribution', 'getsingle', array('trxn_id' => 'HOOKAH2', 'return' => 'creditnote_id'));

    // this should have no creditnote
    $this->assertEquals('HOOKAH', CRM_Utils_Array::value('creditnote_id', $contribution), "The contribution should have our creditnote ID");
  }

  /**
   * Verify the generateIdentifier hook for contribution's invoice ID
   */
  public function testInvoiceIDHook() {
    $this->hookClass->setHook('civicrm_generateIdentifier', array($this, 'hooktest_civicrm_generateIdentifier'));

    // create contribution
    $this->callAPISuccess('Contribution', 'create', array(
      'total_amount'           => '11.11',
      'financial_type_id'      => 1,
      'contact_id'             => 1,
      'payment_instrument_id'  => 1,
      'contribution_status_id' => 1,
      'trxn_id'                => 'HOOKAH'
    ));
    $contribution = $this->callAPISuccess('Contribution', 'getsingle', array('trxn_id' => 'HOOKAH', 'return' => 'invoice_number'));

    // verify: no invoice number
    $this->assertEquals('', CRM_Utils_Array::value('invoice_number', $contribution), "A new contribution should not have an invoice ID");

    // create invoice
    $params = array('forPage' => 1, 'output' => 'pdf_invoice');
    CRM_Contribute_Form_Task_Invoice::printPDF(array($contribution['id']), $params, array($contribution['contact_id']));
    $this->assertEquals('invoice_number', $this->_last_context, "Hook was not called with the right context");

    // verify: invoice number
    $contribution = $this->callAPISuccess('Contribution', 'getsingle', array('trxn_id' => 'HOOKAH', 'return' => 'invoice_number'));
    $this->assertEquals('HOOKAH', CRM_Utils_Array::value('invoice_number', $contribution), "The invoice number should be ours");
  }




  /**
   * Helper hook implementation
   */
  public function hooktest_civicrm_generateIdentifier(&$identifier, $context, $object) {
    // store the context for evaluation
    $this->_last_context = $context;

    // set the identifiert to HOOKAH
    $identifier = "HOOKAH";

    if ($context == 'invoice_number') {
      // there is a bug in CiviCRM: the invoice_number doesn't get stored!
      if (empty($object->invoice_number)) {
        CRM_Core_DAO::setFieldValue('CRM_Contribute_DAO_Contribution', $object->id, 'invoice_number', $identifier);
      }
    }
  }
}
