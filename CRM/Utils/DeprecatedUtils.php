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

/*
 * These functions have been deprecated out of API v3 Utils folder as they are not part of the
 * API. Calling API functions directly is not supported & these functions are not called by any
 * part of the API so are not really part of the api
 *
 */

require_once 'api/v3/utils.php';

/**
 *
 * @param array $params
 *
 * @return array
 *   <type>
 */
function _civicrm_api3_deprecated_duplicate_formatted_contact($params) {
  $id = $params['id'] ?? NULL;
  $externalId = $params['external_identifier'] ?? NULL;
  if ($id || $externalId) {
    $contact = new CRM_Contact_DAO_Contact();

    $contact->id = $id;
    $contact->external_identifier = $externalId;

    if ($contact->find(TRUE)) {
      if ($params['contact_type'] != $contact->contact_type) {
        return civicrm_api3_create_error("Mismatched contact IDs OR Mismatched contact Types");
      }

      $error = CRM_Core_Error::createError("Found matching contacts: $contact->id",
        CRM_Core_Error::DUPLICATE_CONTACT,
        'Fatal', $contact->id
      );
      return civicrm_api3_create_error($error->pop());
    }
  }
  else {
    $ids = CRM_Contact_BAO_Contact::getDuplicateContacts($params, $params['contact_type'], 'Unsupervised');

    if (!empty($ids)) {
      $ids = implode(',', $ids);
      $error = CRM_Core_Error::createError("Found matching contacts: $ids",
        CRM_Core_Error::DUPLICATE_CONTACT,
        'Fatal', $ids
      );
      return civicrm_api3_create_error($error->pop());
    }
  }
  return civicrm_api3_create_success(TRUE);
}
