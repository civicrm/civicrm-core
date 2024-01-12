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
class E2E_Extern_LegacyRestTest extends E2E_Extern_BaseRestTest {

  protected $LEGACY_EXTERN_SUPPORTED = ['Drupal', 'Backdrop', 'Joomla', 'WordPress'];

  protected function setUp(): void {
    if (!in_array(CIVICRM_UF, $this->LEGACY_EXTERN_SUPPORTED)) {
      $this->markTestSkipped('Legacy extern/rest.php is not supported by ' . CIVICRM_UF);
    }
    parent::setUp();
  }

  protected function getRestUrl() {
    return CRM_Core_Resources::singleton()
      ->getUrl('civicrm', 'extern/rest.php');
  }

  protected function isOldQSupported(): bool {
    return TRUE;
  }

}
