<?php
namespace Civi;

/**
 * Ensure that the facade provides expected services.
 */
class FacadeTest extends \CiviUnitTestCase {
  public function testRequest() {
    $_GET['fooId'] = 123;
    $this->assertTrue(\Civi::request() instanceof \Symfony\Component\HttpFoundation\Request);
    $this->assertEquals(123, \Civi::request()->query->getInt('fooId'));
  }

  protected function tearDown() {
    unset($_GET['fooId']);
    parent::tearDown();
  }

}
