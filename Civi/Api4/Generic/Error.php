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

namespace Civi\Api4\Generic;

use Psr\Log\LogLevel;

/**
 * Class representing an APIv4 error.
 */
class Error implements \JsonSerializable {

  /**
   * The error severity (use Psr\Log\LogLevel strings)
   *
   * @var string
   */
  private string $level;

  /**
   * Optional title for the error
   *
   * @var string
   */
  private string $title;

  /**
   * @var string
   */
  private string $message;

  /**
   * Optional (machine-readable) code for the error
   *
   * @var int|string
   */
  private int|string $code;

  /**
   * @var string
   */
  private string $id;

  /**
   * Error constructor.
   */
  public function __construct(string $message, int|string $code = 0, string $title = '', string $level = LogLevel::ERROR) {
    $this->message = $message;
    $this->code = $code;
    $this->title = $title;
    $this->level = $level;
    $this->id = \CRM_Core_Error::createErrorId();
  }

  /**
   * @return string
   */
  public function getLevel(): string {
    return $this->level;
  }

  /**
   * @param string $level
   * @return $this
   */
  public function setLevel(string $level): Error {
    $this->level = $level;
    return $this;
  }

  /**
   * @return string
   */
  public function getTitle(): string {
    return $this->title;
  }

  /**
   * @param string $title
   * @return $this
   */
  public function setTitle(string $title): Error {
    $this->title = $title;
    return $this;
  }

  /**
   * @return string
   */
  public function getMessage(): string {
    return $this->message;
  }

  /**
   * @param string $message
   * @return $this
   */
  public function setMessage(string $message): Error {
    $this->message = $message;
    return $this;
  }

  /**
   * @return int|string
   */
  public function getCode(): int|string {
    return $this->code;
  }

  /**
   * @param int|string $code
   * @return $this
   */
  public function setCode(int|string $code): Error {
    $this->code = $code;
    return $this;
  }

  /**
   * @return string
   */
  public function getId(): string {
    return $this->id;
  }

  /**
   * @return array
   */
  public function jsonSerialize(): array {
    return [
      'level' => $this->level,
      'message' => $this->message,
      'title' => $this->title,
      'code' => $this->code,
      'id' => $this->id,
    ];
  }

}
