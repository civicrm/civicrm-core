<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * This class contains all the function that are called using AJAX
 */
class CRM_Admin_Page_AJAX {

  /**
   * Function to build menu tree
   */
  static function getNavigationList() {
    echo CRM_Core_BAO_Navigation::buildNavigation(TRUE, FALSE);
    CRM_Utils_System::civiExit();
  }

  /**
   * Function to process drag/move action for menu tree
   */
  static function menuTree() {
    echo CRM_Core_BAO_Navigation::processNavigation($_GET);
    CRM_Utils_System::civiExit();
  }

  /**
   * Function to build status message while
   * enabling/ disabling various objects
   */
  static function getStatusMsg() {
    $recordID  = CRM_Utils_Type::escape($_POST['recordID'], 'Integer');
    $recordBAO = CRM_Utils_Type::escape($_POST['recordBAO'], 'String');
    $op        = CRM_Utils_Type::escape($_POST['op'], 'String');
    $show      = NULL;

    if ($op == 'disable-enable') {
      $status = ts('Are you sure you want to enable this record?');
    }
    else {
      switch ($recordBAO) {
        case 'CRM_Core_BAO_UFGroup':
          require_once (str_replace('_', DIRECTORY_SEPARATOR, $recordBAO) . '.php');
          $method = 'getUFJoinRecord';
          $result = array($recordBAO, $method);
          $ufJoin = call_user_func_array(($result), array($recordID, TRUE));
          if (!empty($ufJoin)) {
            $status = ts('This profile is currently used for %1.', array(1 => implode(', ', $ufJoin))) . ' <br/><br/>' . ts('If you disable the profile - it will be removed from these forms and/or modules. Do you want to continue?');
          }
          else {
            $status = ts('Are you sure you want to disable this profile?');
          }
          break;

        case 'CRM_Price_BAO_Set':
          require_once (str_replace('_', DIRECTORY_SEPARATOR, $recordBAO) . '.php');
          $usedBy = CRM_Price_BAO_Set::getUsedBy($recordID);
          $priceSet = CRM_Price_BAO_Set::getTitle($recordID);

          if (!CRM_Utils_System::isNull($usedBy)) {
            $template = CRM_Core_Smarty::singleton();
            $template->assign('usedBy', $usedBy);
            $comps = array(
              'Event' => 'civicrm_event',
              'Contribution' => 'civicrm_contribution_page',
              'EventTemplate' => 'civicrm_event_template'
            );
            $contexts = array();
            foreach ($comps as $name => $table) {
              if (array_key_exists($table, $usedBy)) {
                $contexts[] = $name;
              }
            }
            $template->assign('contexts', $contexts);

            $show   = 'noButton';
            $table  = $template->fetch('CRM/Price/Page/table.tpl');
            $status = ts('Unable to disable the \'%1\' price set - it is currently in use by one or more active events, contribution pages or contributions.', array(
              1 => $priceSet)) . "<br/> $table";
          }
          else {
            $status = ts('Are you sure you want to disable \'%1\' Price Set?', array(1 => $priceSet));
          }
          break;

        case 'CRM_Event_BAO_Event':
          $status = ts('Are you sure you want to disable this Event?');
          break;

        case 'CRM_Core_BAO_UFField':
          $status = ts('Are you sure you want to disable this CiviCRM Profile field?');
          break;

        case 'CRM_Contribute_BAO_ManagePremiums':
          $status = ts('Are you sure you want to disable this premium? This action will remove the premium from any contribution pages that currently offer it. However it will not delete the premium record - so you can re-enable it and add it back to your contribution page(s) at a later time.');
          break;

        case 'CRM_Contact_BAO_RelationshipType':
          $status = ts('Are you sure you want to disable this relationship type?') . '<br/><br/>' . ts('Users will no longer be able to select this value when adding or editing relationships between contacts.');
          break;

        case 'CRM_Financial_BAO_FinancialType':
          $status = ts('Are you sure you want to disable this financial type?');
          break;
          
        case 'CRM_Financial_BAO_FinancialAccount':
          if (!CRM_Financial_BAO_FinancialAccount::getARAccounts($recordID)) {
            $show   = 'noButton';
            $status = ts('The selected financial account cannot be disabled because at least one Accounts Receivable type account is required (to ensure that accounting transactions are in balance).');
          }
          else {
            $status = ts('Are you sure you want to disable this financial account?');
          }
          break;

        case 'CRM_Financial_BAO_PaymentProcessor': 
          $status = ts('Are you sure you want to disable this payment processor?') . ' <br/><br/>' . ts('Users will no longer be able to select this value when adding or editing transaction pages.');
          break;

        case 'CRM_Financial_BAO_PaymentProcessorType':
          $status = ts('Are you sure you want to disable this payment processor type?');
          break;

        case 'CRM_Core_BAO_LocationType':
          $status = ts('Are you sure you want to disable this location type?') . ' <br/><br/>' . ts('Users will no longer be able to select this value when adding or editing contact locations.');
          break;

        case 'CRM_Event_BAO_ParticipantStatusType':
          $status = ts('Are you sure you want to disable this Participant Status?') . '<br/><br/> ' . ts('Users will no longer be able to select this value when adding or editing Participant Status.');
          break;

        case 'CRM_Mailing_BAO_Component':
          $status = ts('Are you sure you want to disable this component?');
          break;

        case 'CRM_Core_BAO_CustomField':
          $status = ts('Are you sure you want to disable this custom data field?');
          break;

        case 'CRM_Core_BAO_CustomGroup':
          $status = ts('Are you sure you want to disable this custom data group? Any profile fields that are linked to custom fields of this group will be disabled.');
          break;

        case 'CRM_Core_BAO_MessageTemplates':
          $status = ts('Are you sure you want to disable this message tempate?');
          break;

        case 'CRM_ACL_BAO_ACL':
          $status = ts('Are you sure you want to disable this ACL?');
          break;

        case 'CRM_ACL_BAO_EntityRole':
          $status = ts('Are you sure you want to disable this ACL Role Assignment?');
          break;

        case 'CRM_Member_BAO_MembershipType':
          $status = ts('Are you sure you want to disable this membership type?');
          break;

        case 'CRM_Member_BAO_MembershipStatus':
          $status = ts('Are you sure you want to disable this membership status rule?');
          break;

        case 'CRM_Price_BAO_Field':
          $status = ts('Are you sure you want to disable this price field?');
          break;

        case 'CRM_Contact_BAO_Group':
          $status = ts('Are you sure you want to disable this Group?');
          break;

        case 'CRM_Core_BAO_OptionGroup':
          $status = ts('Are you sure you want to disable this Option?');
          break;

        case 'CRM_Contact_BAO_ContactType':
          $status = ts('Are you sure you want to disable this Contact Type?');
          break;

        case 'CRM_Core_BAO_OptionValue':
          require_once (str_replace('_', DIRECTORY_SEPARATOR, $recordBAO) . '.php');
          $label = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue', $recordID, 'label');
          $status = ts('Are you sure you want to disable the \'%1\' option ?', array(1 => $label));
          $status .= '<br /><br />' . ts('WARNING - Disabling an option which has been assigned to existing records will result in that option being cleared when the record is edited.');
          break;

        case 'CRM_Contribute_BAO_ContributionRecur':
          $recurDetails = CRM_Contribute_BAO_ContributionRecur::getSubscriptionDetails($recordID);
          $status = ts('Are you sure you want to mark this recurring contribution as cancelled?');
          $status .= '<br /><br /><strong>' . ts('WARNING - This action sets the CiviCRM recurring contribution status to Cancelled, but does NOT send a cancellation request to the payment processor. You will need to ensure that this recurring payment (subscription) is cancelled by the payment processor.') . '</strong>';
          if ($recurDetails->membership_id) {
            $status .= '<br /><br /><strong>' . ts('This recurring contribution is linked to an auto-renew membership. If you cancel it, the associated membership will no longer renew automatically. However, the current membership status will not be affected.') . '</strong>';
          }
          break;
          
        case 'CRM_Batch_BAO_Batch':
          if ($op == 'close') {
            $status = ts('Are you sure you want to close this batch?');
          }
          elseif ($op == 'open') {
            $status = ts('Are you sure you want to reopen this batch?');
          }
          elseif ($op == 'delete') {
            $status = ts('Are you sure you want to delete this batch?');
          }
          elseif ($op == 'remove') {
            $status = ts('Are you sure you want to remove this financial transaction?');
          }
          elseif ($op == 'export') {
            $status = ts('Are you sure you want to close and export this batch?');
          }
          else {
            $status = ts('Are you sure you want to assign this financial transaction to the batch?');
          }
          break;
          
        default:
          $status = ts('Are you sure you want to disable this record?');
          break;
      }
    }
    $statusMessage['status'] = $status;
    $statusMessage['show'] = $show;

    echo json_encode($statusMessage);
    CRM_Utils_System::civiExit();
  }

  static function getTagList() {
    $name = CRM_Utils_Type::escape($_GET['name'], 'String');
    $parentId = CRM_Utils_Type::escape($_GET['parentId'], 'Integer');

    $isSearch = NULL;
    if (isset($_GET['search'])) {
      $isSearch = CRM_Utils_Type::escape($_GET['search'], 'Integer');
    }

    $tags = array();

    // always add current search term as possible tag
    // here we append :::value to determine if existing / new tag should be created
    if (!$isSearch) {
      $tags[] = array(
        'name' => $name,
        'id' => $name . ":::value",
      );
    }

    $query = "SELECT id, name FROM civicrm_tag WHERE parent_id = {$parentId} and name LIKE '%{$name}%'";
    $dao = CRM_Core_DAO::executeQuery($query);

    while ($dao->fetch()) {
      // make sure we return tag name entered by user only if it does not exists in db
      if ($name == $dao->name) {
        $tags = array();
      }
      // escape double quotes, which break results js
      $tags[] = array('name' => addcslashes($dao->name, '"'),
        'id' => $dao->id,
      );
    }

    echo json_encode($tags);
    CRM_Utils_System::civiExit();
  }

  static function mergeTagList() {
    $name   = CRM_Utils_Type::escape($_GET['s'], 'String');
    $fromId = CRM_Utils_Type::escape($_GET['fromId'], 'Integer');
    $limit  = CRM_Utils_Type::escape($_GET['limit'], 'Integer');

    // build used-for clause to be used in main query
    $usedForTagA = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Tag', $fromId, 'used_for');
    $usedForClause = array();
    if ($usedForTagA) {
      $usedForTagA = explode(",", $usedForTagA);
      foreach ($usedForTagA as $key => $value) {
        $usedForClause[] = "t1.used_for LIKE '%{$value}%'";
      }
    }
    $usedForClause = !empty($usedForClause) ? implode(' OR ', $usedForClause) : '1';
    sort($usedForTagA);

    // query to list mergable tags
    $query = "
SELECT t1.name, t1.id, t1.used_for, t2.name as parent
FROM   civicrm_tag t1 
LEFT JOIN civicrm_tag t2 ON t1.parent_id = t2.id
WHERE  t1.id <> {$fromId} AND 
       t1.name LIKE '%{$name}%' AND
       ({$usedForClause}) 
LIMIT $limit";
    $dao = CRM_Core_DAO::executeQuery($query);

    while ($dao->fetch()) {
      $warning = 0;
      if (!empty($dao->used_for)) {
        $usedForTagB = explode(',', $dao->used_for);
        sort($usedForTagB);
        $usedForDiff = array_diff($usedForTagA, $usedForTagB);
        if (!empty($usedForDiff)) {
          $warning = 1;
        }
      }
      $tag = addcslashes($dao->name, '"') . "|{$dao->id}|{$warning}\n";
      echo $tag = $dao->parent ? (addcslashes($dao->parent, '"') . ' :: ' . $tag) : $tag;
    }
    CRM_Utils_System::civiExit();
  }

  static function processTags() {
    $skipTagCreate = $skipEntityAction = $entityId = NULL;
    $action        = CRM_Utils_Type::escape($_POST['action'], 'String');
    $parentId      = CRM_Utils_Type::escape($_POST['parentId'], 'Integer');
    if ($_POST['entityId']) {
      $entityId = CRM_Utils_Type::escape($_POST['entityId'], 'Integer');
    }

    $entityTable = CRM_Utils_Type::escape($_POST['entityTable'], 'String');

    if ($_POST['skipTagCreate']) {
      $skipTagCreate = CRM_Utils_Type::escape($_POST['skipTagCreate'], 'Integer');
    }

    if ($_POST['skipEntityAction']) {
      $skipEntityAction = CRM_Utils_Type::escape($_POST['skipEntityAction'], 'Integer');
    }

    // check if user has selected existing tag or is creating new tag
    // this is done to allow numeric tags etc.
    $tagValue = explode(':::', $_POST['tagID']);

    $createNewTag = FALSE;
    $tagID = $tagValue[0];
    if (isset($tagValue[1]) && $tagValue[1] == 'value') {
      $createNewTag = TRUE;
    }

    $tagInfo = array();
    // if action is select
    if ($action == 'select') {
      // check the value of tagID
      // if numeric that means existing tag
      // else create new tag
      if (!$skipTagCreate && $createNewTag) {
        $params = array(
          'name' => $tagID,
          'parent_id' => $parentId,
        );

        $tagObject = CRM_Core_BAO_Tag::add($params, CRM_Core_DAO::$_nullArray);

        $tagInfo = array(
          'name' => $tagID,
          'id' => $tagObject->id,
          'action' => $action,
        );
        $tagID = $tagObject->id;
      }

      if (!$skipEntityAction && $entityId) {
        // save this tag to contact
        $params = array(
          'entity_table' => $entityTable,
          'entity_id' => $entityId,
          'tag_id' => $tagID,
        );

        CRM_Core_BAO_EntityTag::add($params);
      }
      // if action is delete
    }
    elseif ($action == 'delete') {
      if (!is_numeric($tagID)) {
        $tagID = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Tag', $tagID, 'id', 'name');
      }
      if ($entityId) {
        // delete this tag entry for the entity
        $params = array(
          'entity_table' => $entityTable,
          'entity_id' => $entityId,
          'tag_id' => $tagID,
        );

        CRM_Core_BAO_EntityTag::del($params);
      }
      $tagInfo = array(
        'id' => $tagID,
        'action' => $action,
      );
    }

    echo json_encode($tagInfo);
    CRM_Utils_System::civiExit();
  }

  function mappingList() {
    $params = array('mappingID');
    foreach ($params as $param) {
      $$param = CRM_Utils_Array::value($param, $_POST);
    }

    if (!$mappingID) {
      echo json_encode(array('error_msg' => 'required params missing.'));
      CRM_Utils_System::civiExit();
    }

    $selectionOptions = CRM_Core_BAO_ActionSchedule::getSelection1($mappingID);
    extract($selectionOptions);

    $elements = array();
    foreach ($sel4 as $id => $name) {
      $elements[] = array(
        'name' => $name,
        'value' => $id,
      );
    }

    echo json_encode($elements);
    CRM_Utils_System::civiExit();
  }

  function mappingList1() {
    $params = array('mappingID');
    foreach ($params as $param) {
      $$param = CRM_Utils_Array::value($param, $_POST);
    }

    if (!$mappingID) {
      echo json_encode(array('error_msg' => 'required params missing.'));
      CRM_Utils_System::civiExit();
    }

    $selectionOptions = CRM_Core_BAO_ActionSchedule::getSelection1($mappingID);
    extract($selectionOptions);

    $elements = array();
    foreach ($sel5 as $id => $name) {
      $elements['sel5'][] = array(
        'name' => $name,
        'value' => $id,
      );
    }
    $elements['recipientMapping'] = $recipientMapping;

    echo json_encode($elements);
    CRM_Utils_System::civiExit();
  }

  static function mergeTags() {
    $tagAId = CRM_Utils_Type::escape($_POST['fromId'], 'Integer');
    $tagBId = CRM_Utils_Type::escape($_POST['toId'], 'Integer');

    $result = CRM_Core_BAO_EntityTag::mergeTags($tagAId, $tagBId);

    if (!empty($result['tagB_used_for'])) {
      $usedFor = CRM_Core_OptionGroup::values('tag_used_for');
      foreach ($result['tagB_used_for'] as & $val) {
        $val = $usedFor[$val];
      }
      $result['tagB_used_for'] = implode(', ', $result['tagB_used_for']);
    }

    echo json_encode($result);
    CRM_Utils_System::civiExit();
  }

  function recipient() {
    $params = array('recipient');
    foreach ($params as $param) {
      $$param = CRM_Utils_Array::value($param, $_POST);
    }

    if (!$recipient) {
      echo json_encode(array('error_msg' => 'required params missing.'));
      CRM_Utils_System::civiExit();
    }

    switch ($recipient) {
      case 'Participant Status':
        $values = CRM_Event_PseudoConstant::participantStatus();
        break;

      case 'Participant Role':
        $values = CRM_Event_PseudoConstant::participantRole();
        break;

      default:
        exit;
    }

    $elements = array();
    foreach ($values as $id => $name) {
      $elements[] = array(
        'name' => $name,
        'value' => $id,
      );
    }

    echo json_encode($elements);
    CRM_Utils_System::civiExit();
  }
}

