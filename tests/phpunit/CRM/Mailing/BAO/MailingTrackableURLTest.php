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

use Civi\Api4\Mailing;

/**
 * Class CRM_Mailing_BAO_MailingTrackableURLTest
 */
class CRM_Mailing_BAO_MailingTrackableURLTest extends CiviUnitTestCase {

  /**
   * Cleanup after test.
   */
  public function tearDown(): void {
    $this->quickCleanup(['civicrm_mailing_trackable_url']);
    parent::tearDown();
  }

  /**
   * dev/core#5331  Test that two similar-ish URLs do not match
   */
  public function testSimilarTrackingURLs(): void {
    $mailingID = Mailing::create()->execute()->first()['id'];

    // Identical URLs should return the same tracking URL id
    // Fake qid is to help identify a failing test
    $url1 = CRM_Mailing_BAO_MailingTrackableURL::getTrackerURL('https://example.org/', $mailingID, 100);
    $url2 = CRM_Mailing_BAO_MailingTrackableURL::getTrackerURL('https://example.org/', $mailingID, 100);
    $this->assertEquals($url1, $url2);

    // Similar but not identical (one of these would normally cause a 404 on most servers)
    $url1 = CRM_Mailing_BAO_MailingTrackableURL::getTrackerURL('https://example.org/DOCS', $mailingID, 200);
    $url2 = CRM_Mailing_BAO_MailingTrackableURL::getTrackerURL('https://example.org/docs ', $mailingID, 200);
    $this->assertNotEquals($url1, $url2);

    // Also not identical (trailing space gets converted to %20 causing a 404)
    $url1 = CRM_Mailing_BAO_MailingTrackableURL::getTrackerURL('https://example.org/', $mailingID, 300);
    $url2 = CRM_Mailing_BAO_MailingTrackableURL::getTrackerURL('https://example.org/  ', $mailingID, 300);
    $this->assertNotEquals($url1, $url2);
  }

}
