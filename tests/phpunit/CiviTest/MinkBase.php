<?php


use Behat\Mink\WebAssert;
use Behat\Mink\Mink;
use Behat\Mink\Session;
use Behat\Mink\Element\TraversableElement;
use DMore\ChromeDriver\ChromeDriver;

/**
 * Class MinkBase
 * @group e2e
 */
abstract class MinkBase extends \CiviEndToEndTestCase {
   /**
   * @var \Behat\Mink\Mink|null
   */
  protected ?Mink $mink = NULL;

  protected function setUp(): void {
    parent::setUp();
    $this->mink = $this->createMink();
    $GLOBALS['civicrm_url_defaults'][] = ['format' => 'absolute', 'scheme' => 'backend'];
  }

  protected function tearDown(): void {
    array_pop($GLOBALS['civicrm_url_defaults']);
    parent::tearDown();
  }

  protected function assertSession(): WebAssert {
    return $this->mink->assertSession();
  }

  protected function login(string $user): void {
    $tok = \Civi::service('crypto.jwt')->encode([
      'exp' => time() + 60 + 999999,
      'sub' => 'cid:' . $this->getUserId($user),
      'scope' => 'authx',
    ]);
    $loginUrl = Civi::url('backend://civicrm/authx/login')->addQuery([
      '_authx' => 'Bearer ' . $tok,
      '_authxSes' => 1,
    ]);
    $this->mink->getSession()->visit($loginUrl);
  }

  protected function visit(string $url): void {
    $this->mink->getSession()->visit($url);
  }

  private function getUserId(string $user): int {
    $r = civicrm_api3("Contact", "get", ["id" => "@user:" . $user]);
    foreach ($r['values'] as $id => $value) {
      $cid = $id;
      break;
    }
    if (empty($cid)) {
      throw new \RuntimeException("Failed to identify user ({$user})");
    }
    return $cid;
  }

  protected function createMink(): Mink {
    $chromeUrl = sprintf('http://%s:%s', getenv('LOCALHOST') ?: 'localhost', getenv('CHROME_PORT') ?: '9222');

    $driver = new ChromeDriver($chromeUrl, NULL, (string) Civi::url('[cms.root]', 'a'));
    $session = new Session($driver);
    $mink = new Mink(['browser' => $session]);
    $mink->setDefaultSessionName('browser');

    $mink->getSession()->start();
    return $mink;
  }
}