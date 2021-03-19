<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */
namespace Civi\FlexMailer;

/**
 * Class ValidatorTest
 *
 * @group headless
 */
class ValidatorTest extends \CiviUnitTestCase {

  public function setUp(): void {
    // Activate before transactions are setup.
    $manager = \CRM_Extension_System::singleton()->getManager();
    if ($manager->getStatus('org.civicrm.flexmailer') !== \CRM_Extension_Manager::STATUS_INSTALLED) {
      $manager->install(array('org.civicrm.flexmailer'));
    }

    parent::setUp();
    \Civi::settings()->set('flexmailer_traditional', 'flexmailer');
  }

  public function getExamples() {
    $defaults = array(
      'id' => 123,
      'subject' => 'Default subject',
      'name' => 'Default name',
      'from_name' => 'Default sender',
      'from_email' => 'default@example.org',
      'body_html' => '<html>Default HTML body {action.unsubscribeUrl} {domain.address}</html>',
      'body_text' => 'Default text body {action.unsubscribeUrl} {domain.address}',
      'template_type' => 'traditional',
      'template_options' => array(),
    );

    $es = array();
    $es[] = array(
      array_merge($defaults, array('subject' => NULL)),
      array('subject' => '/Field "subject" is required./'),
    );
    $es[] = array(
      array_merge($defaults, array('subject' => NULL, 'from_name' => NULL)),
      array(
        'subject' => '/Field "subject" is required./',
        'from_name' => '/Field "from_name" is required./',
      ),
    );
    $es[] = array(
      array_merge($defaults, array('body_text' => NULL)),
      array(),
    );
    $es[] = array(
      array_merge($defaults, array('body_html' => NULL)),
      array(),
    );
    $es[] = array(
      array_merge($defaults, array('body_html' => NULL, 'body_text' => NULL)),
      array('(body_html|body_text)' => '/Field "body_html" or "body_text" is required./'),
    );
    $es[] = array(
      array_merge($defaults, array('body_html' => 'Muahaha. I omit the mandatory tokens!')),
      array(
        'body_html:domain.address'  => '/This message is missing.*postal address/',
        'body_html:action.optOutUrl or action.unsubscribeUrl' => '/This message is missing.*Unsubscribe via web page/',
      ),
    );
    $es[] = array(
      array_merge($defaults, array('body_html' => 'I omit the mandatory tokens, but checking them is someone else\'s job!', 'template_type' => 'esperanto')),
      array(),
    );
    return $es;
  }

  /**
   * @param array $mailingData
   *   Mailing content (per CRM_Mailing_DAO_Mailing) as an array.
   * @param array $expectedErrors
   * @dataProvider getExamples
   */
  public function testExamples($mailingData, $expectedErrors): void {
    $mailing = new \CRM_Mailing_DAO_Mailing();
    $mailing->copyValues($mailingData);
    $actualErrors = Validator::createAndRun($mailing);
    $this->assertEquals(
      array_keys($actualErrors),
      array_keys($expectedErrors)
    );
    foreach ($expectedErrors as $key => $pat) {
      $this->assertRegExp($pat, $actualErrors[$key], "Error for \"$key\" should match pattern");
    }
  }

}
