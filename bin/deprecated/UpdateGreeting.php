<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                               |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/


/*
 * Using this script you can update Email Greetings, Postal Greetings and Addressee for a specific contact type
 *
 * params for this script
 * ct=Individual or ct=Household or ct=Organization (ct = contact type)
 * gt=email_greeting or gt=postal_greeting or gt=addressee (gt = greeting )
 * id=greeting option value
 *
 * IMPORTANT: You must first create valid option value before using via admin interface.
 * Check option lists for Email Greetings, Postal Greetings and Addressee
 */

/**
 * Class CRM_UpdateGreeting
 */
class CRM_UpdateGreeting {
  /**
   *
   */
  function __construct() {
    $this->initialize();

    $config = CRM_Core_Config::singleton();

    require_once 'CRM/Utils/Request.php';
    require_once 'CRM/Core/PseudoConstant.php';
    require_once 'CRM/Contact/BAO/Contact.php';

    // this does not return on failure
    CRM_Utils_System::authenticateScript(TRUE);

    //log the execution time of script
    CRM_Core_Error::debug_log_message('UpdateGreeting.php');
  }

  function initialize() {
    require_once '../../civicrm.config.php';
    require_once 'CRM/Core/Config.php';
  }

  public function updateGreeting() {
    $config = CRM_Core_Config::singleton();
    $contactType = CRM_Utils_Request::retrieve('ct', 'String', CRM_Core_DAO::$_nullArray, FALSE, NULL, 'REQUEST');
    if (!in_array($contactType,
        array('Individual', 'Household', 'Organization')
      )) {
      CRM_Core_Error::fatal(ts('Invalid Contact Type.'));
    }

    $greeting = CRM_Utils_Request::retrieve('gt', 'String', CRM_Core_DAO::$_nullArray, FALSE, NULL, 'REQUEST');
    if (!in_array($greeting,
        array('email_greeting', 'postal_greeting', 'addressee')
      )) {
      CRM_Core_Error::fatal(ts('Invalid Greeting Type.'));
    }

    if (in_array($greeting, array(
      'email_greeting', 'postal_greeting')) && $contactType == 'Organization') {
      CRM_Core_Error::fatal(ts('You cannot use %1 for contact type %2.', array(1 => $greeting, 2 => $contactType)));
    }

    $valueID = $id = CRM_Utils_Request::retrieve('id', 'Positive', CRM_Core_DAO::$_nullArray, FALSE, NULL, 'REQUEST');

    // if valueID is not passed use default value
    if (!$valueID) {
      require_once 'CRM/Core/OptionGroup.php';
      $contactTypeFilters = array(1 => 'Individual', 2 => 'Household', 3 => 'Organization');
      $filter             = CRM_Utils_Array::key($contactType, $contactTypeFilters);
      $defaulValueID      = CRM_Core_OptionGroup::values($greeting, NULL, NULL, NULL,
        " AND is_default = 1 AND ( filter = {$filter} OR filter = 0 )",
        "value"
      );
      $valueID = array_pop($defaulValueID);
    }

    $filter = array(
      'contact_type' => $contactType,
      'greeting_type' => $greeting,
    );

    $allGreetings = CRM_Core_PseudoConstant::greeting($filter);
    $originalGreetingString = $greetingString = CRM_Utils_Array::value($valueID, $allGreetings);
    if (!$greetingString) {
      CRM_Core_Error::fatal(ts('Incorrect greeting value id %1.', array(1 => $valueID)));
    }

    // build return properties based on tokens
    require_once 'CRM/Utils/Token.php';
    $greetingTokens = CRM_Utils_Token::getTokens($greetingString);
    $tokens = CRM_Utils_Array::value('contact', $greetingTokens);
    $greetingsReturnProperties = array();
    if (is_array($tokens)) {
      $greetingsReturnProperties = array_fill_keys(array_values($tokens), 1);
    }

    //process all contacts only when force pass.
    $force = CRM_Utils_Request::retrieve('force', 'String', CRM_Core_DAO::$_nullArray, FALSE, NULL, 'REQUEST');
    $processAll = $processOnlyIdSet = FALSE;
    if (in_array($force, array(
      1, 'true'))) {
      $processAll = TRUE;
    }
    elseif ($force == 2) {
      $processOnlyIdSet = TRUE;
    }

    //FIXME : apiQuery should handle these clause.
    $filterContactFldIds = $filterIds = array();
    if (!$processAll) {
      $idFldName = $displayFldName = NULL;
      if ($greeting == 'email_greeting' || $greeting == 'postal_greeting' || $greeting == 'addressee') {
        $idFldName = $greeting . '_id';
        $displayFldName = $greeting . '_display';
      }

      if ($idFldName) {
        $sql = "
SELECT DISTINCT id, $idFldName
  FROM civicrm_contact
 WHERE contact_type = %1
   AND ( {$idFldName} IS NULL OR
         ( {$idFldName} IS NOT NULL AND {$displayFldName} IS NULL ) )
   ";
        $dao = CRM_Core_DAO::executeQuery($sql, array(1 => array($contactType, 'String')));
        while ($dao->fetch()) {
          $filterContactFldIds[$dao->id] = $dao->$idFldName;

          if (!CRM_Utils_System::isNull($dao->$idFldName)) {
            $filterIds[$dao->id] = $dao->$idFldName;
          }
        }
      }
      if (empty($filterContactFldIds)) {
        $filterContactFldIds[] = 0;
      }
    }

    if (empty($filterContactFldIds)) {
      return;
    }

    // retrieve only required contact information
    require_once 'CRM/Utils/Token.php';
    $extraParams[] = array('contact_type', '=', $contactType, 0, 0);
    // we do token replacement in the replaceGreetingTokens hook
    list($greetingDetails) = CRM_Utils_Token::getTokenDetails(array_keys($filterContactFldIds),
      $greetingsReturnProperties,
      FALSE, FALSE, $extraParams
    );
    // perform token replacement and build update SQL
    $contactIds = array();
    $cacheFieldQuery = "UPDATE civicrm_contact SET {$greeting}_display = CASE id ";
    foreach ($greetingDetails as $contactID => $contactDetails) {
      if (!$processAll &&
        !array_key_exists($contactID, $filterContactFldIds)
      ) {
        continue;
      }

      if ($processOnlyIdSet) {
        if (!array_key_exists($contactID, $filterIds)) {
          continue;
        }
        if ($id) {
          $greetingString = $originalGreetingString;
          $contactIds[] = $contactID;
        }
        else {
          if ($greetingBuffer = CRM_Utils_Array::value($filterContactFldIds[$contactID], $allGreetings)) {
            $greetingString = $greetingBuffer;
          }
        }
        $allContactIds[] = $contactID;
      }
      else {
        $greetingString = $originalGreetingString;
        if ($greetingBuffer = CRM_Utils_Array::value($filterContactFldIds[$contactID], $allGreetings)) {
          $greetingString = $greetingBuffer;
        }
        else {
          $contactIds[] = $contactID;
        }
      }
      CRM_Contact_BAO_Contact_Utils::processGreetingTemplate($greetingString, $contactDetails, $contactID, 'CRM_UpdateGreeting');
      $greetingString = CRM_Core_DAO::escapeString($greetingString);
      $cacheFieldQuery .= " WHEN {$contactID} THEN '{$greetingString}' ";

      $allContactIds[] = $contactID;
    }

    if (!empty($allContactIds)) {
      $cacheFieldQuery .= " ELSE {$greeting}_display
                              END;";
      if (!empty($contactIds)) {
        // need to update greeting _id field.
        $queryString = "
UPDATE civicrm_contact
   SET {$greeting}_id = {$valueID}
 WHERE id IN (" . implode(',', $contactIds) . ")";
        CRM_Core_DAO::executeQuery($queryString);
      }

      // now update cache field
      CRM_Core_DAO::executeQuery($cacheFieldQuery);
    }
  }
}

$obj = new CRM_UpdateGreeting();
$obj->updateGreeting();
echo "\n\n Greeting is updated for contact(s). (Done) \n";

