<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

namespace Civi\Test;

use WebSocket\Client;

/**
 * Watches a Chrome tab over its own DevTools session, independent of the Mink driver.
 *
 * Events queue up unread in the browser during the test. report() collects them
 * and probes the tab, which still works when the driver's connection is stuck.
 * A CiviCRM page test queues about 1 MB (read in 0.05s); DevTools buffers up to 256 MB.
 */
class ChromeObserver {

  private const LIST_LIMIT = 20;

  private string $pageUrl;

  private ?Client $ws;

  private int $lastId = 0;

  private ?string $openDialog = NULL;

  /**
   * Script URLs by scriptId.
   *
   * @var string[]
   */
  private array $scripts = [];

  /**
   * Unfinished requests by requestId, each as [type, method, URL, wallTime].
   *
   * @var array[]
   */
  private array $pending = [];

  /**
   * The browser's own favicon requests by requestId; a missing favicon says nothing about the page.
   *
   * @var bool[]
   */
  private array $ignored = [];

  /**
   * Report lines by section, the latest LIST_LIMIT each.
   *
   * @var array[]
   */
  private array $findings = [];

  /**
   * Number of lines by section, including dropped ones.
   *
   * @var int[]
   */
  private array $counts = [];

  public function __construct(string $chromeUrl, string $targetId) {
    $this->pageUrl = preg_replace('/^http/', 'ws', $chromeUrl) . '/devtools/page/' . $targetId;
    $this->ws = new Client($this->pageUrl, ['timeout' => 5]);
    foreach (['Network.enable', 'Runtime.enable', 'Log.enable', 'Page.enable', 'Inspector.enable', 'Debugger.enable'] as $method) {
      $this->call($method);
    }
    // Lets Debugger.pause interrupt a busy renderer without `debugger;` statements stopping the page.
    $this->call('Debugger.setBreakpointsActive', ['active' => FALSE]);
  }

  /**
   * Describes the tab's current state and what went wrong in it since the observer started.
   *
   * @param string|null $outputDir
   *   Where to write a screenshot and the page HTML, if anywhere.
   * @param string $fileBase
   *   File name prefix for those files.
   */
  public function report(?string $outputDir, string $fileBase): string {
    $lines = ['--- Browser diagnostics ---'];
    try {
      $target = $this->call('Target.getTargetInfo')['targetInfo'];
      $lines[] = sprintf('Page: %s "%s"', $this->compact($target['url']), $this->shortText($target['title']));
    }
    catch (\Throwable $e) {
      $lines[] = 'Observer connection lost, events since then are missing: ' . $e->getMessage();
    }

    $responsive = FALSE;
    if ($this->openDialog) {
      $lines[] = "Renderer: blocked by an open $this->openDialog dialog";
    }
    elseif (!isset($this->findings['Crash'])) {
      $responsive = $this->probeRenderer($lines);
      if (!$responsive) {
        $this->pauseRenderer($lines);
      }
    }

    foreach ($this->findings as $section => $entries) {
      $dropped = $this->counts[$section] - count($entries);
      $lines[] = "$section ({$this->counts[$section]}" . ($dropped ? ", earliest $dropped not shown" : '') . '):';
      foreach ($entries as $entry) {
        $lines[] = '  ' . $entry;
      }
    }
    if ($this->pending) {
      $lines[] = 'Pending requests (' . count($this->pending) . '):';
      foreach (array_slice($this->pending, -self::LIST_LIMIT) as [$type, $method, $url, $wallTime]) {
        $lines[] = sprintf('  %5.1fs %s %s %s', microtime(TRUE) - $wallTime, $type, $method, $this->compact($url));
      }
    }

    if ($outputDir && $responsive) {
      $this->writeFiles($outputDir, $fileBase, $lines);
    }
    return implode("\n", $lines) . "\n";
  }

  /**
   * Drops the connection without a close handshake, which would read the whole unread queue.
   */
  public function close(): void {
    if ($this->ws) {
      $this->ws->disconnect();
      $this->ws = NULL;
    }
  }

  /**
   * Asks the renderer over a separate connection, so a timeout leaves the observer session usable.
   */
  private function probeRenderer(array &$lines): bool {
    $probe = new Client($this->pageUrl, ['timeout' => 3]);
    try {
      $probe->send(json_encode(['id' => 1, 'method' => 'Runtime.evaluate', 'params' => ['expression' => 'document.readyState', 'returnByValue' => TRUE]]));
      do {
        $message = json_decode($this->receiveFrom($probe), TRUE);
      } while (($message['id'] ?? NULL) !== 1);
      $lines[] = 'Renderer: responds, readyState=' . ($message['result']['result']['value'] ?? json_encode($message));
      return TRUE;
    }
    catch (\Throwable $e) {
      $lines[] = 'Renderer: does not respond within 3s (' . $e->getMessage() . ')';
      return FALSE;
    }
    finally {
      $probe->disconnect();
    }
  }

  private function pauseRenderer(array &$lines): void {
    try {
      // The paused event may arrive before or after the command's reply.
      $id = ++$this->lastId;
      $this->send(['id' => $id, 'method' => 'Debugger.pause']);
      $message = $this->receiveUntil(fn($m) => ($m['method'] ?? NULL) === 'Debugger.paused' || (($m['id'] ?? NULL) === $id && isset($m['error'])));
      if (isset($message['error'])) {
        throw new \RuntimeException($message['error']['message']);
      }
      $paused = $message['params'];
      $lines[] = 'JavaScript running in the renderer:';
      foreach (array_slice($paused['callFrames'], 0, self::LIST_LIMIT) as $frame) {
        $location = $frame['location'];
        $lines[] = '  ' . ($frame['functionName'] ?: '(anonymous)') . ' @ ' . $this->scriptLocation($location['scriptId'], $location['lineNumber'], $location['columnNumber'] ?? 0);
      }
      $this->call('Debugger.resume');
    }
    catch (\Throwable $e) {
      $lines[] = 'JavaScript stack unavailable, Debugger.pause failed: ' . $e->getMessage();
    }
  }

  private function writeFiles(string $outputDir, string $fileBase, array &$lines): void {
    $base = rtrim($outputDir, '/') . '/' . preg_replace('/[^\w.-]+/', '_', $fileBase);
    try {
      $html = $this->call('Runtime.evaluate', ['expression' => 'document.documentElement.outerHTML', 'returnByValue' => TRUE]);
      $png = $this->call('Page.captureScreenshot', ['format' => 'png']);
      if (!isset($html['result']['value']) || !file_put_contents("$base.html", $html['result']['value']) || !file_put_contents("$base.png", base64_decode($png['data']))) {
        throw new \RuntimeException("cannot write $base.html/.png");
      }
      $lines[] = "Files: $base.html, $base.png";
    }
    catch (\Throwable $e) {
      $lines[] = 'Writing files failed: ' . $e->getMessage();
    }
  }

  /**
   * Sends a command and records all events that arrive before its reply.
   */
  private function call(string $method, array $params = []): array {
    $id = ++$this->lastId;
    $this->send(['id' => $id, 'method' => $method, 'params' => (object) $params]);
    $message = $this->receiveUntil(fn($m) => ($m['id'] ?? NULL) === $id);
    if (isset($message['error'])) {
      throw new \RuntimeException("$method: {$message['error']['message']}");
    }
    return $message['result'];
  }

  private function send(array $message): void {
    if (!$this->ws) {
      throw new \RuntimeException('observer connection closed');
    }
    try {
      $this->ws->send(json_encode($message));
    }
    catch (\Throwable $e) {
      $this->ws = NULL;
      throw $e;
    }
  }

  /**
   * The client reconnects silently after a timeout, losing the session; drop it instead.
   */
  private function receiveUntil(callable $done): array {
    try {
      do {
        $message = json_decode($this->receiveFrom($this->ws), TRUE);
        if (isset($message['method'])) {
          $this->record($message['method'], $message['params'] ?? []);
        }
      } while (!$done($message));
      return $message;
    }
    catch (\Throwable $e) {
      $this->ws = NULL;
      throw $e;
    }
  }

  /**
   * The client answers a close frame by disconnecting and returns NULL; the next receive would reconnect.
   */
  private function receiveFrom(Client $client): string {
    $message = $client->receive();
    if ($message === NULL) {
      throw new \RuntimeException('Chrome closed the DevTools connection');
    }
    return $message;
  }

  private function record(string $event, array $p): void {
    if (isset($p['requestId'], $this->ignored[$p['requestId']])) {
      return;
    }
    switch ($event) {
      case 'Debugger.scriptParsed':
        $this->scripts[$p['scriptId']] = $p['url'];
        break;

      case 'Network.requestWillBeSent':
        if (($p['type'] ?? '') === 'Other' && str_ends_with((string) parse_url($p['request']['url'], PHP_URL_PATH), '/favicon.ico')) {
          $this->ignored[$p['requestId']] = TRUE;
          break;
        }
        $this->pending[$p['requestId']] = [$p['type'] ?? '?', $p['request']['method'], $p['request']['url'], $p['wallTime']];
        break;

      case 'Network.loadingFinished':
        unset($this->pending[$p['requestId']]);
        break;

      case 'Network.loadingFailed':
        $request = $this->pending[$p['requestId']] ?? [$p['type'], '?', '?'];
        unset($this->pending[$p['requestId']]);
        if (empty($p['canceled'])) {
          $this->add('Failed requests', sprintf('%s %s %s: %s%s', $request[0], $request[1], $this->compact($request[2]),
            $p['errorText'], isset($p['blockedReason']) ? " (blocked: {$p['blockedReason']})" : ''));
        }
        break;

      case 'Network.responseReceived':
        if ($p['response']['status'] >= 400) {
          $this->add('HTTP errors', sprintf('%d %s %s', $p['response']['status'], $p['type'], $this->compact($p['response']['url'])));
        }
        break;

      case 'Runtime.exceptionThrown':
        $details = $p['exceptionDetails'];
        $this->add('JavaScript exceptions', $this->shortText($details['exception']['description'] ?? $details['text']));
        break;

      case 'Runtime.consoleAPICalled':
        if (in_array($p['type'], ['error', 'warning', 'assert'], TRUE)) {
          $text = implode(' ', array_map(fn($arg) => $arg['value'] ?? $arg['description'] ?? $arg['type'], $p['args']));
          $frame = $p['stackTrace']['callFrames'][0] ?? NULL;
          $this->add('Console', "{$p['type']}: " . $this->shortText($text) . ($frame ? ' @ ' . $this->scriptLocation($frame['scriptId'], $frame['lineNumber'], $frame['columnNumber']) : ''));
        }
        break;

      case 'Log.entryAdded':
        // Network log entries repeat what the Network events already report.
        if ($p['entry']['source'] !== 'network' && in_array($p['entry']['level'], ['error', 'warning'], TRUE)) {
          $this->add('Browser log', "{$p['entry']['source']}/{$p['entry']['level']}: " . $this->shortText($p['entry']['text']));
        }
        break;

      case 'Page.javascriptDialogOpening':
        $this->add('Dialogs', "{$p['type']}: " . $this->shortText($p['message']));
        $this->openDialog = $p['type'];
        break;

      case 'Page.javascriptDialogClosed':
        $this->openDialog = NULL;
        break;

      case 'Inspector.targetCrashed':
        $this->add('Crash', 'renderer process crashed');
        break;

      case 'Inspector.detached':
        $this->add('Crash', "DevTools detached: {$p['reason']}");
        break;
    }
  }

  private function scriptLocation(string $scriptId, int $line, int $column): string {
    $url = $this->scripts[$scriptId] ?? '';
    return ($url === '' ? "script $scriptId" : $this->compact($url)) . ':' . ($line + 1) . ':' . ($column + 1);
  }

  private function add(string $section, string $line): void {
    $this->counts[$section] = ($this->counts[$section] ?? 0) + 1;
    $this->findings[$section][] = $line;
    if (count($this->findings[$section]) > self::LIST_LIMIT) {
      array_shift($this->findings[$section]);
    }
  }

  /**
   * Drops query strings, which may carry credentials (e.g. authx login tokens), and truncates data: URLs.
   */
  private function compact(string $text): string {
    $text = preg_replace('/\b(https?:\/\/[^\s?#\'"]*)\?[^\s#\'"]*/', '$1?…', $text);
    return preg_replace('/\b(data:[^\s,\'"]*,[^\s\'"]{0,24})[^\s\'"]+/', '$1…', $text);
  }

  private function shortText(string $text): string {
    $lines = explode("\n", $text);
    return mb_strimwidth($this->compact(implode(' | ', array_map('trim', array_slice($lines, 0, 4)))), 0, 400, '…');
  }

}
