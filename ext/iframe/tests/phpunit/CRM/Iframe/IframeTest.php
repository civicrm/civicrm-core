<?php

use CRM_Iframe_ExtensionUtil as E;
use Civi\Test\EndToEndInterface;

/**
 * @group e2e
 */
class CRM_Iframe_IframeTest extends \PHPUnit\Framework\TestCase implements EndToEndInterface {

  use \Civi\Test\HttpTestTrait;

  public static function setUpBeforeClass(): void {
    \Civi\Test::e2e()->installMe(__DIR__)->apply();
  }

  /**
   * Enable iframe connector and send a request.
   */
  public function testBasicRequest(): void {
    if (!Civi::service('iframe')->isSupported()) {
      $this->markTestSkipped('iframe extension does not support activation in this environment');
    }

    if (CIVICRM_UF !== 'WordPress') {
      \Civi\Api4\Iframe::installScript()->setCheckPermissions(FALSE)->execute();
    }

    $eventId = CRM_Core_DAO::singleValueQuery('SELECT min(id) FROM civicrm_event');
    $this->assertTrue(is_numeric($eventId), 'Database should have at least one event');

    $url = Civi::url('iframe://civicrm/event/info')->addQuery([
      'reset' => 1,
      'id' => $eventId,
    ]);
    $response = $this->createGuzzle()->get((string) $url);
    $this->assertContentType('text/html', $response);
    $this->assertStatusCode(200, $response);
    $this->assertBodyRegexp('/crm-register-button/', $response);
    $this->assertBodyRegexp('/civicrm-iframe-page/', $response);
    $this->assertNotBodyRegexp(';misc/drupal.js;', $response);
  }

}
