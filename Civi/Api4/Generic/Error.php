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
   * @var string
   */
  private $level;

  /**
   * @var string
   */
  private $message;

  /**
   * @var int|string
   */
  private $code;

  /**
   * @var string
   */
  private $id;

  /**
   * Error constructor.
   */
  public function __construct(string $message, string $level = LogLevel::ERROR, $code = 0) {
    $this->level = $level;
    $this->message = $message;
    $this->code = $code;
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
  public function setLevel(string $level) {
    $this->level = $level;
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
  public function setMessage(string $message) {
    $this->message = $message;
    return $this;
  }

  /**
   * @return int|string
   */
  public function getCode() {
    return $this->code;
  }

  /**
   * @param int|string $code
   * @return $this
   */
  public function setCode($code) {
    $this->code = $code;
    return $this;
  }

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
      'code' => $this->code,
      'id' => $this->id,
    ];
  }

}
