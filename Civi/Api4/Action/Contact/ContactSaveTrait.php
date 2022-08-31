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
        $prefix = strtolower($entity) . '_' . $type . '.';
        $item = \CRM_Utils_Array::filterByPrefix($params, $prefix);
        // Not allowed to update by id or alter primary or billing flags
        unset($item['id'], $item['is_primary'], $item['is_billing']);
        if ($item) {
          $labelField = CoreUtil::getInfoItem($entity, 'label_field');
          // If NULL was given for the main field (e.g. `email`) then delete the record
          if ($labelField && array_key_exists($labelField, $item) && is_null($item[$labelField])) {
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
            foreach ($saved as $key => $value) {
              $key = $prefix . $key;
              $contact->$key = $value;
            }
          }
        }
      }
    }
  }

}
