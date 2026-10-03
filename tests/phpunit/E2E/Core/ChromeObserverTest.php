<?php

namespace E2E\Core;

use Behat\Mink\Exception\DriverException;
use Civi\Test\MinkBase;

/**
 * @group e2e
 */
class ChromeObserverTest extends MinkBase {

  private ?string $outputDir = NULL;

  protected function tearDown(): void {
    if ($this->outputDir) {
      array_map('unlink', glob("$this->outputDir/*"));
      rmdir($this->outputDir);
    }
    parent::tearDown();
  }

  public function testHealthyPageReportsNothing(): void {
    $this->visitHtml('<p>ok</p><script>console.log("log"); console.info("info"); debugger; document.title = "Healthy";</script>');

    $this->assertMatchesRegularExpression('/^--- Browser diagnostics ---\nPage: http\S+\/civicrm\.css "Healthy"\nRenderer: responds, readyState=complete\n$/', $this->report());
  }

  public function testReportsPageErrors(): void {
    $missing = rtrim((string) \Civi::url('[cms.root]', 'a'), '/') . '/civicrm-chrome-observer-missing.png?token=secret';
    $this->visitHtml(<<<HTML
      <img src="$missing">
      <script>
        console.error('console error', 42);
        console.warn('console warning');
        Promise.reject(new Error('rejected promise'));
        fetch('http://chrome-observer.invalid/').catch(() => {});
        setTimeout(() => { throw new Error('uncaught error'); });
        document.createElement('form').submit();
      </script>
      HTML);
    usleep(1000000);

    $report = $this->report();
    $this->assertStringContainsString("HTTP errors (1):\n  404 Image http", $report);
    $this->assertStringContainsString('civicrm-chrome-observer-missing.png?…', $report);
    $this->assertStringNotContainsString('secret', $report);
    $this->assertMatchesRegularExpression('/Failed requests \(1\):\n  Fetch GET http:\/\/chrome-observer.invalid\/: net::ERR_NAME_NOT_RESOLVED\n/', $report);
    $this->assertStringContainsString('JavaScript exceptions (2):', $report);
    $this->assertStringContainsString('  Error: rejected promise | at <anonymous>:4:', $report);
    $this->assertStringContainsString('  Error: uncaught error | at <anonymous>:6:', $report);
    $this->assertStringContainsString("Browser log (1):\n  javascript/warning: Form submission canceled because the form is not connected\n", $report);
    $this->assertMatchesRegularExpression('/Console \(2\):\n  error: console error 42 @ script \d+:2:\d+\n  warning: console warning @ script \d+:3:\d+\n/', $report);
  }

  public function testPausesBusyRendererForStack(): void {
    $this->visitHtml('<script>function spinForSeconds(s) { const end = Date.now() + s * 1000; while (Date.now() < end); } setTimeout(() => spinForSeconds(6), 200);</script>');
    usleep(500000);

    $report = $this->report();
    $this->assertStringContainsString('Renderer: does not respond within 3s', $report);
    $this->assertMatchesRegularExpression('/JavaScript running in the renderer:\n  spinForSeconds @ script \d+:1:\d+\n  \(anonymous\) @ script \d+:1:\d+\n/', $report);
  }

  public function testKeepsLatestEntriesPerSection(): void {
    $this->visitHtml('<script>for (let i = 1; i <= 25; i++) console.error("error " + i);</script>');

    $report = $this->report();
    $this->assertStringContainsString("Console (25, earliest 5 not shown):\n  error: error 6 @ ", $report);
    $this->assertStringContainsString("  error: error 25 @ ", $report);
    $this->assertStringNotContainsString('error: error 5 @', $report);
  }

  public function testReportsOpenDialogWithoutProbing(): void {
    $this->visitHtml('<script>setTimeout(() => alert("dialog text"), 100);</script>');
    usleep(500000);

    $report = $this->report();
    $this->assertStringContainsString("Renderer: blocked by an open alert dialog\nDialogs (1):\n  alert: dialog text\n", $report);
    $this->mink->getSession()->getDriver()->dismissAlert();
  }

  public function testReportsCrash(): void {
    $this->visitHtml('<p>ok</p>');
    try {
      $this->visit('chrome://crash');
    }
    catch (DriverException $e) {
      $this->assertSame('Browser crashed', $e->getMessage());
    }

    $this->assertMatchesRegularExpression("/^--- Browser diagnostics ---\nPage: [^\n]*\nCrash \(1\):\n  renderer process crashed\n$/", $this->report());
  }

  public function testListsPendingRequests(): void {
    if (!in_array(getenv('CHROME_HOST') ?: 'localhost', ['localhost', '127.0.0.1'], TRUE)) {
      $this->markTestSkipped('Needs Chrome on this host to reach a local socket');
    }
    // Accepts connections into the backlog but never answers.
    $server = stream_socket_server('tcp://127.0.0.1:0');
    $address = stream_socket_get_name($server, FALSE);
    $this->visitHtml("<script>fetch('http://$address/never-answers?q=1');</script>");
    usleep(500000);

    $this->assertMatchesRegularExpression("/Pending requests \\(1\\):\n +\\d+\\.\\ds Fetch GET http:\\/\\/$address\\/never-answers\\?…\n/", $this->report());
    fclose($server);
  }

  public function testWritesFilesToOutputDir(): void {
    $this->outputDir = sys_get_temp_dir() . '/chrome-observer-' . getmypid();
    mkdir($this->outputDir);
    $this->visitHtml('<p id="marker">file output</p>');

    $report = $this->chromeObserver->report($this->outputDir, 'Some\\Test-testName with data set #1');
    $base = "$this->outputDir/Some_Test-testName_with_data_set_1";
    $this->assertStringContainsString("Files: $base.html, $base.png", $report);
    $this->assertStringContainsString('<p id="marker">file output</p>', file_get_contents("$base.html"));
    $this->assertStringStartsWith("\x89PNG", file_get_contents("$base.png"));
  }

  /**
   * Writes the page into a document of the site's origin; Chrome blocks loopback requests from data: URLs.
   */
  private function visitHtml(string $html): void {
    $this->visit(\Civi::paths()->getUrl('[civicrm.root]/css/civicrm.css', 'absolute'));
    $this->mink->getSession()->executeScript('document.open(); document.write(' . json_encode($html) . '); document.close();');
  }

  private function report(): string {
    return $this->chromeObserver->report(NULL, '');
  }

}
