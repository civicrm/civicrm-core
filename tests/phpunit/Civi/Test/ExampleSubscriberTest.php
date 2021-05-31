<?php
namespace Civi\Test;

use Civi\Angular\Page\Main;
use Civi\Core\Event\GenericHookEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * This is an example of a barebones test which implements `EventSubscriberInterface`. The method `getSubscribedEvents()` is
 * used to get a list of listeners.
 *
 * The underlying mechanism behind this is:
 *
 * - When booting headless Civi, `CRM_Utils_System_UnitTests::initialize()` looks up the active test object.
 * - It uses `EventScanner` to check the interfaces & methods for any listeners.
 *
 * @group headless
 */
class ExampleSubscriberTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, EventSubscriberInterface {

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

  public static function getSubscribedEvents() {
    return [
      'civi.api.resolve' => 'myCiviApiResolve',
      'civi.api.prepare' => ['myCiviApiPrepare', 1234],
      'hook_civicrm_alterContent' => ['myAlterContentObject', -7000],
      '&hook_civicrm_alterContent' => ['myAlterContentParams', -8000],
    ];
  }

  public function myCiviApiResolve(\Civi\API\Event\ResolveEvent $event): void {
    $this->tracker['civi.api.resolve'][__FUNCTION__] = TRUE;
  }

  public function myCiviApiPrepare(\Civi\API\Event\PrepareEvent $event): void {
    $this->tracker['civi.api.prepare'][__FUNCTION__] = TRUE;
  }

  public function myAlterContentObject(GenericHookEvent $event): void {
    $event->content .= ' ' . __FUNCTION__;
  }

  public function myAlterContentParams(&$content, $context, $tplName, &$object) {
    $content .= ' ' . __FUNCTION__;
  }

  public function testPageOutput() {
    ob_start();
    $p = new Main();
    $p->run();
    $content = ob_get_contents();
    ob_end_clean();
    $this->assertRegExp(';myAlterContentObject myAlterContentParams;', $content);
  }

  public function testGetFields() {
    $this->assertEquals([], $this->tracker['civi.api.resolve']);
    $this->assertEquals([], $this->tracker['civi.api.prepare']);
    \civicrm_api3('Contact', 'getfields', []);
    $this->assertEquals(['myCiviApiResolve' => TRUE], $this->tracker['civi.api.resolve']);
    $this->assertEquals(['myCiviApiPrepare' => TRUE], $this->tracker['civi.api.prepare']);
  }

}
