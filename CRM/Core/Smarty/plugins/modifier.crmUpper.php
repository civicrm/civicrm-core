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
 * Upper case a string but use the multibyte strtoupper function to better handle accents / umlaut
 *
 * @param string $string the string to upper case
 *
 * @return string
 */
function smarty_modifier_crmUpper($string): string {
  return mb_strtoupper($string);
}
