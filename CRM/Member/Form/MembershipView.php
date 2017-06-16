<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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
 * @copyright CiviCRM LLC (c) 2004-2017
 * $Id$
 *
 */

/**
 * This class generates form components for Payment-Instrument
 *
 */
class CRM_Member_Form_MembershipView extends CRM_Core_Form {

  /**
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  static $_links = NULL;

  /**
   * Add context information at the end of a link.
   *
   * @return string
   *   extra query parameters
   */
  public function addContext() {
    $extra = '';
    foreach (array('context', 'selectedChild') as $arg) {
      if ($value = CRM_Utils_Request::retrieve($arg, 'String', $this)) {
        $extra .= "&{$arg}={$value}";
      }
    }
    return $extra;
  }

  /**
   * Get action Links.
   *
   * @return array
   *   (reference) of action links
   */
  public function &links() {
    if (!(self::$_links)) {
      self::$_links = array(
        CRM_Core_Action::DELETE => array(
          'name' => ts('Delete'),
          'url' => 'civicrm/contact/view/membership',
          'qs' => 'action=view&id=%%id%%&cid=%%cid%%&relAction=delete&mid=%%mid%%&reset=1' . $this->addContext(),
          'title' => ts('Cancel Related Membership'),
        ),
        CRM_Core_Action::ADD => array(
          'name' => ts('Create'),
          'url' => 'civicrm/contact/view/membership',
          'qs' => 'action=view&id=%%id%%&cid=%%cid%%&relAction=create&rid=%%rid%%&reset=1' . $this->addContext(),
          'title' => ts('Create Related Membership'),
        ),
      );
    }
    return self::$_links;
  }

  /**
   * Perform create or delete action on related memberships.
   *
   * @param string $action
   *   Create or delete.
   * @param array $owner
   *   Primary membership info (membership_id, contact_id, membership_type ...).
   */
  public function relAction($action, $owner) {
    switch ($action) {
      case 'delete':
        $id = CRM_Utils_Request::retrieve('mid', 'Positive', $this);
        $relatedContactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this);
        $relatedDisplayName = CRM_Contact_BAO_Contact::displayName($relatedContactId);
        CRM_Member_BAO_Membership::del($id);
        CRM_Core_Session::setStatus(ts('Related membership for %1 has been deleted.', array(1 => $relatedDisplayName)),
          ts('Membership Deleted'), 'success');
        break;

      case 'create':
        $ids = array();
        $params = array(
          'contact_id' => CRM_Utils_Request::retrieve('rid', 'Positive', $this),
          'membership_type_id' => $owner['membership_type_id'],
          'owner_membership_id' => $owner['id'],
          'join_date' => CRM_Utils_Date::processDate($owner['join_date'], NULL, TRUE, 'Ymd'),
          'start_date' => CRM_Utils_Date::processDate($owner['start_date'], NULL, TRUE, 'Ymd'),
          'end_date' => CRM_Utils_Date::processDate($owner['end_date'], NULL, TRUE, 'Ymd'),
          'source' => ts('Manual Assignment of Related Membership'),
          'is_test' => $owner['is_test'],
          'campaign_id' => CRM_Utils_Array::value('campaign_id', $owner),
          'status_id' => $owner['status_id'],
          'skipStatusCal' => TRUE,
          'createActivity' => TRUE,
        );
        CRM_Member_BAO_Membership::create($params, $ids);
        $relatedDisplayName = CRM_Contact_BAO_Contact::displayName($params['contact_id']);
        CRM_Core_Session::setStatus(ts('Related membership for %1 has been created.', array(1 => $relatedDisplayName)),
          ts('Membership Added'), 'success');
        break;

      default:
        CRM_Core_Error::fatal(ts("Invalid action specified in URL"));
    }

    // Redirect back to membership view page for the owner, without the relAction parameters
    CRM_Utils_System::redirect(
      CRM_Utils_System::url(
        'civicrm/contact/view/membership',
        "action=view&reset=1&id={$owner['membership_id']}&cid={$owner['contact_id']}" . $this->addContext()
      )
    );
  }

  /**
   * Set variables up before form is built.
   *
   * @return void
   */
  public function preProcess() {

    $values = array();
    $id = CRM_Utils_Request::retrieve('id', 'Positive', $this);

    // Make sure context is assigned to template for condition where we come here view civicrm/membership/view
    $context = CRM_Utils_Request::retrieve('context', 'String', $this);
    $this->assign('context', $context);

    if ($id) {
      $params = array('id' => $id);
      CRM_Member_BAO_Membership::retrieve($params, $values);
      if (CRM_Financial_BAO_FinancialType::isACLFinancialTypeStatus()) {
        $finTypeId = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType', $values['membership_type_id'], 'financial_type_id');
        $finType = CRM_Contribute_PseudoConstant::financialType($finTypeId);
        if (!CRM_Core_Permission::check('view contributions of type ' . $finType)) {
          CRM_Core_Error::fatal(ts('You do not have permission to access this page.'));
        }
      }
      else {
        $this->assign('noACL', TRUE);
      }
      $membershipType = CRM_Member_BAO_MembershipType::getMembershipTypeDetails($values['membership_type_id']);

      // Do the action on related Membership if needed
      $relAction = CRM_Utils_Request::retrieve('relAction', 'String', $this);
      if ($relAction) {
        $this->relAction($relAction, $values);
      }

      // build associated contributions
      $this->assign('accessContribution', FALSE);
      if (CRM_Core_Permission::access('CiviContribute')) {
        $this->assign('accessContribution', TRUE);
        CRM_Member_Page_Tab::associatedContribution($values['contact_id'], $id);
      }

      //Provide information about membership source when it is the result of a relationship (CRM-1901)
      $values['owner_membership_id'] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership',
        $id,
        'owner_membership_id'
      );

      if (isset($values['owner_membership_id'])) {
        $values['owner_contact_id'] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership',
          $values['owner_membership_id'],
          'contact_id',
          'id'
        );

        $values['owner_display_name'] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
          $values['owner_contact_id'],
          'display_name',
          'id'
        );

        $direction = strrev($membershipType['relationship_direction']);
        // To display relationship type in view membership page
        $relTypeIds = str_replace(CRM_Core_DAO::VALUE_SEPARATOR, ",", $membershipType['relationship_type_id']);
        $sql = "
SELECT relationship_type_id,
  CASE
  WHEN  contact_id_a = {$values['owner_contact_id']} AND contact_id_b = {$values['contact_id']} THEN 'b_a'
  WHEN  contact_id_b = {$values['owner_contact_id']} AND contact_id_a = {$values['contact_id']} THEN 'a_b'
END AS 'relType'
  FROM civicrm_relationship
 WHERE relationship_type_id IN ($relTypeIds)";
        $dao = CRM_Core_DAO::executeQuery($sql);
        $values['relationship'] = NULL;
        while ($dao->fetch()) {
          $typeId = $dao->relationship_type_id;
          $direction = $dao->relType;
          if ($direction && $typeId) {
            if ($values['relationship']) {
              $values['relationship'] .= ',';
            }
            $values['relationship'] .= CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_RelationshipType',
              $typeId,
              "name_$direction",
              'id'
            );
          }
        }
      }

      $this->assign('has_related', FALSE);
      // if membership can be granted, and we are the owner of the membership
      if (!empty($membershipType['relationship_type_id']) && empty($values['owner_membership_id'])) {
        // display related contacts/membership block
        $this->assign('has_related', TRUE);
        $this->assign('max_related', CRM_Utils_Array::value('max_related', $values, ts('Unlimited')));
        // split the relations in 2 arrays based on direction
        $relTypeId = explode(CRM_Core_DAO::VALUE_SEPARATOR, $membershipType['relationship_type_id']);
        $relDirection = explode(CRM_Core_DAO::VALUE_SEPARATOR, $membershipType['relationship_direction']);
        foreach ($relTypeId as $rid) {
          $dir = each($relDirection);
          $relTypeDir[substr($dir['value'], 0, 1)][] = $rid;
        }
        // build query in 2 parts with a UNION if necessary
        // _x and _y are replaced with _a and _b first, then vice-versa
        // comment is a qualifier for the relationship - now just job_title
        $select = "
SELECT r.id, c.id as cid, c.display_name as name, c.job_title as comment,
       rt.name_x_y as relation, r.start_date, r.end_date,
       m.id as mid, ms.is_current_member, ms.label as status
  FROM civicrm_relationship r
  LEFT JOIN civicrm_relationship_type rt ON rt.id = r.relationship_type_id
  LEFT JOIN civicrm_contact c ON c.id = r.contact_id_x
  LEFT JOIN civicrm_membership m ON (m.owner_membership_id = {$values['id']}
  AND m.contact_id = r.contact_id_x AND m.is_test = 0)
  LEFT JOIN civicrm_membership_status ms ON ms.id = m.status_id
 WHERE r.contact_id_y = {$values['contact_id']} AND r.is_active = 1  AND c.is_deleted = 0";
        $query = '';
        foreach (array('a', 'b') as $dir) {
          if (isset($relTypeDir[$dir])) {
            $query .= ($query ? ' UNION ' : '')
              . str_replace('_y', '_' . $dir, str_replace('_x', '_' . ($dir == 'a' ? 'b' : 'a'), $select))
              . ' AND r.relationship_type_id IN (' . implode(',', $relTypeDir[$dir]) . ')';
          }
        }
        $query .= " ORDER BY is_current_member DESC";
        $dao = CRM_Core_DAO::executeQuery($query);
        $related = array();
        $relatedRemaining = CRM_Utils_Array::value('max_related', $values, PHP_INT_MAX);
        $rowElememts = array(
          'id',
          'cid',
          'name',
          'comment',
          'relation',
          'mid',
          'start_date',
          'end_date',
          'is_current_member',
          'status',
        );

        while ($dao->fetch()) {
          $row = array();
          foreach ($rowElememts as $field) {
            $row[$field] = $dao->$field;
          }
          if ($row['mid'] && ($row['is_current_member'] == 1)) {
            $relatedRemaining--;
            $row['action'] = CRM_Core_Action::formLink(self::links(), CRM_Core_Action::DELETE,
              array(
                'id' => CRM_Utils_Request::retrieve('id', 'Positive', $this),
                'cid' => $row['cid'],
                'mid' => $row['mid'],
              ),
              ts('more'),
              FALSE,
              'membership.relationship.action',
              'Relationship',
              CRM_Utils_Request::retrieve('id', 'Positive', $this)
            );
          }
          else {
            if ($relatedRemaining > 0) {
              $row['action'] = CRM_Core_Action::formLink(self::links(), CRM_Core_Action::ADD,
                array(
                  'id' => CRM_Utils_Request::retrieve('id', 'Positive', $this),
                  'cid' => $row['cid'],
                  'rid' => $row['cid'],
                ),
                ts('more'),
                FALSE,
                'membership.relationship.action',
                'Relationship',
                CRM_Utils_Request::retrieve('id', 'Positive', $this)
              );
            }
          }
          $related[] = $row;
        }
        $this->assign('related', $related);
        if ($relatedRemaining <= 0) {
          $this->assign('related_text', ts('None available'));
        }
        else {
          if ($relatedRemaining < 100000) {
            $this->assign('related_text', ts('%1 available', array(1 => $relatedRemaining)));
          }
          else {
            $this->assign('related_text', ts('Unlimited', array(1 => $relatedRemaining)));
          }
        }
      }

      $displayName = CRM_Contact_BAO_Contact::displayName($values['contact_id']);
      $this->assign('displayName', $displayName);

      // Check if this is default domain contact CRM-10482
      if (CRM_Contact_BAO_Contact::checkDomainContact($values['contact_id'])) {
        $displayName .= ' (' . ts('default organization') . ')';
      }

      // omitting contactImage from title for now since the summary overlay css doesn't work outside crm-container
      CRM_Utils_System::setTitle(ts('View Membership for') . ' ' . $displayName);

      // add viewed membership to recent items list
      $recentTitle = $displayName . ' - ' . ts('Membership Type:') . ' ' . $values['membership_type'];
      $url = CRM_Utils_System::url('civicrm/contact/view/membership',
        "action=view&reset=1&id={$values['id']}&cid={$values['contact_id']}&context=home"
      );

      $recentOther = array();
      if (CRM_Core_Permission::checkActionPermission('CiviMember', CRM_Core_Action::UPDATE)) {
        $recentOther['editUrl'] = CRM_Utils_System::url('civicrm/contact/view/membership',
          "action=update&reset=1&id={$values['id']}&cid={$values['contact_id']}&context=home"
        );
      }
      if (CRM_Core_Permission::checkActionPermission('CiviMember', CRM_Core_Action::DELETE)) {
        $recentOther['deleteUrl'] = CRM_Utils_System::url('civicrm/contact/view/membership',
          "action=delete&reset=1&id={$values['id']}&cid={$values['contact_id']}&context=home"
        );
      }
      CRM_Utils_Recent::add($recentTitle,
        $url,
        $values['id'],
        'Membership',
        $values['contact_id'],
        NULL,
        $recentOther
      );

      CRM_Member_Page_Tab::setContext($this, $values['contact_id']);

      $memType = CRM_Core_DAO::getFieldValue("CRM_Member_DAO_Membership", $id, "membership_type_id");

      $groupTree = CRM_Core_BAO_CustomGroup::getTree('Membership', NULL, $id, 0, $memType);
      CRM_Core_BAO_CustomGroup::buildCustomDataView($this, $groupTree, FALSE, NULL, NULL, NULL, $id);

      $isRecur = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership', $id, 'contribution_recur_id');

      $autoRenew = $isRecur ? TRUE : FALSE;
    }

    if (!empty($values['is_test'])) {
      $values['membership_type'] .= ' (test) ';
    }

    $subscriptionCancelled = CRM_Member_BAO_Membership::isSubscriptionCancelled($id);
    $values['auto_renew'] = ($autoRenew && !$subscriptionCancelled) ? 'Yes' : 'No';

    //do check for campaigns
    if ($campaignId = CRM_Utils_Array::value('campaign_id', $values)) {
      $campaigns = CRM_Campaign_BAO_Campaign::getCampaigns($campaignId);
      $values['campaign'] = $campaigns[$campaignId];
    }

    $this->assign($values);
  }

  /**
   * Build the form object.
   *
   * @return void
   */
  public function buildQuickForm() {
    $this->addButtons(array(
      array(
        'type' => 'cancel',
        'name' => ts('Done'),
        'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
        'isDefault' => TRUE,
      ),
    ));
  }

}
