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
 * Class CRM_Utils_Cache_InvalidArgumentException
 *
 * NOTE: PSR-16 specifies its exceptions using interfaces. For cache-consumers,
 * it's better to catch based on the interface. For cache-drivers, we need
 * a concrete class.
 */
class CRM_Utils_Cache_InvalidArgumentException extends \CRM_Core_Exception implements \Psr\SimpleCache\InvalidArgumentException {
}
