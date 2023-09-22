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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Utils_EchoLogger extends Psr\Log\AbstractLogger implements \Psr\Log\LoggerInterface {

  /**
   * Logs with an arbitrary level.
   *
   * @param mixed $level
   * @param string $message
   * @param array $context
   */
  public function log($level, $message, array $context = []): void {
    echo $message . "\n";
  }

}
