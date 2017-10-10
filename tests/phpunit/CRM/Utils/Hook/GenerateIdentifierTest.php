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
    // TODO: test create + cancel

    // TODO: test create refunded
  }

  /**
   * Verify the generateIdentifier hook for contribution's invoice ID
   */
  public function testInvoiceIDHook() {
    // TODO: create contribution

    // TODO: verify: no invoice id

    // TODO: create invoice

    // TODO: verify: invoice id
  }

  /**
   * Helper hook implementation
   */
  public function hooktest_civicrm_generateIdentifier(&$identifier, $context, $object) {
    // store the context for evaluation
    $this->_last_context = $context;

    // set the identifiert to HOOKAH
    $identifier = "HOOKAH";
  }
}
