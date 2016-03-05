<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
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
   * Return menu tree as json data for editing.
   */
  public static function getNavigationList() {
    echo CRM_Core_BAO_Navigation::buildNavigation(TRUE, FALSE);
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

        case 'CRM_Contribute_BAO_ManagePremiums':
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

  public static function mergeTagList() {
    $name = CRM_Utils_Type::escape($_GET['term'], 'String');
    $fromId = CRM_Utils_Type::escape($_GET['fromId'], 'Integer');
    $limit = Civi::settings()->get('search_autocomplete_count');

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
    $result = array();

    while ($dao->fetch()) {
      $row = array(
        'id' => $dao->id,
        'text' => ($dao->parent ? "{$dao->parent} :: " : '') . $dao->name,
      );
      // Add warning about used_for types
      if (!empty($dao->used_for)) {
        $usedForTagB = explode(',', $dao->used_for);
        sort($usedForTagB);
        $usedForDiff = array_diff($usedForTagA, $usedForTagB);
        if (!empty($usedForDiff)) {
          $row['warning'] = TRUE;
        }
      }
      $result[] = $row;
    }
    CRM_Utils_JSON::output($result);
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
      'sel4' => CRM_Utils_Array::toKeyValueRows($dateFieldLabels),
      'sel5' => CRM_Utils_Array::toKeyValueRows($entityRecipientLabels),
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
      'recipients' => CRM_Utils_Array::toKeyValueRows(CRM_Core_BAO_ActionSchedule::getRecipientListing($mappingID, $recipientType)),
    ));
  }

  public static function mergeTags() {
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

    $result['message'] = ts('"%1" has been merged with "%2". All records previously tagged "%1" are now tagged "%2".',
      array(1 => $result['tagA'], 2 => $result['tagB'])
    );

    CRM_Utils_JSON::output($result);
  }

}
