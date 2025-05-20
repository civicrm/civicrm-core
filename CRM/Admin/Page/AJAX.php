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
 * This class contains all the function that are called using AJAX.
 */
class CRM_Admin_Page_AJAX {

  /**
   * Outputs menubar data (json format) for the current user.
   */
  public static function navMenu() {
    CRM_Core_Page_AJAX::validateAjaxRequestMethod();
    if (CRM_Core_Session::getLoggedInContactID()) {

      $menu = CRM_Core_BAO_Navigation::buildNavigationTree();
      CRM_Core_BAO_Navigation::buildHomeMenu($menu);
      CRM_Utils_Hook::navigationMenu($menu);
      CRM_Core_BAO_Navigation::fixNavigationMenu($menu);
      CRM_Core_BAO_Navigation::orderByWeight($menu);
      CRM_Core_BAO_Navigation::filterByPermission($menu);
      self::formatMenuItems($menu);

      $output = [
        'menu' => $menu,
        'search' => self::getSearchOptions(),
      ];
      // Encourage browsers to cache for a long time - 1 year
      $ttl = 60 * 60 * 24 * 364;
      CRM_Utils_System::setHttpHeader('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + $ttl));
      CRM_Utils_System::setHttpHeader('Cache-Control', "max-age=$ttl, public");
      CRM_Utils_System::setHttpHeader('Content-Type', 'application/json');
      print (json_encode($output));
    }
    CRM_Utils_System::civiExit();
  }

  /**
   * @param array $menu
   */
  public static function formatMenuItems(&$menu) {
    foreach ($menu as $key => &$item) {
      $props = $item['attributes'];
      unset($item['attributes']);
      if (!empty($props['separator'])) {
        $item['separator'] = ($props['separator'] == 1 ? 'bottom' : 'top');
      }
      if (!empty($props['icon'])) {
        $item['icon'] = $props['icon'];
      }
      if (!empty($props['attr'])) {
        $item['attr'] = $props['attr'];
      }
      if (!empty($props['url'])) {
        $item['url'] = CRM_Utils_System::evalUrl(CRM_Core_BAO_Navigation::makeFullyFormedUrl($props['url']));
      }
      if (!empty($props['label'])) {
        $item['label'] = _ts($props['label'], ['context' => 'menu']);
      }
      $item['name'] = !empty($props['name']) ? $props['name'] : CRM_Utils_String::munge($props['label'] ?? '');
      if (!empty($item['child'])) {
        self::formatMenuItems($item['child']);
      }
    }
    $menu = array_values($menu);
  }

  public static function getSearchOptions() {
    $searchOptions = Civi::settings()->get('quicksearch_options');
    $allOptions = array_column(CRM_Core_SelectValues::getQuicksearchOptions(), NULL, 'key');
    $result = [];
    foreach ($searchOptions as $key) {
      $result[] = [
        'key' => $key,
        'value' => $allOptions[$key]['label'],
        'adv_search_legacy' => $allOptions[$key]['adv_search_legacy'] ?? '',
      ];
    }
    return $result;
  }

  /**
   * Process drag/move action for menu tree.
   */
  public static function menuTree() {
    CRM_Core_Page_AJAX::validateAjaxRequestMethod();
    CRM_Core_BAO_Navigation::processNavigation($_GET);
  }

  /**
   * Build status message while enabling/ disabling various objects.
   */
  public static function getStatusMsg() {
    CRM_Core_Page_AJAX::validateAjaxRequestMethod();
    require_once 'api/v3/utils.php';
    $recordID = CRM_Utils_Type::escape($_GET['id'], 'Integer');
    $entity = CRM_Utils_Type::escape($_GET['entity'], 'String');
    $ret = [];

    if ($recordID && $entity && $recordBAO = _civicrm_api3_get_BAO($entity)) {
      switch ($recordBAO) {
        case 'CRM_Core_BAO_UFGroup':
          $method = 'getUFJoinRecord';
          $result = [$recordBAO, $method];
          $ufJoin = call_user_func_array(($result), [$recordID, TRUE]);
          if (!empty($ufJoin)) {
            $ret['content'] = ts('This profile is currently used for %1.', [1 => implode(', ', $ufJoin)]) . ' <br/><br/>' . ts('If you disable the profile - it will be removed from these forms and/or modules. Do you want to continue?');
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
            $comps = [
              'Event' => 'civicrm_event',
              'Contribution' => 'civicrm_contribution_page',
              'EventTemplate' => 'civicrm_event_template',
            ];
            $contexts = [];
            foreach ($comps as $name => $table) {
              if (array_key_exists($table, $usedBy)) {
                $contexts[] = $name;
              }
            }
            $template->assign('contexts', $contexts);

            $ret['illegal'] = TRUE;
            $table = $template->fetch('CRM/Price/Page/table.tpl');
            $ret['content'] = ts('Unable to disable the \'%1\' price set - it is currently in use by one or more active events, contribution pages or contributions.', [
              1 => $priceSet,
            ]) . "<br/> $table";
          }
          else {
            $ret['content'] = ts('Are you sure you want to disable \'%1\' Price Set?', [1 => $priceSet]);
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

        case 'CRM_Mailing_BAO_MailingComponent':
          $ret['content'] = ts('Are you sure you want to disable this component?');
          break;

        case 'CRM_Core_BAO_CustomField':
          $ret['content'] = ts('Are you sure you want to disable this custom data field?');
          break;

        case 'CRM_Core_BAO_CustomGroup':
          $ret['content'] = ts('Are you sure you want to disable this custom data group? Any profile fields that are linked to custom fields of this group will be disabled.');
          break;

        case 'CRM_Core_BAO_MessageTemplate':
          $ret['content'] = ts('Are you sure you want to disable this message template?');
          break;

        case 'CRM_ACL_BAO_ACL':
          $ret['content'] = ts('Are you sure you want to disable this ACL?');
          break;

        case 'CRM_ACL_BAO_ACLEntityRole':
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
          $sgContent = '';
          $sgReferencingThisGroup = CRM_Contact_BAO_SavedSearch::getSmartGroupsUsingGroup($recordID);
          if (!empty($sgReferencingThisGroup)) {
            $sgContent .= '<br /><br /><strong>' . ts('WARNING - This Group is currently referenced by %1 smart group(s).', [
              1 => count($sgReferencingThisGroup),
            ]) . '</strong><ul>';
            foreach ($sgReferencingThisGroup as $gid => $group) {
              $sgContent .= '<li>' . ts('%1 <a class="action-item crm-hover-button" href="%2" target="_blank">Edit Smart Group Criteria</a>', [
                1 => $group['title'],
                2 => $group['editSearchURL'],
              ]) . '</li>';
            }
            $sgContent .= '</ul>' . ts('Disabling this group will cause these groups to no longer restrict members based on membership in this group. Please edit and remove this group as a criteria from these smart groups.');
          }
          $ret['content'] .= $sgContent . '<br /><br /><strong>' . ts('WARNING - Disabling this group will disable all the child groups associated if any.') . '</strong>';
          break;

        case 'CRM_Core_BAO_OptionGroup':
          $ret['content'] = ts('Are you sure you want to disable this Option?');
          break;

        case 'CRM_Contact_BAO_ContactType':
          $ret['content'] = ts('Are you sure you want to disable this Contact Type?');
          break;

        case 'CRM_Core_BAO_OptionValue':
          $label = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue', $recordID, 'label');
          $ret['content'] = ts('Are you sure you want to disable the \'%1\' option ?', [1 => $label]);
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
      $ret = ['status' => 'error', 'content' => 'Error: Unknown entity type.', 'illegal' => TRUE];
    }
    CRM_Core_Page_AJAX::returnJsonResponse($ret);
  }

  /**
   * Outputs one branch in the tag tree
   *
   * Used by jstree to incrementally load tags
   */
  public static function getTagTree() {
    CRM_Core_Page_AJAX::validateAjaxRequestMethod();
    $parent = CRM_Utils_Type::escape(($_GET['parent_id'] ?? 0), 'Integer');
    $substring = CRM_Utils_Type::escape($_GET['str'] ?? NULL, 'String');
    $result = [];

    $whereClauses = ['is_tagset <> 1'];
    $orderColumn = 'label';

    // fetch all child tags in Array('parent_tag' => array('child_tag_1', 'child_tag_2', ...)) format
    $childTagIDs = CRM_Core_BAO_Tag::getChildTags($substring);
    $parentIDs = array_keys($childTagIDs);

    if ($parent) {
      $whereClauses[] = "parent_id = $parent";
    }
    elseif ($substring) {
      $whereClauses['substring'] = " label LIKE '%$substring%' ";
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
        $hasChildTags = !empty($childTagIDs[$dao->id]);
        $usedFor = (array) explode(',', $dao->used_for);
        $tag = [
          'id' => $dao->id,
          'text' => $dao->label,
          'a_attr' => [
            'class' => 'crm-tag-item',
          ],
          'children' => $hasChildTags,
          'data' => [
            'description' => (string) $dao->description,
            'is_selectable' => (bool) $dao->is_selectable,
            'is_reserved' => (bool) $dao->is_reserved,
            'used_for' => $usedFor,
            'color' => $dao->color ?: '#ffffff',
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
