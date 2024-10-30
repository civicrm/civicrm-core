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
 * This api is a facade for the Contact entity.
 * In most ways it acts exactly like the Contact entity, plus it injects [contact_type => Organization]
 * into get, create, and batch actions (however, when updating or deleting a single Contact by id,
 * this will transparently pass-through to the Contact entity, so don't rely on this facade to enforce
 * contact type for single-record-by-id write operations).
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
