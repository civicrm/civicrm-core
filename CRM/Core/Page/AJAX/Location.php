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
 *
 */

/**
 * This class contains all the function that are called using AJAX
 */
class CRM_Core_Page_AJAX_Location {

  /**
   * FIXME: we should make this method like getLocBlock() OR use the same method and
   * remove this one.
   *
   * obtain the location of given contact-id.
   * This method is used by on-behalf-of form to dynamically generate poulate the
   * location field values for selected permissioned contact.
   *
   * @throws \CRM_Core_Exception
   */
  public static function getPermissionedLocation() {
    $cid = CRM_Utils_Request::retrieve('cid', 'Integer', CRM_Core_DAO::$_nullObject, TRUE);
    $ufId = CRM_Utils_Request::retrieve('ufId', 'Integer', CRM_Core_DAO::$_nullObject, TRUE);

    // Verify user id
    $user = CRM_Utils_Request::retrieve('uid', 'Integer', CRM_Core_DAO::$_nullObject, FALSE, CRM_Core_Session::singleton()
      ->get('userID'));
    if (empty($user) || (CRM_Utils_Request::retrieve('cs', 'String', $form, FALSE) && !CRM_Contact_BAO_Contact_Permission::validateChecksumContact($user, CRM_Core_DAO::$_nullObject, FALSE))
    ) {
      CRM_Utils_System::civiExit();
    }

    // Verify user permission on related contact
    $organizations = CRM_Contact_BAO_Relationship::getPermissionedContacts($user, NULL, NULL, 'Organization');
    if (!isset($organizations[$cid])) {
      CRM_Utils_System::civiExit();
    }

    $values = [];
    $entityBlock = ['contact_id' => $cid];
    $location = CRM_Core_BAO_Location::getValues($entityBlock);

    $addressSequence = array_flip(CRM_Utils_Address::sequence(\Civi::settings()->get('address_format')));

    $profileFields = CRM_Core_BAO_UFGroup::getFields($ufId, FALSE, CRM_Core_Action::VIEW, NULL, NULL, FALSE,
      NULL, FALSE, NULL, CRM_Core_Permission::CREATE, NULL
    );
    $website = CRM_Core_BAO_Website::getValues($entityBlock, $values);

    foreach ($location as $fld => $values) {
      if (is_array($values) && !empty($values)) {
        $locType = $values[1]['location_type_id'];
        if ($fld == 'email') {
          $elements["onbehalf_{$fld}-{$locType}"] = [
            'type' => 'Text',
            'value' => $location[$fld][1][$fld],
          ];
          unset($profileFields["{$fld}-{$locType}"]);
        }
        elseif ($fld == 'phone') {
          $phoneTypeId = $values[1]['phone_type_id'];
          $elements["onbehalf_{$fld}-{$locType}-{$phoneTypeId}"] = [
            'type' => 'Text',
            'value' => $location[$fld][1][$fld],
          ];
          unset($profileFields["{$fld}-{$locType}-{$phoneTypeId}"]);
        }
        elseif ($fld == 'im') {
          $providerId = $values[1]['provider_id'];
          $elements["onbehalf_{$fld}-{$locType}"] = [
            'type' => 'Text',
            'value' => $location[$fld][1][$fld],
          ];
          $elements["onbehalf_{$fld}-{$locType}provider_id"] = [
            'type' => 'Select',
            'value' => $location[$fld][1]['provider_id'],
          ];
          unset($profileFields["{$fld}-{$locType}-{$providerId}"]);
        }
      }
    }

    if (!empty($website)) {
      foreach ($website as $key => $val) {
        $websiteTypeId = $values[1]['website_type_id'];
        $elements["onbehalf_url-1"] = [
          'type' => 'Text',
          'value' => $website[1]['url'],
        ];
        $elements["onbehalf_url-1-website_type_id"] = [
          'type' => 'Select',
          'value' => $website[1]['website_type_id'],
        ];
        unset($profileFields["url-1"]);
      }
    }

    $locTypeId = isset($location['address'][1]) ? $location['address'][1]['location_type_id'] : NULL;
    $addressFields = [
      'street_address',
      'supplemental_address_1',
      'supplemental_address_2',
      'supplemental_address_3',
      'city',
      'postal_code',
      'county',
      'state_province',
      'country',
    ];

    foreach ($addressFields as $field) {
      if (array_key_exists($field, $addressSequence)) {
        $addField = $field;
        $type = 'Text';
        if (in_array($field, ['state_province', 'country', 'county'])) {
          $addField = "{$field}_id";
          $type = 'Select';
        }
        $elements["onbehalf_{$field}-{$locTypeId}"] = [
          'type' => $type,
          'value' => isset($location['address'][1]) ? CRM_Utils_Array::value($addField,
            $location['address'][1]) : NULL,
        ];
        unset($profileFields["{$field}-{$locTypeId}"]);
      }
    }

    //set custom field defaults
    $defaults = [];
    CRM_Core_BAO_UFGroup::setProfileDefaults($cid, $profileFields, $defaults, TRUE, NULL, NULL, TRUE);

    if (!empty($defaults)) {
      foreach ($profileFields as $key => $val) {
        if (array_key_exists($key, $defaults)) {
          $htmlType = $val['html_type'] ?? NULL;
          if ($htmlType == 'Radio') {
            $elements["onbehalf_{$key}"]['type'] = $htmlType;
            $elements["onbehalf_{$key}"]['value'] = $defaults[$key];
          }
          elseif ($htmlType == 'CheckBox') {
            $elements["onbehalf_{$key}"]['type'] = $htmlType;
            foreach ($defaults[$key] as $k => $v) {
              $elements["onbehalf_{$key}"]['value'][$k] = $v;
            }
          }
          elseif (strstr($htmlType, 'Multi-Select')) {
            $elements["onbehalf_{$key}"]['type'] = 'Multi-Select';
            $elements["onbehalf_{$key}"]['value'] = array_values($defaults[$key]);
          }
          elseif ($htmlType == 'Autocomplete-Select') {
            $elements["onbehalf_{$key}"]['type'] = $htmlType;
            $elements["onbehalf_{$key}"]['value'] = $defaults[$key];
          }
          elseif ($htmlType == 'Select Date') {
            $elements["onbehalf_{$key}"]['type'] = $htmlType;
            //CRM-18349, date value must be ISO formatted before being set as a default value for crmDatepicker custom field
            $elements["onbehalf_{$key}"]['value'] = CRM_Utils_Date::processDate($defaults[$key], NULL, FALSE, 'Y-m-d G:i:s');
          }
          else {
            $elements["onbehalf_{$key}"]['type'] = $htmlType;
            $elements["onbehalf_{$key}"]['value'] = $defaults[$key];
          }
        }
        else {
          $elements["onbehalf_{$key}"]['value'] = '';
        }
      }
    }

    CRM_Utils_JSON::output($elements);
  }

  public static function jqState() {
    CRM_Utils_JSON::output(CRM_Core_BAO_Location::getChainSelectValues($_GET['_value'], 'country'));
  }

  public static function jqCounty() {
    CRM_Utils_JSON::output(CRM_Core_BAO_Location::getChainSelectValues($_GET['_value'], 'stateProvince'));
  }

  public static function getLocBlock() {
    // i wish i could retrieve loc block info based on loc_block_id,
    // Anyway, lets retrieve an event which has loc_block_id set to 'lbid'.
    if ($_REQUEST['lbid']) {
      $params = ['1' => [$_REQUEST['lbid'], 'Integer']];
      $eventId = CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_event WHERE loc_block_id=%1 LIMIT 1', $params);
    }
    // now lets use the event-id obtained above, to retrieve loc block information.
    if ($eventId) {
      $params = ['entity_id' => $eventId, 'entity_table' => 'civicrm_event'];
      // second parameter is of no use, but since required, lets use the same variable.
      $location = CRM_Core_BAO_Location::getValues($params, $params);
    }

    $result = [];
    $addressOptions = CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
      'address_options', TRUE, NULL, TRUE
    );
    // lets output only required fields.
    foreach ($addressOptions as $element => $isSet) {
      if ($isSet && (!in_array($element, [
        'im',
        'openid',
      ]))) {
        if (in_array($element, [
          'country',
          'state_province',
          'county',
        ])) {
          $element .= '_id';
        }
        elseif ($element == 'address_name') {
          $element = 'name';
        }
        $fld = "address[1][{$element}]";
        $value = $location['address'][1][$element] ?? NULL;
        $value = $value ? $value : "";
        $result[str_replace([
          '][',
          '[',
          "]",
        ], ['_', '_', ''], $fld)] = $value;
      }
    }

    foreach ([
      'email',
      'phone_type_id',
      'phone',
    ] as $element) {
      $block = ($element == 'phone_type_id') ? 'phone' : $element;
      for ($i = 1; $i < 3; $i++) {
        $fld = "{$block}[{$i}][{$element}]";
        $value = $location[$block][$i][$element] ?? NULL;
        $value = $value ? $value : "";
        $result[str_replace([
          '][',
          '[',
          "]",
        ], ['_', '_', ''], $fld)] = $value;
      }
    }

    // set the message if loc block is being used by more than one event.
    $result['count_loc_used'] = CRM_Event_BAO_Event::countEventsUsingLocBlockId($_REQUEST['lbid']);

    CRM_Utils_JSON::output($result);
  }

}
