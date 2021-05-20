<?php
namespace Civi\Test;

use Civi\Angular\Page\Main;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * This is an example of a barebones test which uses a hook (based on CiviTestListener).
 *
 * @group headless
 */
class ExampleHookTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, EventSubscriberInterface {

  /**
   * @var \CRM_Contact_DAO_Contact
   */
  protected $contact;

  protected $tally;

  public function setUpHeadless() {
    return \Civi\Test::headless()->apply();
  }

  protected function setUp(): void {
    $this->contact = \CRM_Core_DAO::createTestObject('CRM_Contact_DAO_Contact', [
      'contact_type' => 'Individual',
    ]);
    $session = \CRM_Core_Session::singleton();
    $session->set('userID', $this->contact->id);
    $this->tally = ['civi.api.resolve' => 0, 'civi.api.prepare' => 0];
  }

  protected function tearDown(): void {
    $this->contact->delete();
  }

  /**
   * Listen to hook_civicrm_alterContent in traditional hook format.
   *
   * @see \CRM_Utils_Hook::alterContent
   */
  public function hook_civicrm_alterContent(&$content, $context, $tplName, &$object) {
    $content .= "zzzyyyxxx";
  }

  /**
   * Listen to hook_civicrm_alterContent in Symfony event format.
   *
   * @see \CRM_Utils_Hook::alterContent
   */
  public function on_hook_civicrm_alterContent(\Civi\Core\Event\GenericHookEvent $event) {
    $event->content .= "111222333";
  }

  /**
   * Listen to `civi.api.resolve` in Symfony event format.
   *
   * @see \Civi\API\Event\ResolveEvent
   */
  public function on_civi_api_resolve(\Civi\API\Event\ResolveEvent $event) {
    $this->tally['civi.api.resolve']++;
  }

  public static function getSubscribedEvents() {
    return [
      'civi.api.prepare' => ['myCiviApiPrepare', 1234],
    ];
  }

  /**
   * Listen to `civi.api.resolve` in Symfony event format.
   *
   * @see \Civi\API\Event\ResolveEvent
   */
  public function myCiviApiPrepare(\Civi\API\Event\PrepareEvent $event) {
    $this->tally['civi.api.prepare']++;
  }

  public function testPageOutput() {
    ob_start();
    $p = new Main();
    $p->run();
    $content = ob_get_contents();
    ob_end_clean();
    $this->assertRegExp(';zzzyyyxxx;', $content);
    $this->assertRegExp(';111222333;', $content);
  }

  public function testGetFields() {
    $this->assertEquals(0, $this->tally['civi.api.resolve']);
    $this->assertEquals(0, $this->tally['civi.api.prepare']);
    \civicrm_api3('Contact', 'getfields', []);
    $this->assertTrue($this->tally['civi.api.resolve'] > 0);
    $this->assertTrue($this->tally['civi.api.prepare'] > 0);
  }

}
