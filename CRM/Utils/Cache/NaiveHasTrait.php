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
 * The traditional CRM_Utils_Cache_Interface did not support has().
 * To get drop-in compliance with PSR-16, we use a naive adapter.
 *
 * There may be opportunities to replace/optimize in specific drivers.
 */
trait CRM_Utils_Cache_NaiveHasTrait {

  public function has($key) {
    $nack = CRM_Utils_Cache::nack();
    $value = $this->get($key, $nack);
    return ($value !== $nack);
  }

}
