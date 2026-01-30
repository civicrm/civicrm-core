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
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::BROWSE),
        ],
        CRM_Core_Action::PREVIEW => [
          'name' => ts('Preview'),
          'url' => 'civicrm/admin/custom/group/preview',
          'qs' => 'reset=1&gid=%%id%%',
          'title' => ts('Preview Custom Data Set'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::PREVIEW),
        ],
        CRM_Core_Action::UPDATE => [
          'name' => ts('Settings'),
          'url' => 'civicrm/admin/custom/group/edit',
          'qs' => 'action=update&reset=1&id=%%id%%',
          'title' => ts('Edit Custom Set'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::UPDATE),
        ],
        CRM_Core_Action::DISABLE => [
          'name' => ts('Disable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Disable Custom Set'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::DISABLE),
        ],
        CRM_Core_Action::ENABLE => [
          'name' => ts('Enable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Enable Custom Set'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::ENABLE),
        ],
        CRM_Core_Action::DELETE => [
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/custom/group/delete',
          'qs' => 'reset=1&id=%%id%%',
          'title' => ts('Delete Custom Set'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::DELETE),
        ],
      ];
    }
    return self::$_actionLinks;
  }

  /**
   * @return void
   */
  public function run() {
    $this->browse();
    return parent::run();
  }

  /**
   * Browse all custom data groups.
   */
  public function browse() {
    // get all custom groups sorted by weight
    $customGroups = [];
    $dao = new CRM_Core_DAO_CustomGroup();
    $dao->is_reserved = FALSE;
    $dao->orderBy('weight, title');
    $dao->find();

    $customGroupExtends = CRM_Core_SelectValues::customGroupExtends();
    $customGroupStyle = CRM_Core_SelectValues::customGroupStyle();
    while ($dao->fetch()) {
      $id = $dao->id;
      $customGroups[$id] = ['class' => ''];
      CRM_Core_DAO::storeValues($dao, $customGroups[$id]);
      // form all action links
      $action = array_sum(array_keys(self::actionLinks()));

      // update enable/disable links depending on custom_group properties.
      if ($dao->is_active) {
        $action -= CRM_Core_Action::ENABLE;
      }
      else {
        $action -= CRM_Core_Action::DISABLE;
      }
      $customGroups[$id]['order'] = $customGroups[$id]['weight'];
      $customGroups[$id]['action'] = CRM_Core_Action::formLink(self::actionLinks(), $action,
        ['id' => $id],
        ts('more'),
        FALSE,
        'customGroups.row.actions',
        'CustomGroup',
        $id
      );
      if (!empty($customGroups[$id]['style'])) {
        $customGroups[$id]['style_display'] = $customGroupStyle[$customGroups[$id]['style']];
      }
      $customGroups[$id]['extends_display'] = $customGroupExtends[$customGroups[$id]['extends']];
      $customGroups[$id]['extends_entity_column_value'] ??= NULL;
    }

    // FIXME: This hardcoded array is mostly redundant with CRM_Core_BAO_CustomGroup::getSubTypes
    $subTypes = [];

    $subTypes['Activity'] = CRM_Core_PseudoConstant::activityType(FALSE, TRUE, FALSE, 'label', TRUE);
    $subTypes['Contribution'] = CRM_Contribute_PseudoConstant::financialType();
    $subTypes['Membership'] = \Civi\Api4\MembershipType::get(FALSE)
      ->addWhere('is_active', '=', TRUE)
      ->addOrderBy('weight', 'ASC')
      ->execute()
      ->column('title', 'id');
    $subTypes['Event'] = CRM_Core_OptionGroup::values('event_type');
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
    $subTypes['Contact'] = [];

    CRM_Core_BAO_CustomGroup::getExtendedObjectTypes($subTypes);

    foreach ($customGroups as $key => $values) {
      $subValue = $values['extends_entity_column_value'];
      $subName = $customGroups[$key]['extends_entity_column_id'] ?? NULL;
      $type = $customGroups[$key]['extends'] ?? NULL;
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
        $customGroups[$key]['extends_entity_column_value'] = $colValue;
      }
      else {
        if (isset($subTypes[$type]) && is_array($subTypes[$type])) {
          $customGroups[$key]["extends_entity_column_value"] = ts("Any");
        }
      }
    }

    $returnURL = CRM_Utils_System::url('civicrm/admin/custom/group');
    CRM_Utils_Weight::addOrder($customGroups, 'CRM_Core_DAO_CustomGroup',
      'id', $returnURL
    );

    $this->assign('rows', $customGroups);
  }

}
