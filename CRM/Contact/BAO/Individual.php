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

/**
 * Class contains functions for individual contact type.
 */
class CRM_Contact_BAO_Individual extends CRM_Contact_DAO_Contact {

  /**
   * Class constructor.
   */
  public function __construct() {
  }

  /**
   * Function is used to format the individual contact values.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param CRM_Contact_BAO_Contact $contact
   *   Contact object.
   *
   * @return CRM_Contact_BAO_Contact
   */
  public static function format(&$params, &$contact) {
    if (!self::dataExists($params)) {
      return NULL;
    }

    // "null" value for example is passed by dedupe merge in order to empty.
    // Display name computation shouldn't consider such values.
    foreach (['first_name', 'middle_name', 'last_name', 'nick_name', 'formal_title', 'birth_date', 'deceased_date'] as $displayField) {
      if (CRM_Utils_Array::value($displayField, $params) == "null") {
        $params[$displayField] = '';
      }
    }

    $sortName = $displayName = '';
    $firstName = trim($params['first_name'] ?? '');
    $middleName = trim($params['middle_name'] ?? '');
    $lastName = trim($params['last_name'] ?? '');
    $nickName = CRM_Utils_Array::value('nick_name', $params, '');
    $prefix_id = CRM_Utils_Array::value('prefix_id', $params, '');
    $suffix_id = CRM_Utils_Array::value('suffix_id', $params, '');
    $formalTitle = CRM_Utils_Array::value('formal_title', $params, '');

    // get prefix and suffix names
    $prefix = $suffix = NULL;
    if ($prefix_id) {
      $params['individual_prefix'] = $prefix = CRM_Core_PseudoConstant::getLabel('CRM_Contact_DAO_Contact', 'prefix_id', $prefix_id);
    }
    if ($suffix_id) {
      $params['individual_suffix'] = $suffix = CRM_Core_PseudoConstant::getLabel('CRM_Contact_DAO_Contact', 'suffix_id', $suffix_id);
    }

    $individual = NULL;
    if ($contact->id) {
      $individual = new CRM_Contact_BAO_Contact();
      $individual->id = $contact->id;
      if ($individual->find(TRUE)) {

        //lets allow to update single name field though preserveDBName
        //but if db having null value and params contain value, CRM-4330.
        $useDBNames = [];

        foreach (['last', 'middle', 'first', 'nick'] as $name) {
          $dbName = "{$name}_name";
          $value = $individual->$dbName;

          // the db has name values
          if ($value && !empty($params['preserveDBName'])) {
            $useDBNames[] = $name;
          }
        }

        foreach (['prefix', 'suffix'] as $name) {
          $dbName = "{$name}_id";
          $value = $individual->$dbName;
          if ($value && !empty($params['preserveDBName'])) {
            $useDBNames[] = $name;
          }
        }

        if ($individual->formal_title && !empty($params['preserveDBName'])) {
          $useDBNames[] = 'formal_title';
        }

        // CRM-4430
        //1. preserve db name if want
        //2. lets get value from param if exists.
        //3. if not in params, lets get from db.

        foreach (['last', 'middle', 'first', 'nick'] as $name) {
          $phpName = "{$name}Name";
          $dbName = "{$name}_name";
          $value = $individual->$dbName;
          if (in_array($name, $useDBNames)) {
            $params[$dbName] = $value;
            $contact->$dbName = $value;
            $$phpName = $value;
          }
          elseif (array_key_exists($dbName, $params)) {
            $$phpName = $params[$dbName];
          }
          elseif ($value) {
            $$phpName = $value;
          }
        }

        foreach (['prefix', 'suffix'] as $name) {
          $dbName = "{$name}_id";

          $value = $individual->$dbName;
          if (in_array($name, $useDBNames)) {
            $params[$dbName] = $value;
            $contact->$dbName = $value;
            if ($value) {
              $$name = CRM_Core_PseudoConstant::getLabel('CRM_Contact_DAO_Contact', $dbName, $value);
            }
            else {
              $$name = NULL;
            }
          }
          elseif (array_key_exists($dbName, $params)) {
            // CRM-5278
            if (!empty($params[$dbName])) {
              $$name = CRM_Core_PseudoConstant::getLabel('CRM_Contact_DAO_Contact', $dbName, $params[$dbName]);
            }
          }
          elseif ($value) {
            $$name = CRM_Core_PseudoConstant::getLabel('CRM_Contact_DAO_Contact', $dbName, $value);
          }
        }

        if (in_array('formal_title', $useDBNames)) {
          $params['formal_title'] = $individual->formal_title;
          $contact->formal_title = $individual->formal_title;
          $formalTitle = $individual->formal_title;
        }
        elseif (array_key_exists('formal_title', $params)) {
          $formalTitle = $params['formal_title'];
        }
        elseif ($individual->formal_title) {
          $formalTitle = $individual->formal_title;
        }
      }
    }

    if ($lastName || $firstName || $middleName) {
      // make sure we have values for all the name fields.
      $formatted = $params;
      $nameParams = [
        'first_name' => $firstName,
        'middle_name' => $middleName,
        'last_name' => $lastName,
        'nick_name' => $nickName,
        'individual_suffix' => $suffix,
        'individual_prefix' => $prefix,
        'prefix_id' => $prefix_id,
        'suffix_id' => $suffix_id,
        'formal_title' => $formalTitle,
      ];
      // make sure we have all the name fields.
      foreach ($nameParams as $name => $value) {
        if (empty($formatted[$name]) && $value) {
          $formatted[$name] = $value;
        }
      }

      $tokens = [];
      CRM_Utils_Hook::tokens($tokens);
      $tokenFields = [];
      foreach ($tokens as $catTokens) {
        foreach ($catTokens as $token => $label) {
          $tokenFields[] = $token;
        }
      }

      //build the sort name.
      $format = Civi::settings()->get('sort_name_format');
      $sortName = CRM_Utils_Address::format($formatted, $format,
        FALSE, FALSE, $tokenFields
      );
      $sortName = trim($sortName);

      //build the display name.
      $format = Civi::settings()->get('display_name_format');
      $displayName = CRM_Utils_Address::format($formatted, $format,
        FALSE, FALSE, $tokenFields
      );
      $displayName = trim($displayName);
    }

    //start further check for email.
    if (empty($sortName) || empty($displayName)) {
      $email = NULL;
      if (!empty($params['email']) &&
        is_array($params['email'])
      ) {
        foreach ($params['email'] as $emailBlock) {
          if (isset($emailBlock['is_primary'])) {
            $email = $emailBlock['email'];
            break;
          }
        }
      }
      $uniqId = $params['user_unique_id'] ?? NULL;
      if (!$email && $contact->id) {
        $email = CRM_Contact_BAO_Contact::getPrimaryEmail($contact->id);
      }
    }

    //now set the names.
    $names = ['displayName' => 'display_name', 'sortName' => 'sort_name'];
    foreach ($names as $value => $name) {
      if (empty($$value)) {
        if ($email) {
          $$value = $email;
        }
        elseif ($uniqId) {
          $$value = $uniqId;
        }
        elseif (!empty($params[$name])) {
          $$value = $params[$name];
        }
        // If we have nothing else going on set sort_name to display_name.
        elseif ($displayName) {
          $$value = $displayName;
        }
      }
      //finally if we could not pass anything lets keep db.
      if (!empty($$value)) {
        $contact->$name = $$value;
      }
    }

    $format = CRM_Utils_Date::getDateFormat('birth');
    if ($date = CRM_Utils_Array::value('birth_date', $params)) {
      if (in_array($format, [
        'dd-mm',
        'mm/dd',
      ])) {
        $separator = '/';
        if ($format == 'dd-mm') {
          $separator = '-';
        }
        $date = $date . $separator . '1902';
      }
      elseif (in_array($format, [
        'yy-mm',
      ])) {
        $date = $date . '-01';
      }
      elseif (in_array($format, [
        'M yy',
      ])) {
        $date = $date . '-01';
      }
      elseif (in_array($format, [
        'yy',
      ])) {
        $date = $date . '-01-01';
      }
      $processedDate = CRM_Utils_Date::processDate($date);
      $existing = substr(str_replace('-', '', $contact->birth_date), 0, 8) . '000000';
      // By adding this check here we can rip out this whole routine in a few
      // months after confirming it actually does nothing, ever.
      if ($existing !== $processedDate) {
        CRM_Core_Error::deprecatedWarning('birth_date formatting should happen before BAO is hit');
        $contact->birth_date = $processedDate;
      }
    }
    elseif ($contact->birth_date) {
      if ($contact->birth_date !== CRM_Utils_Date::isoToMysql($contact->birth_date)) {
        CRM_Core_Error::deprecatedWarning('birth date formatting should happen before BAO is hit');
      }
      $contact->birth_date = CRM_Utils_Date::isoToMysql($contact->birth_date);
    }

    if ($date = CRM_Utils_Array::value('deceased_date', $params)) {
      if (in_array($format, [
        'dd-mm',
        'mm/dd',
      ])) {
        $separator = '/';
        if ($format == 'dd-mm') {
          $separator = '-';
        }
        $date = $date . $separator . '1902';
      }
      elseif (in_array($format, [
        'yy-mm',
      ])) {
        $date = $date . '-01';
      }
      elseif (in_array($format, [
        'M yy',
      ])) {
        $date = $date . '-01';
      }
      elseif (in_array($format, [
        'yy',
      ])) {
        $date = $date . '-01-01';
      }
      $processedDate = CRM_Utils_Date::processDate($date);
      $existing = substr(str_replace('-', '', $contact->deceased_date), 0, 8) . '000000';
      // By adding this check here we can rip out this whole routine in a few
      // months after confirming it actually does nothing, ever.
      if ($existing !== $processedDate) {
        CRM_Core_Error::deprecatedWarning('deceased formatting should happen before BAO is hit');
      }
      $contact->deceased_date = CRM_Utils_Date::processDate($date);
    }
    elseif ($contact->deceased_date) {
      if ($contact->deceased_date !== CRM_Utils_Date::isoToMysql($contact->deceased_date)) {
        CRM_Core_Error::deprecatedWarning('deceased date formatting should happen before BAO is hit');
      }
      $contact->deceased_date = CRM_Utils_Date::isoToMysql($contact->deceased_date);
    }

    if ($middle_name = CRM_Utils_Array::value('middle_name', $params)) {
      if ($middle_name !== $contact->middle_name) {
        CRM_Core_Error::deprecatedWarning('random magic is deprecated - how could this be true');
      }
      $contact->middle_name = $middle_name;
    }

    return $contact;
  }

  /**
   * Creates display name.
   *
   * @return string
   *   the constructed display name
   */
  public function displayName() {
    $prefix = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'prefix_id');
    $suffix = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'suffix_id');
    return str_replace('  ', ' ', trim($prefix[$this->prefix_id] . ' ' . $this->first_name . ' ' . $this->middle_name . ' ' . $this->last_name . ' ' . $suffix[$this->suffix_id]));
  }

  /**
   * Check if there is data to create the object.
   *
   * @param array $params
   *
   * @return bool
   */
  public static function dataExists($params) {
    if ($params['contact_type'] == 'Individual') {
      return TRUE;
    }

    return FALSE;
  }

}
