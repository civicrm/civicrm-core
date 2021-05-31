<?php
namespace Civi\Test;

use Civi\Angular\Page\Main;

/**
 * This is an example of a barebones test which implements `HookInterface`. Methods are automatically scanned to
 * find event-listeners based on a naming convention:
 *
 * - `function hook_*($arg1, &$arg2, ...)`: Bind to eponymous hook. Receive a list of ordered parameters.
 * - 'function on_*($event)`: Bind to eponymous Symfony event. Receive an event object.
 *
 * The underlying mechanism behind this is:
 *
 * - When booting headless Civi, `CRM_Utils_System_UnitTests::initialize()` looks up the active test object.
 * - It uses `EventScanner` to check the interfaces & methods for any listeners.
 *
 * @group headless
 */
class ExampleHookTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface {

  /**
   * @var \CRM_Contact_DAO_Contact
   */
  protected $contact;

  protected $tracker;

  public function setUpHeadless() {
    return \Civi\Test::headless()->apply();
  }

  protected function setUp(): void {
    $this->contact = \CRM_Core_DAO::createTestObject('CRM_Contact_DAO_Contact', [
      'contact_type' => 'Individual',
    ]);
    $session = \CRM_Core_Session::singleton();
    $session->set('userID', $this->contact->id);
    $this->tracker = ['civi.api.resolve' => [], 'civi.api.prepare' => []];
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
    $content .= ' ' . __FUNCTION__;
  }

  /**
   * Listen to hook_civicrm_alterContent in Symfony event format.
   *
   * @see \CRM_Utils_Hook::alterContent
   */
  public function on_hook_civicrm_alterContent(\Civi\Core\Event\GenericHookEvent $event): void {
    $event->content .= ' ' . __FUNCTION__;
  }

  /**
   * Listen to `civi.api.resolve` in Symfony event format.
   *
   * @see \Civi\API\Event\ResolveEvent
   */
  public function on_civi_api_resolve(\Civi\API\Event\ResolveEvent $event): void {
    $this->tracker['civi.api.resolve'][__FUNCTION__] = TRUE;
  }

  /**
   * Listen to `civi.api.resolve` in Symfony event format.
   *
   * @see \Civi\API\Event\PrepareEvent
   */
  public function on_civi_api_prepare(\Civi\API\Event\PrepareEvent $event): void {
    $this->tracker['civi.api.prepare'][__FUNCTION__] = TRUE;
  }

  public function testPageOutput() {
    ob_start();
    $p = new Main();
    $p->run();
    $content = ob_get_contents();
    ob_end_clean();
    $this->assertRegExp('; hook_civicrm_alterContent on_hook_civicrm_alterContent;', $content);
  }

  public function testGetFields() {
    $this->assertEquals([], $this->tracker['civi.api.resolve']);
    $this->assertEquals([], $this->tracker['civi.api.prepare']);
    \civicrm_api3('Contact', 'getfields', []);
    $this->assertEquals(['on_civi_api_resolve' => TRUE], $this->tracker['civi.api.resolve']);
    $this->assertEquals(['on_civi_api_prepare' => TRUE], $this->tracker['civi.api.prepare']);
  }

}
