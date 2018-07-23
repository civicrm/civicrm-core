<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 * This class contains all the function that are called using AJAX.
 */
class CRM_Admin_Page_AJAX {

  /**
   * CRM-12337 Output navigation menu as executable javascript.
   *
   * @see smarty_function_crmNavigationMenu
   */
  public static function getNavigationMenu() {
    $contactID = CRM_Core_Session::singleton()->get('userID');
    if ($contactID) {
      CRM_Core_Page_AJAX::setJsHeaders();
      $smarty = CRM_Core_Smarty::singleton();
      $smarty->assign('includeEmail', civicrm_api3('setting', 'getvalue', array('name' => 'includeEmailInName', 'group' => 'Search Preferences')));
      print $smarty->fetchWith('CRM/common/navigation.js.tpl', array(
        'navigation' => CRM_Core_BAO_Navigation::createNavigation($contactID),
      ));
    }
    CRM_Utils_System::civiExit();
  }

  /**
   * Process drag/move action for menu tree.
   */
  public static function menuTree() {
    CRM_Core_BAO_Navigation::processNavigation($_GET);
  }

  /**
   * Build status message while enabling/ disabling various objects.
   */
  public static function getStatusMsg() {
    require_once 'api/v3/utils.php';
    $recordID = CRM_Utils_Type::escape($_GET['id'], 'Integer');
    $entity = CRM_Utils_Type::escape($_GET['entity'], 'String');
    $ret = array();

    if ($recordID && $entity && $recordBAO = _civicrm_api3_get_BAO($entity)) {
      switch ($recordBAO) {
        case 'CRM_Core_BAO_UFGroup':
          $method = 'getUFJoinRecord';
          $result = array($recordBAO, $method);
          $ufJoin = call_user_func_array(($result), array($recordID, TRUE));
          if (!empty($ufJoin)) {
            $ret['content'] = ts('This profile is currently used for %1.', array(1 => implode(', ', $ufJoin))) . ' <br/><br/>' . ts('If you disable the profile - it will be removed from these forms and/or modules. Do you want to continue?');
          }
          else {
            $ret['content'] = ts('Are you sure you want to disable this profile?');
          }
          break;

        case 'CRM_Price_BAO_PriceSet':
          $usedBy = CRM_Price_BAO_PriceSet::getUsedBy($recordID);
          $priceSet = CRM_Price_BAO_PriceSet::getTitle($recordID);

          if (!CRM_Utils_System::isNull($usedBy)) {
            $template = CRM_Core_Smarty::singleton();
            $template->assign('usedBy', $usedBy);
            $comps = array(
              'Event' => 'civicrm_event',
              'Contribution' => 'civicrm_contribution_page',
              'EventTemplate' => 'civicrm_event_template',
            );
            $contexts = array();
            foreach ($comps as $name => $table) {
              if (array_key_exists($table, $usedBy)) {
                $contexts[] = $name;
              }
            }
            $template->assign('contexts', $contexts);

            $ret['illegal'] = TRUE;
            $table = $template->fetch('CRM/Price/Page/table.tpl');
            $ret['content'] = ts('Unable to disable the \'%1\' price set - it is currently in use by one or more active events, contribution pages or contributions.', array(
                1 => $priceSet,
              )) . "<br/> $table";
          }
          else {
            $ret['content'] = ts('Are you sure you want to disable \'%1\' Price Set?', array(1 => $priceSet));
          }
          break;

        case 'CRM_Event_BAO_Event':
          $ret['content'] = ts('Are you sure you want to disable this Event?');
          break;

        case 'CRM_Core_BAO_UFField':
          $ret['content'] = ts('Are you sure you want to disable this CiviCRM Profile field?');
          break;

        case 'CRM_Contribute_BAO_Product':
          $ret['content'] = ts('Are you sure you want to disable this premium? This action will remove the premium from any contribution pages that currently offer it. However it will not delete the premium record - so you can re-enable it and add it back to your contribution page(s) at a later time.');
          break;

        case 'CRM_Contact_BAO_Relationship':
          $ret['content'] = ts('Are you sure you want to disable this relationship?');
          break;

        case 'CRM_Contact_BAO_RelationshipType':
          $ret['content'] = ts('Are you sure you want to disable this relationship type?') . '<br/><br/>' . ts('Users will no longer be able to select this value when adding or editing relationships between contacts.');
          break;

        case 'CRM_Financial_BAO_FinancialType':
          $ret['content'] = ts('Are you sure you want to disable this financial type?');
          break;

        case 'CRM_Financial_BAO_FinancialAccount':
          if (!CRM_Financial_BAO_FinancialAccount::getARAccounts($recordID)) {
            $ret['illegal'] = TRUE;
            $ret['content'] = ts('The selected financial account cannot be disabled because at least one Accounts Receivable type account is required (to ensure that accounting transactions are in balance).');
          }
          else {
            $ret['content'] = ts('Are you sure you want to disable this financial account?');
          }
          break;

        case 'CRM_Financial_BAO_PaymentProcessor':
          $ret['content'] = ts('Are you sure you want to disable this payment processor?') . ' <br/><br/>' . ts('Users will no longer be able to select this value when adding or editing transaction pages.');
          break;

        case 'CRM_Financial_BAO_PaymentProcessorType':
          $ret['content'] = ts('Are you sure you want to disable this payment processor type?');
          break;

        case 'CRM_Core_BAO_LocationType':
          $ret['content'] = ts('Are you sure you want to disable this location type?') . ' <br/><br/>' . ts('Users will no longer be able to select this value when adding or editing contact locations.');
          break;

        case 'CRM_Event_BAO_ParticipantStatusType':
          $ret['content'] = ts('Are you sure you want to disable this Participant Status?') . '<br/><br/> ' . ts('Users will no longer be able to select this value when adding or editing Participant Status.');
          break;

        case 'CRM_Mailing_BAO_Component':
          $ret['content'] = ts('Are you sure you want to disable this component?');
          break;

        case 'CRM_Core_BAO_CustomField':
          $ret['content'] = ts('Are you sure you want to disable this custom data field?');
          break;

        case 'CRM_Core_BAO_CustomGroup':
          $ret['content'] = ts('Are you sure you want to disable this custom data group? Any profile fields that are linked to custom fields of this group will be disabled.');
          break;

        case 'CRM_Core_BAO_MessageTemplate':
          $ret['content'] = ts('Are you sure you want to disable this message tempate?');
          break;

        case 'CRM_ACL_BAO_ACL':
          $ret['content'] = ts('Are you sure you want to disable this ACL?');
          break;

        case 'CRM_ACL_BAO_EntityRole':
          $ret['content'] = ts('Are you sure you want to disable this ACL Role Assignment?');
          break;

        case 'CRM_Member_BAO_MembershipType':
          $ret['content'] = ts('Are you sure you want to disable this membership type?');
          break;

        case 'CRM_Member_BAO_MembershipStatus':
          $ret['content'] = ts('Are you sure you want to disable this membership status rule?');
          break;

        case 'CRM_Price_BAO_PriceField':
          $ret['content'] = ts('Are you sure you want to disable this price field?');
          break;

        case 'CRM_Contact_BAO_Group':
          $ret['content'] = ts('Are you sure you want to disable this Group?');
          break;

        case 'CRM_Core_BAO_OptionGroup':
          $ret['content'] = ts('Are you sure you want to disable this Option?');
          break;

        case 'CRM_Contact_BAO_ContactType':
          $ret['content'] = ts('Are you sure you want to disable this Contact Type?');
          break;

        case 'CRM_Core_BAO_OptionValue':
          $label = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue', $recordID, 'label');
          $ret['content'] = ts('Are you sure you want to disable the \'%1\' option ?', array(1 => $label));
          $ret['content'] .= '<br /><br />' . ts('WARNING - Disabling an option which has been assigned to existing records will result in that option being cleared when the record is edited.');
          break;

        case 'CRM_Contribute_BAO_ContributionRecur':
          $recurDetails = CRM_Contribute_BAO_ContributionRecur::getSubscriptionDetails($recordID);
          $ret['content'] = ts('Are you sure you want to mark this recurring contribution as cancelled?');
          $ret['content'] .= '<br /><br /><strong>' . ts('WARNING - This action sets the CiviCRM recurring contribution status to Cancelled, but does NOT send a cancellation request to the payment processor. You will need to ensure that this recurring payment (subscription) is cancelled by the payment processor.') . '</strong>';
          if ($recurDetails->membership_id) {
            $ret['content'] .= '<br /><br /><strong>' . ts('This recurring contribution is linked to an auto-renew membership. If you cancel it, the associated membership will no longer renew automatically. However, the current membership status will not be affected.') . '</strong>';
          }
          break;

        default:
          $ret['content'] = ts('Are you sure you want to disable this record?');
          break;
      }
    }
    else {
      $ret = array('status' => 'error', 'content' => 'Error: Unknown entity type.', 'illegal' => TRUE);
    }
    CRM_Core_Page_AJAX::returnJsonResponse($ret);
  }

  /**
   * Get a list of mappings.
   *
   * This appears to be only used by scheduled reminders.
   */
  static public function mappingList() {
    if (empty($_GET['mappingID'])) {
      CRM_Utils_JSON::output(array('status' => 'error', 'error_msg' => 'required params missing.'));
    }

    $mapping = CRM_Core_BAO_ActionSchedule::getMapping($_GET['mappingID']);
    $dateFieldLabels = $mapping ? $mapping->getDateFields() : array();

    // The UX here is quirky -- for "Activity" types, there's a simple drop "Recipients"
    // dropdown which is always displayed. For other types, the "Recipients" drop down is
    // conditional upon the weird isLimit ('Limit To / Also Include / Neither') dropdown.
    $noThanksJustKidding = !$_GET['isLimit'];
    if ($mapping instanceof CRM_Activity_ActionMapping || !$noThanksJustKidding) {
      $entityRecipientLabels = $mapping ? ($mapping->getRecipientTypes() + CRM_Core_BAO_ActionSchedule::getAdditionalRecipients()) : array();
    }
    else {
      $entityRecipientLabels = CRM_Core_BAO_ActionSchedule::getAdditionalRecipients();
    }
    $recipientMapping = array_combine(array_keys($entityRecipientLabels), array_keys($entityRecipientLabels));

    $output = array(
      'sel4' => CRM_Utils_Array::makeNonAssociative($dateFieldLabels),
      'sel5' => CRM_Utils_Array::makeNonAssociative($entityRecipientLabels),
      'recipientMapping' => $recipientMapping,
    );

    CRM_Utils_JSON::output($output);
  }

  /**
   * (Scheduled Reminders) Get the list of possible recipient filters.
   *
   * Ex: GET /civicrm/ajax/recipientListing?mappingID=contribpage&recipientType=
   */
  public static function recipientListing() {
    $mappingID = filter_input(INPUT_GET, 'mappingID', FILTER_VALIDATE_REGEXP, array(
      'options' => array(
        'regexp' => '/^[a-zA-Z0-9_\-]+$/',
      ),
    ));
    $recipientType = filter_input(INPUT_GET, 'recipientType', FILTER_VALIDATE_REGEXP, array(
      'options' => array(
        'regexp' => '/^[a-zA-Z0-9_\-]+$/',
      ),
    ));

    CRM_Utils_JSON::output(array(
      'recipients' => CRM_Utils_Array::makeNonAssociative(CRM_Core_BAO_ActionSchedule::getRecipientListing($mappingID, $recipientType)),
    ));
  }

  /**
   * Outputs one branch in the tag tree
   *
   * Used by jstree to incrementally load tags
   */
  public static function getTagTree() {
    $parent = CRM_Utils_Type::escape(CRM_Utils_Array::value('parent_id', $_GET, 0), 'Integer');
    $substring = CRM_Utils_Type::escape(CRM_Utils_Array::value('str', $_GET), 'String');
    $result = array();

    $whereClauses = array('is_tagset <> 1');
    $orderColumn = 'name';

    // fetch all child tags in Array('parent_tag' => array('child_tag_1', 'child_tag_2', ...)) format
    $childTagIDs = CRM_Core_BAO_Tag::getChildTags($substring);
    $parentIDs = array_keys($childTagIDs);

    if ($parent) {
      $whereClauses[] = "parent_id = $parent";
    }
    elseif ($substring) {
      $whereClauses['substring'] = " name LIKE '%$substring%' ";
      if (!empty($parentIDs)) {
        $whereClauses['substring'] = sprintf(" %s OR id IN (%s) ", $whereClauses['substring'], implode(',', $parentIDs));
      }
      $orderColumn = 'id';
    }
    else {
      $whereClauses[] = "parent_id IS NULL";
    }

    $dao = CRM_Utils_SQL_Select::from('civicrm_tag')
            ->where($whereClauses)
            ->groupBy('id')
            ->orderBy($orderColumn)
            ->execute();

    while ($dao->fetch()) {
      if (!empty($substring)) {
        $result[] = $dao->id;
        if (!empty($childTagIDs[$dao->id])) {
          $result = array_merge($result, $childTagIDs[$dao->id]);
        }
      }
      else {
        $hasChildTags = empty($childTagIDs[$dao->id]) ? FALSE : TRUE;
        $usedFor = (array) explode(',', $dao->used_for);
        $tag = [
          'id' => $dao->id,
          'text' => $dao->name,
          'a_attr' => [
            'class' => 'crm-tag-item',
          ],
          'children' => $hasChildTags,
          'data' => [
            'description' => (string) $dao->description,
            'is_selectable' => (bool) $dao->is_selectable,
            'is_reserved' => (bool) $dao->is_reserved,
            'used_for' => $usedFor,
            'color' => $dao->color ? $dao->color : '#ffffff',
            'usages' => civicrm_api3('EntityTag', 'getcount', [
              'entity_table' => ['IN' => $usedFor],
              'tag_id' => $dao->id,
            ]),
          ],
        ];
        if ($dao->description || $dao->is_reserved) {
          $tag['li_attr']['title'] = ((string) $dao->description) . ($dao->is_reserved ? ' (*' . ts('Reserved') . ')' : '');
        }
        if ($dao->is_reserved) {
          $tag['li_attr']['class'] = 'is-reserved';
        }
        if ($dao->color) {
          $tag['a_attr']['style'] = "background-color: {$dao->color}; color: " . CRM_Utils_Color::getContrast($dao->color);
        }
        $result[] = $tag;
      }
    }

    if ($substring) {
      $result = array_values(array_unique($result));
    }

    if (!empty($_REQUEST['is_unit_test'])) {
      return $result;
    }

    CRM_Utils_JSON::output($result);
  }

}
