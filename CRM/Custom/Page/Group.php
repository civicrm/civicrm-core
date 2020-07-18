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
 * Create a page for displaying Custom Sets.
 *
 * Heart of this class is the run method which checks
 * for action type and then displays the appropriate
 * page.
 *
 */
class CRM_Custom_Page_Group extends CRM_Core_Page {

  /**
   * The action links that we need to display for the browse screen
   *
   * @var array
   */
  private static $_actionLinks;

  /**
   * Get the action links for this page.
   *
   *
   * @return array
   *   array of action links that we need to display for the browse screen
   */
  public static function &actionLinks() {
    // check if variable _actionsLinks is populated
    if (!isset(self::$_actionLinks)) {
      self::$_actionLinks = [
        CRM_Core_Action::BROWSE => [
          'name' => ts('View and Edit Custom Fields'),
          'url' => 'civicrm/admin/custom/group/field',
          'qs' => 'reset=1&action=browse&gid=%%id%%',
          'title' => ts('View and Edit Custom Fields'),
        ],
        CRM_Core_Action::PREVIEW => [
          'name' => ts('Preview'),
          'url' => 'civicrm/admin/custom/group',
          'qs' => 'action=preview&reset=1&id=%%id%%',
          'title' => ts('Preview Custom Data Set'),
        ],
        CRM_Core_Action::UPDATE => [
          'name' => ts('Settings'),
          'url' => 'civicrm/admin/custom/group',
          'qs' => 'action=update&reset=1&id=%%id%%',
          'title' => ts('Edit Custom Set'),
        ],
        CRM_Core_Action::DISABLE => [
          'name' => ts('Disable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Disable Custom Set'),
        ],
        CRM_Core_Action::ENABLE => [
          'name' => ts('Enable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Enable Custom Set'),
        ],
        CRM_Core_Action::DELETE => [
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/custom/group',
          'qs' => 'action=delete&reset=1&id=%%id%%',
          'title' => ts('Delete Custom Set'),
        ],
      ];
    }
    return self::$_actionLinks;
  }

  /**
   * Run the page.
   *
   * This method is called after the page is created. It checks for the
   * type of action and executes that action.
   * Finally it calls the parent's run method.
   *
   * @return void
   */
  public function run() {
    // get the requested action
    $action = CRM_Utils_Request::retrieve('action', 'String',
      // default to 'browse'
      $this, FALSE, 'browse'
    );

    if ($action & CRM_Core_Action::DELETE) {
      $session = CRM_Core_Session::singleton();
      $session->pushUserContext(CRM_Utils_System::url('civicrm/admin/custom/group/', 'action=browse'));
      $controller = new CRM_Core_Controller_Simple('CRM_Custom_Form_DeleteGroup', "Delete Cutom Set", NULL);
      $id = CRM_Utils_Request::retrieve('id', 'Positive',
        $this, FALSE, 0
      );
      $controller->set('id', $id);
      $controller->setEmbedded(TRUE);
      $controller->process();
      $controller->run();
    }
    // assign vars to templates
    $this->assign('action', $action);
    $id = CRM_Utils_Request::retrieve('id', 'Positive',
      $this, FALSE, 0
    );

    // what action to take ?
    if ($action & (CRM_Core_Action::UPDATE | CRM_Core_Action::ADD)) {
      $this->edit($id, $action);
    }
    elseif ($action & CRM_Core_Action::PREVIEW) {
      $this->preview($id);
    }
    else {
      // finally browse the custom groups
      $this->browse();
    }
    // parent run
    return parent::run();
  }

  /**
   * Edit custom group.
   *
   * @param int $id
   *   Custom group id.
   * @param string $action
   *   The action to be invoked.
   *
   * @return void
   */
  public function edit($id, $action) {
    // create a simple controller for editing custom data
    $controller = new CRM_Core_Controller_Simple('CRM_Custom_Form_Group', ts('Custom Set'), $action);

    // set the userContext stack
    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url('civicrm/admin/custom/group/', 'action=browse'));
    $controller->set('id', $id);
    $controller->setEmbedded(TRUE);
    $controller->process();
    $controller->run();
  }

  /**
   * Preview custom group.
   *
   * @param int $id
   *   Custom group id.
   *
   * @return void
   */
  public function preview($id) {
    $controller = new CRM_Core_Controller_Simple('CRM_Custom_Form_Preview', ts('Preview Custom Data'), NULL);
    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url('civicrm/admin/custom/group', 'action=browse'));
    $controller->set('groupId', $id);
    $controller->setEmbedded(TRUE);
    $controller->process();
    $controller->run();
  }

  /**
   * Browse all custom data groups.
   *
   * @param string $action
   *   The action to be invoked.
   *
   * @return void
   */
  public function browse($action = NULL) {
    // get all custom groups sorted by weight
    $customGroup = [];
    $dao = new CRM_Core_DAO_CustomGroup();
    $dao->is_reserved = FALSE;
    $dao->orderBy('weight, title');
    $dao->find();

    $customGroupExtends = CRM_Core_SelectValues::customGroupExtends();
    $customGroupStyle = CRM_Core_SelectValues::customGroupStyle();
    while ($dao->fetch()) {
      $id = $dao->id;
      $customGroup[$id] = [];
      CRM_Core_DAO::storeValues($dao, $customGroup[$id]);
      // form all action links
      $action = array_sum(array_keys(self::actionLinks()));

      // update enable/disable links depending on custom_group properties.
      if ($dao->is_active) {
        $action -= CRM_Core_Action::ENABLE;
      }
      else {
        $action -= CRM_Core_Action::DISABLE;
      }
      $customGroup[$id]['order'] = $customGroup[$id]['weight'];
      $customGroup[$id]['action'] = CRM_Core_Action::formLink(self::actionLinks(), $action,
        ['id' => $id],
        ts('more'),
        FALSE,
        'customGroup.row.actions',
        'CustomGroup',
        $id
      );
      if (!empty($customGroup[$id]['style'])) {
        $customGroup[$id]['style_display'] = $customGroupStyle[$customGroup[$id]['style']];
      }
      $customGroup[$id]['extends_display'] = $customGroupExtends[$customGroup[$id]['extends']];
    }

    //fix for Displaying subTypes
    $subTypes = [];

    $subTypes['Activity'] = CRM_Core_PseudoConstant::activityType(FALSE, TRUE, FALSE, 'label', TRUE);
    $subTypes['Contribution'] = CRM_Contribute_PseudoConstant::financialType();
    $subTypes['Membership'] = CRM_Member_BAO_MembershipType::getMembershipTypes(FALSE);
    $subTypes['Event'] = CRM_Core_OptionGroup::values('event_type');
    $subTypes['Grant'] = CRM_Core_OptionGroup::values('grant_type');
    $subTypes['Campaign'] = CRM_Campaign_PseudoConstant::campaignType();
    $subTypes['Participant'] = [];
    $subTypes['ParticipantRole'] = CRM_Core_OptionGroup::values('participant_role');
    $subTypes['ParticipantEventName'] = CRM_Event_PseudoConstant::event();
    $subTypes['ParticipantEventType'] = CRM_Core_OptionGroup::values('event_type');
    $subTypes['Individual'] = CRM_Contact_BAO_ContactType::subTypePairs('Individual', FALSE, NULL);
    $subTypes['Household'] = CRM_Contact_BAO_ContactType::subTypePairs('Household', FALSE, NULL);
    $subTypes['Organization'] = CRM_Contact_BAO_ContactType::subTypePairs('Organization', FALSE, NULL);

    $relTypeInd = CRM_Contact_BAO_Relationship::getContactRelationshipType(NULL, 'null', NULL, 'Individual');
    $relTypeOrg = CRM_Contact_BAO_Relationship::getContactRelationshipType(NULL, 'null', NULL, 'Organization');
    $relTypeHou = CRM_Contact_BAO_Relationship::getContactRelationshipType(NULL, 'null', NULL, 'Household');

    $allRelationshipType = [];
    $allRelationshipType = array_merge($relTypeInd, $relTypeOrg);
    $allRelationshipType = array_merge($allRelationshipType, $relTypeHou);

    //adding subtype specific relationships CRM-5256
    $relSubType = CRM_Contact_BAO_ContactType::subTypeInfo();
    foreach ($relSubType as $subType => $val) {
      $subTypeRelationshipTypes = CRM_Contact_BAO_Relationship::getContactRelationshipType(NULL, NULL, NULL, $val['parent'],
        FALSE, 'label', TRUE, $subType
      );
      $allRelationshipType = array_merge($allRelationshipType, $subTypeRelationshipTypes);
    }

    $subTypes['Relationship'] = $allRelationshipType;

    $cSubTypes = CRM_Core_Component::contactSubTypes();
    $contactSubTypes = [];
    foreach ($cSubTypes as $key => $value) {
      $contactSubTypes[$key] = $key;
    }

    $subTypes['Contact'] = $contactSubTypes;

    CRM_Core_BAO_CustomGroup::getExtendedObjectTypes($subTypes);

    foreach ($customGroup as $key => $values) {
      $subValue = $customGroup[$key]['extends_entity_column_value'] ?? NULL;
      $subName = $customGroup[$key]['extends_entity_column_id'] ?? NULL;
      $type = $customGroup[$key]['extends'] ?? NULL;
      if ($subValue) {
        $subValue = explode(CRM_Core_DAO::VALUE_SEPARATOR,
          substr($subValue, 1, -1)
        );
        $colValue = NULL;
        foreach ($subValue as $sub) {
          if ($sub) {
            if ($type == 'Participant') {
              if ($subName == 1) {
                $colValue = $colValue ? $colValue . ', ' . $subTypes['ParticipantRole'][$sub] : $subTypes['ParticipantRole'][$sub];
              }
              elseif ($subName == 2) {
                $colValue = $colValue ? $colValue . ', ' . $subTypes['ParticipantEventName'][$sub] : $subTypes['ParticipantEventName'][$sub];
              }
              elseif ($subName == 3) {
                $colValue = $colValue ? $colValue . ', ' . $subTypes['ParticipantEventType'][$sub] : $subTypes['ParticipantEventType'][$sub];
              }
            }
            elseif ($type == 'Relationship') {
              $colValue = $colValue ? $colValue . ', ' . $subTypes[$type][$sub . '_a_b'] : $subTypes[$type][$sub . '_a_b'];
              if (isset($subTypes[$type][$sub . '_b_a'])) {
                $colValue = $colValue ? $colValue . ', ' . $subTypes[$type][$sub . '_b_a'] : $subTypes[$type][$sub . '_b_a'];
              }
            }
            else {
              $colValue = $colValue ? ($colValue . (isset($subTypes[$type][$sub]) ? ', ' . $subTypes[$type][$sub] : '')) : ($subTypes[$type][$sub] ?? '');
            }
          }
        }
        $customGroup[$key]["extends_entity_column_value"] = $colValue;
      }
      else {
        if (isset($subTypes[$type]) && is_array($subTypes[$type])) {
          $customGroup[$key]["extends_entity_column_value"] = ts("Any");
        }
      }
    }

    $returnURL = CRM_Utils_System::url('civicrm/admin/custom/group', "reset=1&action=browse");
    CRM_Utils_Weight::addOrder($customGroup, 'CRM_Core_DAO_CustomGroup',
      'id', $returnURL
    );

    $this->assign('rows', $customGroup);
  }

}
