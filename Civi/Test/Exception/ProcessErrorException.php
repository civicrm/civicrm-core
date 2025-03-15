<?php

namespace Civi\Test\Exception;

use Civi\Test\ProcessHelper;

class ProcessErrorException extends \RuntimeException {

  private ?string $cmd;

  private ?string $stdout;

  private ?string $stderr;

  private ?int $exit;

  public function __construct(?string $cmd, ?string $stdout, ?string $stderr, ?int $exit, $message = "", $code = 0, ?\Throwable $previous = NULL) {
    $this->cmd = $cmd;
    $this->stdout = $stdout;
    $this->stderr = $stderr;
    $this->exit = $exit;
    if (empty($message)) {
      $message = ProcessHelper::formatOutput($cmd, $stdout, $stdout, $exit);
    }
    parent::__construct($message, $code, $previous);
  }

}
