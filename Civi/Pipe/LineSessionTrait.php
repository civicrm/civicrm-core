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
 * Synchronous line-oriented communication session.
 *
 * @code
 * $session = new class {
 *   use LineSessionTrait;
 *   protected function onRequest(string $requestLine): ?string {
 *     return 'Thanks';
 *   }
 *   protected function onException(string $requestLine, \Throwable $t): ?string {
 *     return 'Oops';
 *   }
 * }
 * $session->setIO(STDIN, STDOUT)->run();
 * @endCode
 */
trait LineSessionTrait {

  /**
   * The onConnect() method is called when a new session is opened.
   *
   * @param string $negotiationFlags
   *   List of pipe initialization flags. See Civi::pipe() for description of flags.
   * @return string|null
   *   Header/welcome line, or NULL if none.
   * @see Civi::pipe
   */
  protected function onConnect(string $negotiationFlags): ?string {
    return NULL;
  }

  /**
   * The onRequest() method is called after receiving one line of text.
   *
   * @param string $requestLine
   *   The received line of text.
   * @return string|null
   *   The line to send back, or NULL if none.
   */
  abstract protected function onRequest(string $requestLine): ?string;

  /**
   * The onRequest() method is called after receiving one line of text.
   *
   * @param string $requestLine
   *   The received line of text - which led to the unhandled exception.
   * @param \Throwable $t
   *   The unhandled exception.
   * @return string|null
   *   The line to send back, or NULL if none.
   */
  abstract protected function onException(string $requestLine, \Throwable $t): ?string;

  /**
   * @var resource
   *   Ex: STDIN
   */
  protected $input;

  /**
   * @var resource
   *   Ex: STDOUT
   */
  protected $output;

  /**
   * Line-delimiter.
   *
   * @var string
   */
  protected $delimiter = "\n";

  /**
   * Maximum size of the buffer for reading lines.
   *
   * Clients may need to set this if they submit large requests.
   *
   * @var int
   */
  protected $bufferSize = 524288;

  /**
   * A value to display immediately before the response lines.
   *
   * Clients may set this is if they want to detect and skip buggy noise.
   *
   * @var string|null
   *   Ex: chr(1).chr(1)
   */
  protected $responsePrefix = NULL;

  /**
   * @param resource|null $input
   * @param resource|null $output
   */
  public function __construct($input = NULL, $output = NULL) {
    $this->input = $input;
    $this->output = $output;
  }

  /**
   * Run the main loop. Poll for commands on $input and write responses to $output.
   *
   * @param string $negotiationFlags
   *   List of pipe initialization flags. See Civi::pipe() for description of flags.
   */
  public function run(string $negotiationFlags = '') {
    $this->write($this->onConnect($negotiationFlags));

    while (FALSE !== ($line = stream_get_line($this->input, $this->bufferSize, $this->delimiter))) {
      $line = rtrim($line, $this->delimiter);
      if (empty($line)) {
        continue;
      }

      try {
        $response = $this->onRequest($line);
      }
      catch (\Throwable $t) {
        $response = $this->onException($line, $t);
      }
      $this->write($response);
    }
  }

  /**
   * @return int
   */
  public function getBufferSize(): int {
    return $this->bufferSize;
  }

  /**
   * @param int $bufferSize
   * @return $this
   */
  public function setBufferSize(int $bufferSize) {
    $this->bufferSize = $bufferSize;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getResponsePrefix(): ?string {
    return $this->responsePrefix;
  }

  /**
   * @param string|null $responsePrefix
   * @return $this
   */
  public function setResponsePrefix(?string $responsePrefix) {
    $this->responsePrefix = $responsePrefix;
    return $this;
  }

  /**
   * @param resource $input
   * @param resource $output
   * @return $this
   */
  public function setIO($input, $output) {
    $this->input = $input;
    $this->output = $output;
    return $this;
  }

  protected function write(?string $response): void {
    if ($response === NULL) {
      return;
    }
    if ($this->responsePrefix !== NULL) {
      fwrite($this->output, $this->responsePrefix);
    }
    fwrite($this->output, $response);
    fwrite($this->output, $this->delimiter);
  }

}
