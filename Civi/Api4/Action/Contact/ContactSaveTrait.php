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

namespace Civi\Api4\Action\Contact;

use Civi\Api4\Utils\CoreUtil;

/**
 * Code shared by Contact create/update/save actions
 */
trait ContactSaveTrait {

  /**
   * @param array $items
   * @return array
   */
  protected function write(array $items) {
    foreach ($items as &$contact) {
      // For some reason the contact BAO requires this for updates
      if (!empty($contact['id'])) {
        $contact['contact_id'] = $contact['id'];
      }
      elseif (empty($contact['contact_type'])) {
        // Guess which type of contact is being created
        if (!empty($contact['organization_name'])) {
          $contact['contact_type'] = 'Organization';
        }
        elseif (!empty($contact['household_name'])) {
          $contact['contact_type'] = 'Household';
        }
        else {
          $contact['contact_type'] = 'Individual';
        }
      }
    }
    $saved = parent::write($items);
    foreach ($items as $index => $item) {
      self::saveLocations($item, $saved[$index]);
    }
    return $saved;
  }

  /**
   * @param array $params
   * @param \CRM_Contact_DAO_Contact $contact
   */
  protected function saveLocations(array $params, $contact) {
    foreach (['Address', 'Email', 'Phone', 'IM'] as $entity) {
      foreach (['primary', 'billing'] as $type) {
        $joinName = strtolower($entity) . '_' . $type;
        $joinPrefix = $joinName . '.';
        $item = \CRM_Utils_Array::filterByPrefix($params, $joinPrefix);
        // For updating by id, setting e.g. 'primary_email' is equivalent to 'primary_email.id'
        if (array_key_exists($joinName, $params)) {
          $item['id'] ??= $params[$joinName];
        }
        // Not allowed to alter primary or billing flags
        unset($item['is_primary'], $item['is_billing']);
        if ($item) {
          $labelField = CoreUtil::getInfoItem($entity, 'label_field');
          $labelParamExists = $labelField && array_key_exists($labelField, $item);
          $idParamExists = array_key_exists('id', $item);
          // If NULL was given for the main field (e.g. `email`) or the ID, then delete the record
          if (($labelParamExists && is_null($item[$labelField])) || (!$labelParamExists && $idParamExists && is_null($item['id']))) {
            civicrm_api4($entity, 'delete', [
              'checkPermissions' => FALSE,
              'where' => [
                ['contact_id', '=', $contact->id],
                ["is_$type", '=', TRUE],
              ],
            ]);
          }
          else {
            $item['contact_id'] = $contact->id;
            $item["is_$type"] = TRUE;
            $saved = civicrm_api4($entity, 'save', [
              'checkPermissions' => FALSE,
              'records' => [$item],
              'match' => ['contact_id', "is_$type"],
            ])->first();
            // Update object values for sake of api output
            $contact->$joinName = $saved['id'];
            foreach ($saved as $key => $value) {
              $key = $joinPrefix . $key;
              $contact->$key = $value;
            }
          }
        }
      }
    }
  }

  /**
   * Get fields the logged in user is not permitted to act on.
   *
   * Override parent to exclude api_key as this is dealt with elsewhere.
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public function getUnpermittedFields(): array {
    $fields = parent::getUnpermittedFields();
    if (\CRM_Core_Session::getLoggedInContactID()) {
      // This is handled in the BAO to allow for edit own api key.
      unset($fields['api_key']);
    }
    return $fields;
  }

}
