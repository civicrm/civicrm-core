<?php
namespace Civi\Test;

use Civi\Angular\Page\Main;

/**
 * This is an example of a barebones test which uses a hook (based on CiviTestListener).
 *
 * @group headless
 */
class ExampleHookTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface {

  /**
   * @var \CRM_Contact_DAO_Contact
   */
  protected $contact;

  public function setUpHeadless() {
    return \Civi\Test::headless()->apply();
  }

  protected function setUp() {
    $this->contact = \CRM_Core_DAO::createTestObject('CRM_Contact_DAO_Contact', array(
      'contact_type' => 'Individual',
    ));
    $session = \CRM_Core_Session::singleton();
    $session->set('userID', $this->contact->id);
  }

  protected function tearDown() {
    $this->contact->delete();
  }

  /**
   * @see \CRM_Utils_Hook::alterContent
   */
  public function hook_civicrm_alterContent(&$content, $context, $tplName, &$object) {
    $content .= "zzzyyyxxx";
  }

  public function testPageOutput() {
    ob_start();
    $p = new Main();
    $p->run();
    $content = ob_get_contents();
    ob_end_clean();
    $this->assertRegExp(';zzzyyyxxx;', $content);
  }

}
