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
 * Convert the data to a JSON string
 *
 * Example usage: {$myArray|@json}
 *
 * @param mixed $data
 *
 * @return string
 *   JSON
 */
function smarty_modifier_json($data) {
  return json_encode($data);
}
