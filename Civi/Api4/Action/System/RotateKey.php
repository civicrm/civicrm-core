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

namespace Civi\Api4\Action\System;

use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;

/**
 * Rotate the keys used for encrypted database content.
 *
 * Crypto keys are loaded from the CryptoRegistry based on tag name. Each tag will
 * have one preferred key and 0+ legacy keys. They rekey operation finds any
 * old content (based on legacy keys) and rewrites it (using the preferred key).
 *
 * @method string getTag()
 * @method $this setTag(string $tag)
 */
class RotateKey extends AbstractAction {

  /**
   * Tag name (e.g. "CRED")
   *
   * @var string
   */
  protected $tag;

  /**
   * @param \Civi\Api4\Generic\Result $result
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\Crypto\Exception\CryptoException
   */
  public function _run(Result $result) {
    if (empty($this->tag)) {
      throw new \CRM_Core_Exception("Missing required argument: tag");
    }

    // Track log of changes in memory.
    $logger = new class() extends \Psr\Log\AbstractLogger {

      /**
       * @var array
       */
      public $log = [];

      /**
       * Logs with an arbitrary level.
       *
       * @param mixed $level
       * @param string $message
       * @param array $context
       */
      public function log($level, $message, array $context = []): void {
        $evalVar = function($m) use ($context) {
          return $context[$m[1]] ?? '';
        };

        $this->log[] = [
          'level' => $level,
          'message' => preg_replace_callback('/\{([a-zA-Z0-9\.]+)\}/', $evalVar, $message),
        ];
      }

    };

    \CRM_Utils_Hook::cryptoRotateKey($this->tag, $logger);

    $result->exchangeArray($logger->log);
  }

}
