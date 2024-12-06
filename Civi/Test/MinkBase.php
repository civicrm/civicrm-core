<?php

namespace Civi\Test;

use Civi;
use Behat\Mink\WebAssert;
use Behat\Mink\Mink;
use Behat\Mink\Session;
use DMore\ChromeDriver\ChromeDriver;

/**
 * Class MinkBase.  Helper functions for E2E testing.
 */
abstract class MinkBase extends \CiviEndToEndTestCase {

  protected ?Mink $mink = NULL;
  protected bool $screenshotsEnabled = FALSE;

  protected function setUp(): void {
    parent::setUp();

    if (CIVICRM_UF === 'Drupal8' && version_compare(\CRM_Core_Config::singleton()->userSystem->getVersion(), '10', '<')) {
      $this->markTestSkipped('Browser testing is currently unsupported on Civi-Drupal 9');
    }

    // $this->failOnJavascriptConsoleErrors = TRUE; // Not implemented yet
    $this->mink = $this->createMink();
    $this->screenshotsEnabled = $_ENV['SCREENSHOTS'] ?? FALSE;
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
    // The use of frontend:// or backend:// is abstract on some env's. For WP, only 'frontend' supports auth
    $loginUrl = Civi::url('frontend://civicrm/authx/login')->addQuery([
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
    $chromeUrl = sprintf('http://%s:%s', getenv('CHROME_HOST') ?: 'localhost', getenv('CHROME_PORT') ?: '9222');

    $driver = new ChromeDriver($chromeUrl, NULL, (string) Civi::url('[cms.root]', 'a'));
    $session = new Session($driver);
    $mink = new Mink(['browser' => $session]);
    $mink->setDefaultSessionName('browser');

    $mink->getSession()->start();
    return $mink;
  }

  /**
   * Creates a screenshot.
   *
   * @param string $filename
   *   The file name of the resulting screenshot including a writable path. For
   *   example, /tmp/test_screenshot.jpg.
   * @param bool $set_background_color
   *   (optional) By default this method will set the background color to white.
   *   Set to FALSE to override this behavior.
   * @param bool $force
   *   (optional) By default this method will not take screenshots for performance reasons.
   *   Set to TRUE to take screenshots.
   *
   * @throws \Behat\Mink\Exception\UnsupportedDriverActionException
   *   When operation not supported by the driver.
   * @throws \Behat\Mink\Exception\DriverException
   *   When the operation cannot be done.
   */
  protected function createScreenshot($filename, $set_background_color = TRUE, $force = FALSE) : void {
    if (!($this->screenshotsEnabled || $force)) {
      return;
    }
    $session = $this->mink->getSession();
    if ($set_background_color) {
      $session->executeScript("document.body.style.backgroundColor = 'white';");
    }
    $image = $session->getScreenshot();
    file_put_contents($filename, $image);
  }

}
