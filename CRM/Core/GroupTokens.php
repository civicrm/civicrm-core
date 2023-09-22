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
 * Class CRM_Core_GroupTokens
 *
 * Generate "member.*" tokens.
 */
class CRM_Core_GroupTokens extends CRM_Core_EntityTokens {

  /**
   * Get the entity name for api v4 calls.
   *
   * @return string
   */
  protected function getApiEntityName(): string {
    return 'Group';
  }

  /**
   * List out the fields that are exposed.
   *
   * @return string[]
   */
  protected function getExposedFields(): array {
    return [
      'id',
      'name',
      'title',
      'frontend_title',
      'frontend_description',
    ];
  }

}
