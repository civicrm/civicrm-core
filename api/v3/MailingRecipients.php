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
 * API for retrieving mailing recipients.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Returns array of MailingRecipients.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 *   API return Array of matching mailing jobs
 */
function civicrm_api3_mailing_recipients_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
