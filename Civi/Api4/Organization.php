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
namespace Civi\Api4;

/**
 * Contacts of type Organization.
 *
 * @inheritDoc
 * @since 5.67
 * @package Civi\Api4
 */
class Organization extends Contact {

  protected static function getEntityTitle(bool $plural = FALSE): string {
    return $plural ? ts('Organizations') : ts('Organization');
  }

}
