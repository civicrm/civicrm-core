<?php

/**
 * @group headless
 */
class CRM_Mailing_TokensTest extends \CiviUnitTestCase {

  protected function setUp() {
    $this->useTransaction();
    parent::setUp();
    $this->callAPISuccess('mail_settings', 'get',
      array('api.mail_settings.create' => array('domain' => 'chaos.org')));
  }

  public function getExampleTokens() {
    $cases = array();

    $cases[] = array('text/plain', 'The {mailing.id}!', ';The [0-9]+!;');
    $cases[] = array('text/plain', 'The {mailing.name}!', ';The Example Name!;');
    $cases[] = array('text/plain', 'The {mailing.editUrl}!', ';The http.*civicrm/mailing/send.*!;');
    $cases[] = array('text/plain', 'To subscribe: {action.subscribeUrl}!', ';To subscribe: http.*civicrm/mailing/subscribe.*!;');
    $cases[] = array('text/plain', 'To optout: {action.optOutUrl}!', ';To optout: http.*civicrm/mailing/optout.*!;');
    $cases[] = array('text/plain', 'To unsubscribe: {action.unsubscribe}!', ';To unsubscribe: u\.123\.456\.abcd1234@chaos.org!;');

    // TODO: Think about supporting dynamic tokens like "{action.subscribe.\d+}"

    return $cases;
  }

  /**
   * Check that mailing-tokens are generated (given a mailing_id as input).
   *
   * @param string $inputTemplateFormat
   *   Ex: 'text/plain' or 'text/html'
   * @param string $inputTemplate
   *   Ex: 'Hello, {contact.first_name}'.
   * @param string $expectRegex
   * @dataProvider getExampleTokens
   */
  public function testTokensWithMailingId($inputTemplateFormat, $inputTemplate, $expectRegex) {
    $mailing = CRM_Core_DAO::createTestObject('CRM_Mailing_DAO_Mailing', array(
      'name' => 'Example Name',
    ));
    $contact = CRM_Core_DAO::createTestObject('CRM_Contact_DAO_Contact');

    $p = new \Civi\Token\TokenProcessor(Civi::service('dispatcher'), array(
      'mailingId' => $mailing->id,
    ));
    $p->addMessage('example', $inputTemplate, $inputTemplateFormat);
    $p->addRow()->context(array(
      'contactId' => $contact->id,
      'mailingJobId' => 123,
      'mailingActionTarget' => array(
        'id' => 456,
        'hash' => 'abcd1234',
        'email' => 'someone@example.com',
      ),
    ));
    $p->evaluate();
    $count = 0;
    foreach ($p->getRows() as $row) {
      $this->assertRegExp($expectRegex, $row->render('example'));
      $count++;
    }
    $this->assertEquals(1, $count);
  }

  /**
   * Check that mailing-tokens are generated (given a mailing DAO as input).
   */
  public function testTokensWithMailingObject() {
    // We only need one case to see that the mailing-object works as
    // an alternative to the mailing-id.
    $inputTemplateFormat = 'text/plain';
    $inputTemplate = 'To optout: {action.optOutUrl}!';
    $expectRegex = ';To optout: http.*civicrm/mailing/optout.*!;';

    $mailing = CRM_Core_DAO::createTestObject('CRM_Mailing_DAO_Mailing', array(
      'name' => 'Example Name',
    ));
    $contact = CRM_Core_DAO::createTestObject('CRM_Contact_DAO_Contact');

    $p = new \Civi\Token\TokenProcessor(Civi::service('dispatcher'), array(
      'mailing' => $mailing,
    ));
    $p->addMessage('example', $inputTemplate, $inputTemplateFormat);
    $p->addRow()->context(array(
      'contactId' => $contact->id,
      'mailingJobId' => 123,
      'mailingActionTarget' => array(
        'id' => 456,
        'hash' => 'abcd1234',
        'email' => 'someone@example.com',
      ),
    ));
    $p->evaluate();
    $count = 0;
    foreach ($p->getRows() as $row) {
      $this->assertRegExp($expectRegex, $row->render('example'));
      $count++;
    }
    $this->assertEquals(1, $count);
  }

  /**
   * Check the behavior in the erroneous situation where someone uses
   * a mailing-related token without providing a mailing ID.
   */
  public function testTokensWithoutMailing() {
    // We only need one case to see that the mailing-object works as
    // an alternative to the mailing-id.
    $inputTemplateFormat = 'text/plain';
    $inputTemplate = 'To optout: {action.optOutUrl}!';

    $mailing = CRM_Core_DAO::createTestObject('CRM_Mailing_DAO_Mailing', array(
      'name' => 'Example Name',
    ));
    $contact = CRM_Core_DAO::createTestObject('CRM_Contact_DAO_Contact');

    $p = new \Civi\Token\TokenProcessor(Civi::service('dispatcher'), array(
      'mailing' => $mailing,
    ));
    $p->addMessage('example', $inputTemplate, $inputTemplateFormat);
    $p->addRow()->context(array(
      'contactId' => $contact->id,
    ));
    try {
      $p->evaluate();
      $this->fail('TokenProcessor::evaluate() should have thrown an exception');
    }
    catch (CRM_Core_Exception $e) {
      $this->assertRegExp(';Cannot use action tokens unless context defines mailingJobId and mailingActionTarget;', $e->getMessage());
    }
  }

}
