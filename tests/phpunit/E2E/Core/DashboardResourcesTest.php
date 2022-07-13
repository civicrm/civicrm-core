<?php

namespace E2E\Core;

use Civi\Test\HttpTestTrait;

/**
 * @package E2E\Core
 * @group e2e
 *
 * Get the dashboard. Ensure all JS/CSS resources are loadable.
 *
 * This is a basic smoke to ensure that will run in every E2E configuration.
 * It ensures that decent sample of JS/CSS resources can be loaded.
 */
class DashboardResourcesTest extends \CiviEndToEndTestCase {

  public static function setUpBeforeClass(): void {
    parent::setUpBeforeClass();
    \Civi\Test::e2e()
      ->install(['authx'])
      ->callback(
        function() {
          \CRM_Utils_System::synchronizeUsers();
        },
        'synchronizeUsers'
      )
      ->apply();
  }

  use HttpTestTrait;

  /**
   * Get a list of resources that should be referenced the dashboard page.
   *
   * @return array
   *   Each item defines the expected URL and expected content.
   */
  protected function getExpectedResources(): array {
    $result = [];

    // Check a mix of resources, including both
    // (a) static+dynamic resources and
    // (b) JS+CSS resources.

    $result[] = [
      // Example of a dynamic resource (JS)
      'url' => ';crm-l10n;',
      'content' => ';CRM\.config\.timeIs24Hr;',
    ];

    $result[] = [
      // Example of a static resource (JS)
      'url' => ';dist/jquery\.(min\.)?js;',
      'content' => ';(Copyright|\(c\)) jQuery Foundation;',
    ];

    $result[] = [
      // Example of a static resource (CSS)
      'url' => ';/civicrm\.css;',
      'content' => ';\.crm-container;',
    ];

    return $result;
  }

  /**
   * Get `civicrm/dashboard?reset=1` and assert that all resources are downloadable.
   */
  public function testGetAll() {
    global $_CV;
    $guzzle = $this->createGuzzle([
      'allow_redirects' => TRUE,
      'authx_user' => $_CV['ADMIN_USER'],
    ]);

    $dashboard = $guzzle->get('civicrm/dashboard?reset=1');
    $this->assertStatusCode(200, $dashboard);

    $expectedResources = $this->getExpectedResources();
    $actualResources = [];
    foreach ($this->findResourceUrls($dashboard->getBody()) as $srcUrl) {
      $actualResources[$srcUrl] = (string) $guzzle->get($srcUrl)->getBody();
    }

    foreach ($actualResources as $resUrl => $resContent) {
      foreach (array_keys($expectedResources) as $resNum) {
        $pattern = $expectedResources[$resNum];
        if (preg_match($pattern['url'], $resUrl)) {
          $this->assertRegExp($pattern['content'], $resContent, sprintf('URL (%s) should have content matching (%s)', $resUrl, $pattern['content']));
          unset($expectedResources[$resNum]);
        }
      }
    }
    $this->assertEquals([], $expectedResources, 'Every expected pattern should have a match in the result set. If any patterns remain, then something was missing.');
  }

  /**
   * Extract the list of JS and CSS URLs from the HTML body.
   *
   * @param string $htmlBody
   * @return string[]
   *   List of URLs.
   */
  protected function findResourceUrls(string $htmlBody): array {
    $doc = \phpQuery::newDocumentHTML($htmlBody);
    $resources = [];

    $doc->find('script')->each(function(\DOMElement $script) use (&$resources) {
      $srcUrl = $script->getAttribute('src');
      if (!empty($srcUrl)) {
        $resources[] = $srcUrl;
      }
    });

    $doc->find('link[rel=stylesheet]')->each(function(\DOMElement $style) use (&$resources) {
      $srcUrl = $style->getAttribute('href');
      if (!empty($srcUrl)) {
        $resources[] = $srcUrl;
      }
    });

    $doc->find('style')->each(function(\DOMElement $style) use (&$resources) {
      $lines = explode("\n", $style->nodeValue);
      foreach ($lines as $line) {
        if (preg_match('/@import url\("(.*)"\)/', $line, $m)) {
          $resources[] = $m[1];
        }
      }
    });

    return $resources;
  }

}
