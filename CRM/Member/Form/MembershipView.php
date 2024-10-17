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

use Civi\Api4\Membership;

/**
 * This class generates form components for Payment-Instrument
 */
class CRM_Member_Form_MembershipView extends CRM_Core_Form {

  /**
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  public static $_links = NULL;

  /**
   * The id of the membership being viewed.
   *
   * @var int
   */
  private $membershipID;

  /**
   * Contact's ID.
   *
   * @var int
   */
  private $contactID;

  /**
   * Add context information at the end of a link.
   *
   * @return string
   *   extra query parameters
   */
  public function addContext() {
    $extra = '';
    foreach (['context', 'selectedChild'] as $arg) {
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
      self::$_links = [
        CRM_Core_Action::DELETE => [
          'name' => ts('Delete'),
          'url' => 'civicrm/contact/view/membership',
          'qs' => 'action=view&id=%%id%%&cid=%%cid%%&relAction=delete&mid=%%mid%%&reset=1' . $this->addContext(),
          'title' => ts('Cancel Related Membership'),
        ],
        CRM_Core_Action::ADD => [
          'name' => ts('Create'),
          'url' => 'civicrm/contact/view/membership',
          'qs' => 'action=view&id=%%id%%&cid=%%cid%%&relAction=create&rid=%%rid%%&reset=1' . $this->addContext(),
          'title' => ts('Create Related Membership'),
        ],
      ];
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
   *
   * @throws \CRM_Core_Exception
   */
  public function relAction($action, $owner) {
    switch ($action) {
      case 'delete':
        $id = CRM_Utils_Request::retrieve('mid', 'Positive', $this);
        $relatedContactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this);
        $relatedDisplayName = CRM_Contact_BAO_Contact::displayName($relatedContactId);
        CRM_Member_BAO_Membership::del($id);
        CRM_Core_Session::setStatus(ts('Related membership for %1 has been deleted.', [1 => $relatedDisplayName]),
          ts('Membership Deleted'), 'success');
        break;

      case 'create':
        $params = [
          'contact_id' => CRM_Utils_Request::retrieve('rid', 'Positive', $this),
          'membership_type_id' => $owner['membership_type_id'],
          'owner_membership_id' => $owner['id'],
          'join_date' => CRM_Utils_Date::processDate($owner['join_date'], NULL, TRUE, 'Ymd'),
          'start_date' => CRM_Utils_Date::processDate($owner['start_date'], NULL, TRUE, 'Ymd'),
          'end_date' => CRM_Utils_Date::processDate($owner['end_date'], NULL, TRUE, 'Ymd'),
          'source' => ts('Manual Assignment of Related Membership'),
          'is_test' => $owner['is_test'],
          'campaign_id' => $owner['campaign_id'] ?? NULL,
          'status_id' => $owner['status_id'],
          'skipStatusCal' => TRUE,
          'createActivity' => TRUE,
        ];
        CRM_Member_BAO_Membership::create($params);
        $relatedDisplayName = CRM_Contact_BAO_Contact::displayName($params['contact_id']);
        CRM_Core_Session::setStatus(ts('Related membership for %1 has been created.', [1 => $relatedDisplayName]),
          ts('Membership Added'), 'success');
        break;

      default:
        throw new CRM_Core_Exception(ts('Invalid action specified in URL'));
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
   * @throws \Civi\Core\Exception\DBQueryException
   */
  public function preProcess() {
    $values = [];
    $this->membershipID = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    $this->contactID = CRM_Utils_Request::retrieve('cid', 'Positive', $this);

    // Make sure context is assigned to template for condition where we come here view civicrm/membership/view
    $context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $this);
    $this->assign('context', $context);

    if ($this->membershipID) {
      $memberships = Membership::get()
        ->addSelect('*', 'status_id:label', 'membership_type_id:label', 'membership_type_id.financial_type_id', 'status_id.is_current_member')
        ->addWhere('id', '=', $this->membershipID)
        ->execute();
      if (!count($memberships)) {
        CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
      }
      $values = $memberships->first();

      // Ensure keys expected by MembershipView.tpl are set correctly
      // Some of these defaults are overwritten dependant on context below
      $values['financialTypeId'] = $values['membership_type_id.financial_type_id'];
      $values['membership_type'] = $values['membership_type_id:label'];
      $values['status'] = $values['status_id:label'];
      $values['active'] = $values['status_id.is_current_member'];
      $values['owner_contact_id'] = FALSE;
      $values['owner_display_name'] = FALSE;
      $values['campaign'] = FALSE;

      // This tells the template not to check financial acls when determining
      // whether to show edit & delete links. Link decisions
      // should be moved to the php layer - with financialacls using hooks.
      $this->assign('noACL', !CRM_Financial_BAO_FinancialType::isACLFinancialTypeStatus());

      $membershipType = \Civi\Api4\MembershipType::get(FALSE)
        ->addSelect('relationship_direction', 'relationship_type_id')
        ->addWhere('id', '=', $values['membership_type_id'])
        ->execute()
        ->first();

      // Do the action on related Membership if needed
      $relAction = CRM_Utils_Request::retrieve('relAction', 'String', $this);
      if ($relAction) {
        $this->relAction($relAction, $values);
      }

      // build associated contributions
      $this->assign('accessContribution', FALSE);
      if (CRM_Core_Permission::access('CiviContribute')) {
        $this->assign('accessContribution', TRUE);
        CRM_Member_Page_Tab::associatedContribution($values['contact_id'], $this->membershipID);
      }

      //Provide information about membership source when it is the result of a relationship (CRM-1901)
      $values['owner_membership_id'] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership',
        $this->membershipID,
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

        // To display relationship type in view membership page
        $sql = "
SELECT relationship_type_id,
  CASE
  WHEN  contact_id_a = {$values['owner_contact_id']} AND contact_id_b = {$values['contact_id']} THEN 'b_a'
  WHEN  contact_id_b = {$values['owner_contact_id']} AND contact_id_a = {$values['contact_id']} THEN 'a_b'
END AS 'relType'
  FROM civicrm_relationship
 WHERE relationship_type_id IN (" . implode(',', $membershipType['relationship_type_id']) . ")";
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
        $this->assign('max_related', $values['max_related'] ?? ts('Unlimited'));
        // split the relations in 2 arrays based on direction
        foreach ($membershipType['relationship_type_id'] as $x => $rid) {
          $relTypeDir[substr($membershipType['relationship_direction'][$x], 0, 1)][] = $rid;
        }
        // build query in 2 parts with a UNION if necessary
        // _x and _y are replaced with _a and _b first, then vice-versa
        // comment is a qualifier for the relationship - now just job_title
        $select = "
SELECT r.id, c.id as cid, c.display_name as name, c.job_title as comment,
       rt.label_x_y as relation, r.start_date, r.end_date,
       m.id as mid, ms.is_current_member, ms.label as status
  FROM civicrm_relationship r
  LEFT JOIN civicrm_relationship_type rt ON rt.id = r.relationship_type_id
  LEFT JOIN civicrm_contact c ON c.id = r.contact_id_x
  LEFT JOIN civicrm_membership m ON (m.owner_membership_id = {$values['id']}
  AND m.contact_id = r.contact_id_x AND m.is_test = 0)
  LEFT JOIN civicrm_membership_status ms ON ms.id = m.status_id
 WHERE r.contact_id_y = {$values['contact_id']} AND r.is_active = 1  AND c.is_deleted = 0";
        $query = '';
        foreach (['a', 'b'] as $dir) {
          if (isset($relTypeDir[$dir])) {
            $query .= ($query ? ' UNION ' : '')
              . str_replace('_y', '_' . $dir, str_replace('_x', '_' . ($dir == 'a' ? 'b' : 'a'), $select))
              . ' AND r.relationship_type_id IN (' . implode(',', $relTypeDir[$dir]) . ')';
          }
        }
        $query .= " ORDER BY is_current_member DESC";
        $dao = CRM_Core_DAO::executeQuery($query);
        $related = [];
        $relatedRemaining = $values['max_related'] ?? PHP_INT_MAX;
        $rowElememts = [
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
        ];

        while ($dao->fetch()) {
          $row = [];
          foreach ($rowElememts as $field) {
            $row[$field] = $dao->$field;
          }
          if ($row['mid'] && ($row['is_current_member'] == 1)) {
            $relatedRemaining--;
            $row['action'] = CRM_Core_Action::formLink(self::links(), CRM_Core_Action::DELETE,
              [
                'id' => CRM_Utils_Request::retrieve('id', 'Positive', $this),
                'cid' => $row['cid'],
                'mid' => $row['mid'],
              ],
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
                [
                  'id' => CRM_Utils_Request::retrieve('id', 'Positive', $this),
                  'cid' => $row['cid'],
                  'rid' => $row['cid'],
                ],
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
            $this->assign('related_text', ts('%1 available', [1 => $relatedRemaining]));
          }
          else {
            $this->assign('related_text', ts('Unlimited', [1 => $relatedRemaining]));
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
      $this->setTitle(ts('View Membership for') . ' ' . $displayName);

      // add viewed membership to recent items list
      $recentTitle = $displayName . ' - ' . ts('Membership Type:') . ' ' . $values['membership_type'];
      $url = CRM_Utils_System::url('civicrm/contact/view/membership',
        "action=view&reset=1&id={$values['id']}&cid={$values['contact_id']}&context=home"
      );

      $recentOther = [];
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

      $memType = CRM_Core_DAO::getFieldValue("CRM_Member_DAO_Membership", $this->membershipID, "membership_type_id");

      $groupTree = CRM_Core_BAO_CustomGroup::getTree('Membership', NULL, $this->membershipID, 0, $memType, NULL,
        TRUE, NULL, FALSE, CRM_Core_Permission::VIEW);
      CRM_Core_BAO_CustomGroup::buildCustomDataView($this, $groupTree, FALSE, NULL, NULL, NULL, $this->membershipID);

      $isRecur = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership', $this->membershipID, 'contribution_recur_id');

      $autoRenew = (bool) $isRecur;
    }

    if (!empty($values['is_test'])) {
      $values['membership_type'] = CRM_Core_TestEntity::appendTestText($values['membership_type']);
    }

    $subscriptionCancelled = CRM_Member_BAO_Membership::isSubscriptionCancelled((int) $this->membershipID);
    $values['auto_renew'] = ($autoRenew && !$subscriptionCancelled) ? 'Yes' : 'No';

    //do check for campaigns
    $campaignId = $values['campaign_id'] ?? NULL;
    if ($campaignId) {
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
    $this->addButtons([
      [
        'type' => 'cancel',
        'name' => ts('Done'),
        'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
        'isDefault' => TRUE,
      ],
    ]);
  }

}
