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

namespace Civi\Core;

/**
 * The LogManager will provide instances of "LoggerInterface".
 *
 * @package Civi\Core
 */
class LogManager {

  const DEFAULT_LOGGER = 'psr_log';

  private $channels = [];

  /**
   * Find or create a logger.
   *
   * This implementation will look for a service "log.{NAME}". If none is defined,
   * then it will fallback to the "psr_log" service.
   *
   * @param string $channel
   *   Symbolic name of the intended log.
   *   This should correlate to a service "log.{NAME}".
   *
   * @return \Psr\Log\LoggerInterface
   */
  public function getLog($channel = 'default') {
    if (!isset($this->channels[$channel])) {
      $c = \Civi::container();
      $svc = "log." . $channel;
      $this->channels[$channel] = $c->has($svc) ? $c->get($svc) : $c->get(self::DEFAULT_LOGGER);
    }
    return $this->channels[$channel];
  }

}
