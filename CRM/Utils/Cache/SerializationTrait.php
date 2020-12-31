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

/**
 * Class CRM_Utils_Cache_SerializationTrait
 */
trait CRM_Utils_Cache_SerializationTrait {

  /**
   * @param mixed $data
   * @return string
   * @see \serialize()
   */
  protected function serialize($data) {
    return serialize($data);
  }

  /**
   * @param string $data
   * @return mixed
   * @see \unserialize()
   */
  protected function unserialize($data) {
    return unserialize($data);
  }

}
