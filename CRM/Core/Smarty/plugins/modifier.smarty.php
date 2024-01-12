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
 * Implement smarty:nodefaults for Smarty3.
 *
 * Adding |smarty:nodefaults to strings is the smarty
 * v2 way to indicates that a string should not be escaped.
 * It doesn't work with smarty 3 but it is a useful way to make strings
 * findable for this purpose as we figure out the best way for smarty 3.
 *
 * As a bridging mechanism this ensures the modifiers added in v2 do not error in v3.
 *
 * Eventually we want to run v3/v4 to escape by default but we are deferring that challenge
 * until we have achieved the first set of upgrading to v3.
 *
 * @param string $string
 *   The html to be tweaked.
 * @param string $modifier
 *   Either nodefaults or nothing
 *
 * @return string
 *   the new modified html string
 */
function smarty_modifier_smarty($string, string $modifier) {
  if ($modifier === 'nodefaults') {
    return $string;
  }
  // For clarity - we don't do this.
  throw new CRM_Core_Exception('unsupported modifier');
}
