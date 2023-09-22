<?php

class CRM_Export_Utils {

  /**
   * This transforms the lists of fields for each contact type & component
   * into a single unified list suitable for select2.
   *
   * The return values of CRM_Core_BAO_Mapping::getBasicFields contain a separate field list
   * for every contact type and sub-type. This is extremely redundant as 90%+ of the fields
   * in each list are the same. To avoid sending bloated data to the client-side, we turn
   * it into a single list where fields not shared by every contact type get a contact_type
   * attribute so they can be filtered appropriately by the selector.
   *
   * We also sort fields into optgroup categories, and add component fields appropriate to this export.
   *
   * @param $exportMode
   * @return array
   * @throws CRM_Core_Exception
   */
  public static function getExportFields($exportMode) {
    $fieldGroups = CRM_Core_BAO_Mapping::getBasicFields('Export');

    $categories = [
      'contact' => ['text' => ts('Contact Fields'), 'is_contact' => TRUE],
      'address' => ['text' => ts('Address Fields'), 'is_contact' => TRUE],
      'communication' => ['text' => ts('Communication Fields'), 'is_contact' => TRUE],
    ];
    $optionMap = [
      'civicrm_website' => 'website_type_id',
      'civicrm_phone' => 'phone_type_id',
      'civicrm_im' => 'im_provider_id',
    ];
    // Whitelist of field properties we actually care about; others will be discarded
    $fieldProps = ['id', 'text', 'has_location', 'option_list', 'relationship_type_id', 'related_contact_type'];
    $relTypes = civicrm_api3('RelationshipType', 'get', ['options' => ['limit' => 0]])['values'];

    // Add component fields
    $compFields = [];
    $compLabels = CRM_Core_BAO_Mapping::addComponentFields($compFields, 'Export', $exportMode);
    foreach ($compLabels as $comp => $label) {
      $categories[$comp] = ['text' => $label];
      foreach ($compFields[$comp] as $key => $field) {
        $field['text'] = $field['title'];
        $field['id'] = $key;
        $categories[$comp]['children'][] = array_intersect_key($field, array_flip($fieldProps));
      }
    }

    // Unset groups, tags, notes for component export
    if ($exportMode != CRM_Export_Form_Select::CONTACT_EXPORT) {
      foreach (array_keys($fieldGroups) as $contactType) {
        CRM_Utils_Array::remove($fieldGroups[$contactType], 'groups', 'tags', 'notes');
      }
    }

    // Now combine all those redundant lists of fields into a single list with categories
    foreach ($fieldGroups as $contactType => $fields) {
      // 'related' was like a poor-mans optgroup.
      unset($fields['related']);
      foreach ($fields as $key => $field) {
        $group = 'contact';
        $field['text'] = $field['title'];
        $field['id'] = $key;
        $field['has_location'] = !empty($field['hasLocationType']);
        if (isset($field['table_name']) && isset($optionMap[$field['table_name']])) {
          $field['option_list'] = $optionMap[$field['table_name']];
          $group = 'communication';
        }
        elseif (!empty($field['has_location'])) {
          $group = 'address';
        }
        if ($key == 'email') {
          $group = 'communication';
        }
        if (!empty($field['custom_group_id'])) {
          $group = $field['custom_group_id'];
          $categories[$group]['text'] = $field['groupTitle'];
          $categories[$group]['is_contact'] = TRUE;
        }
        if (!empty($field['related'])) {
          $group = 'related';
          $categories[$group]['text'] = ts('Related Contact Info');
          list($type, , $dir) = explode('_', $key);
          $field['related_contact_type'] = $relTypes[$type]["contact_sub_type_$dir"] ?? $relTypes[$type]["contact_type_$dir"] ?? '*';
          // Skip relationship types targeting disabled contacts
          if ($field['related_contact_type'] != '*' && !isset($fieldGroups[$field['related_contact_type']])) {
            continue;
          }
        }
        if (empty($categories[$group]['children'][$key])) {
          // Discard unwanted field props to save space
          $categories[$group]['children'][$key] = array_intersect_key($field, array_flip($fieldProps));
        }
        // Set contact_type, which gets added to on every iteration
        $categories[$group]['children'][$key]['contact_type'][] = $contactType;
        // If a field applies to every contact type, remove the contact_type flag as it's redundant
        if (count($fieldGroups) == count($categories[$group]['children'][$key]['contact_type'])) {
          unset($categories[$group]['children'][$key]['contact_type']);
        }
      }
    }
    // We needed meaningful keys while organizing fields but if we send them client-side they'll just be in the way
    foreach ($categories as &$category) {
      $category['children'] = array_values($category['children']);
    }
    return array_values($categories);
  }

}
