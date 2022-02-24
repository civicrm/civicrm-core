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
 * Verify that the REST API bindings correctly parse and authenticate requests.
 *
 * @group e2e
 */
class E2E_Extern_AuthxRestTest extends E2E_Extern_BaseRestTest {

  public static function setUpBeforeClass(): void {
    parent::setUpBeforeClass();
    \Civi\Test::e2e()
      ->install(['authx'])
      ->callback(
        function() {
          \CRM_Utils_System::synchronizeUsers();
        },
        'synchronizeUsers'
      )
      ->apply();
  }

  protected function getRestUrl() {
    return CRM_Utils_System::url('civicrm/ajax/rest', NULL, TRUE, NULL, FALSE, TRUE);
  }

  protected function isOldQSupported(): bool {
    return FALSE;
  }

}
