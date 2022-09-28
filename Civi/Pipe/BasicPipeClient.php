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

namespace Civi\Pipe;

/**
 * This is a thin/trivial client implementation that connects to Civi::pipe()
 * and synchronously exchanges JSON messages.
 *
 * It is intended for use E2E testing.
 *
 * @code
 * $rpc = new BasicPipeClient('drush ev \'civicrm_initialize(); Civi::pipe();\'');
 * $rpc->call('login', ['contactId' => 202]);
 * $contacts = $rpc->call('api4', ['Contact', 'get']);
 * @endCode
 *
 * Failed method-calls will emit `JsonRpcMethodException`.
 * Errors in protocol handling will emit `RuntimeExcpetion`.
 */
class BasicPipeClient {

  /**
   * Maximum length of a requst
   *
   * @var int
   */
  private $bufferSize;

  /**
   * @var array
   */
  private $pipes;

  /**
   * @var resource|false|null
   */
  private $process;

  /**
   * @var array|null
   */
  private $welcome;

  /**
   * @param string|null $command
   *   The shell command to start the pipe. If given, auto-connect.
   *   If omitted, then you can call connect($command) later.
   *   Ex: `cv ev 'Civi::pipe();'`, `cv ev 'Civi::pipe("u");'`, `drush ev 'civicrm_initialize(); Civi::pipe("vt");'`
   * @param int $bufferSize
   */
  public function __construct(?string $command = NULL, int $bufferSize = 32767) {
    $this->bufferSize = $bufferSize;
    if ($command) {
      $this->connect($command);
    }
  }

  public function __destruct() {
    if ($this->process) {
      $this->close();
    }
  }

  /**
   * Start a worker process.
   *
   * @param string $command
   *   The shell command to start the pipe.
   *   Ex: `cv ev 'Civi::pipe();'`, `cv ev 'Civi::pipe("u");'`, `drush ev 'civicrm_initialize(); Civi::pipe("vt");'`
   * @return array
   *   Returns the header/welcome message for the connection.
   */
  public function connect(string $command): array {
    if ($this->process) {
      throw new \RuntimeException('Client error: Already connected');
    }

    $desc = [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'a']];
    $this->process = proc_open($command, $desc, $this->pipes);
    if (!$this->process) {
      throw new \RuntimeException("Client error: Failed to open process: $command");
    }
    $line = stream_get_line($this->pipes[1], $this->bufferSize, "\n");
    $this->welcome = json_decode($line, TRUE);
    if ($this->welcome === NULL || !isset($this->welcome['Civi::pipe'])) {
      throw new \RuntimeException('Protocol error: Received malformed welcome');
    }
    return $this->welcome['Civi::pipe'];
  }

  public function close(): void {
    proc_close($this->process);
    $this->pipes = NULL;
    $this->process = NULL;
  }

  /**
   * Call a method and return the result.
   *
   * @param string $method
   * @param array $params
   * @param string|int|null $id
   * @return array{result: array, error: array, jsonrpc: string, id: string|int|null}
   *   The JSON-RPC response recrd. Contains `result` or `error`.
   */
  public function call(string $method, array $params, $id = NULL): array {
    if (!$this->process) {
      throw new \RuntimeException('Client error: Connection was not been opened yet.');
    }

    $requestLine = json_encode(['jsonrpc' => '2.0', 'method' => $method, 'params' => $params, 'id' => $id]);
    fwrite($this->pipes[0], $requestLine . "\n");
    $responseLine = stream_get_line($this->pipes[1], $this->bufferSize, "\n");
    $decode = json_decode($responseLine, TRUE);
    if (!isset($decode['jsonrpc']) || $decode['jsonrpc'] !== '2.0') {
      throw new \RuntimeException('Protocol error: Response lacks JSON-RPC header.');
    }
    if (!array_key_exists('id', $decode) || $decode['id'] !== $id) {
      throw new \RuntimeException('Protocol error: Received response for wrong request.');
    }

    if (array_key_exists('error', $decode) && !array_key_exists('result', $decode)) {
      throw new JsonRpcMethodException($decode);
    }
    if (array_key_exists('result', $decode) && !array_key_exists('error', $decode)) {
      return $decode['result'];
    }
    throw new \RuntimeException("Protocol error: Response must include 'result' xor 'error'.");
  }

  /**
   * @param int $bufferSize
   * @return $this
   */
  public function setBufferSize(int $bufferSize) {
    $this->bufferSize = $bufferSize;
    if ($this->process) {
      $this->call('options', ['bufferSize' => $bufferSize]);
    }
    return $this;
  }

  /**
   * @return int
   */
  public function getBufferSize(): int {
    return $this->bufferSize;
  }

  /**
   * @return array|NULL
   */
  public function getWelcome(): ?array {
    return $this->welcome['Civi::pipe'] ?? NULL;
  }

}
