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
 * Wrapper around CRM_Utils_Color::getContrast
 *
 * @param string $color
 *
 * @return string
 */
function smarty_modifier_colorContrast($color) {
  return CRM_Utils_Color::getContrast($color);
}
