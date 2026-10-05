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
 * Class CRM_Case_Tokens
 *
 * Generate "case.*" tokens.
 */
class CRM_Case_Tokens extends CRM_Core_EntityTokens {

  /**
   * Get the entity name for api v4 calls.
   *
   * @return string
   */
  protected function getApiEntityName(): string {
    return 'Case';
  }

  /**
   * Get entity fields that should not be exposed as tokens.
   *
   * @return string[]
   */
  protected function getSkippedFields(): array {
    return array_merge(parent::getSkippedFields(), [
      // A raw contact ID with no :label variant (it's an FK, not a
      // pseudoconstant) - not useful as a standalone merge token, same
      // rationale as why {case.contact_id} is downgraded to sysadmin
      // audience in the parent class.
      'case_manager_id',
      // These three all resolve relative to whoever is currently
      // logged in (CRM_Core_Session::getLoggedInContactID()), not a
      // fixed/portable attribute of the case itself - the value would
      // depend on who runs the mail merge, not the recipient, so
      // they're not meaningful as standalone tokens either.
      'my_case_role',
      'is_my_case',
      'is_my_managed_case',
    ]);
  }

}
