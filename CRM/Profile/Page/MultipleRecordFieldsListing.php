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
class CRM_Profile_Page_MultipleRecordFieldsListing extends CRM_Core_Page_Basic {

  /**
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  public static $_links = NULL;

  protected $_fields = NULL;

  protected $_profileId = NULL;

  /**
   * @param int $profileId
   */
  public function setProfileID(int $profileId): void {
    $this->_profileId = $profileId;
  }

  public $_contactId = NULL;

  public $_customGroupTitle = NULL;

  public $_pageViewType = 'profileDataView';

  /**
   * Get BAO Name.
   *
   * @return string
   *   Classname of BAO.
   */
  public function getBAOName() {
    return '';
  }

  /**
   * Get action Links.
   *
   * @return array
   *   (reference) of action links
   */
  public function &links() {
    if (!isset(self::$_links['profileDataView'])) {
      $view = array_search(CRM_Core_Action::VIEW, CRM_Core_Action::$_names);
      $update = array_search(CRM_Core_Action::UPDATE, CRM_Core_Action::$_names);
      $delete = array_search(CRM_Core_Action::DELETE, CRM_Core_Action::$_names);

      $links = [];
      $links[CRM_Core_Action::VIEW] = [
        'name' => ts('View'),
        'title' => ts('View %1', [1 => $this->_customGroupTitle . ' record']),
        'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::VIEW),
        'url' => 'civicrm/profile/view',
        'qs' => "reset=1&id=%%id%%&recordId=%%recordId%%&gid=%%gid%%&multiRecord={$view}",
      ];

      $links[CRM_Core_Action::UPDATE] = [
        'name' => ts('Edit'),
        'title' => ts('Edit %1', [1 => $this->_customGroupTitle . ' record']),
        'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::UPDATE),
        'url' => 'civicrm/profile/edit',
        'qs' => "reset=1&id=%%id%%&recordId=%%recordId%%&gid=%%gid%%&multiRecord={$update}",
      ];

      $links[CRM_Core_Action::DELETE] = [
        'name' => ts('Delete'),
        'title' => ts('Delete %1', [1 => $this->_customGroupTitle . ' record']),
        'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::DELETE),
        'url' => 'civicrm/profile/edit',
        'qs' => "reset=1&id=%%id%%&recordId=%%recordId%%&gid=%%gid%%&multiRecord={$delete}",
      ];

      self::$_links['profileDataView'] = $links;
    }
    return self::$_links['profileDataView'];
  }

  /**
   * Run the page.
   *
   * This method is called after the page is created. It checks for the type
   * of action and executes that action. Finally it calls the parent's run
   * method.
   *
   */
  public function run() {
    $action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, FALSE);
    $this->assign('action', $action);

    $profileId = CRM_Utils_Request::retrieve('profileId', 'Positive', $this, FALSE);
    if (!is_array($profileId) && is_numeric($profileId)) {
      $this->_profileId = $profileId;
    }

    $this->_contactId = CRM_Utils_Request::retrieve('contactId', 'Positive', $this, FALSE);
    $this->_pageViewType = 'profileDataView';

    if ($action & CRM_Core_Action::BROWSE) {
      $this->browse();
      return;
    }
    return parent::run();
  }

  /**
   * Browse the listing.
   *
   */
  public function browse(): array {
    $dateFields = NULL;
    $newCgCount = $cgcount = 0;
    $attributes = $result = [];
    $dateFieldsVals = NULL;
    $customGroupId = NULL;
    $fieldIDs = NULL;

    if ($this->_profileId) {
      $fields = CRM_Core_BAO_UFGroup::getFields($this->_profileId, FALSE, NULL,
        NULL, NULL,
        FALSE, NULL,
        FALSE,
        NULL,
        CRM_Core_Permission::EDIT
      );
      $multiRecordFields = [];
      $multiRecordFieldsWithSummaryListing = CRM_Core_BAO_UFGroup::shiftMultiRecordFields($fields, $multiRecordFields, TRUE);

      $multiFieldId = CRM_Core_BAO_CustomField::getKeyID(key($multiRecordFields));
      $customGroupId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField', $multiFieldId, 'custom_group_id');
      $reached = CRM_Core_BAO_CustomGroup::hasReachedMaxLimit($customGroupId, $this->_contactId);
      if (!$reached) {
        $this->assign('contactId', $this->_contactId);
        $this->assign('gid', $this->_profileId);
      }
      $this->assign('reachedMax', $reached);

      if ($multiRecordFieldsWithSummaryListing && !empty($multiRecordFieldsWithSummaryListing)) {
        $fieldIDs = array_keys($multiRecordFieldsWithSummaryListing);
      }
    }

    if (!empty($fieldIDs) && $this->_contactId) {
      $options = [];
      $returnProperities = [
        'html_type',
        'data_type',
        'date_format',
        'time_format',
        'default_value',
        'is_required',
        'is_view',
      ];
      foreach ($fieldIDs as $key => $fieldID) {
        $fieldIDs[$key] = !is_numeric($fieldID) ? CRM_Core_BAO_CustomField::getKeyID($fieldID) : $fieldID;
        $param = ['id' => $fieldIDs[$key]];
        $returnValues = [];
        CRM_Core_DAO::commonRetrieve('CRM_Core_DAO_CustomField', $param, $returnValues, $returnProperities);
        if ($returnValues['data_type'] == 'Date') {
          $dateFields[$fieldIDs[$key]] = 1;
          $actualPHPFormats = CRM_Utils_Date::datePluginToPHPFormats();
          $dateFormat = (array) ($actualPHPFormats[$returnValues['date_format']] ?? []);
          $timeFormat = $returnValues['time_format'] ?? NULL;
        }

        $optionValuePairs = CRM_Core_BAO_CustomOption::getCustomOption($fieldIDs[$key]);
        if (!empty($optionValuePairs)) {
          foreach ($optionValuePairs as $optionPairs) {
            $options[$fieldIDs[$key]][$optionPairs['value']] = $optionPairs['label'];
          }
        }

        $options[$fieldIDs[$key]]['attributes']['html_type'] = $returnValues['html_type'];
        $options[$fieldIDs[$key]]['attributes']['data_type'] = $returnValues['data_type'];
        $options[$fieldIDs[$key]]['attributes']['is_required'] = !empty($returnValues['is_required']);
        $options[$fieldIDs[$key]]['attributes']['default_value'] = $returnValues['default_value'] ?? NULL;
        $options[$fieldIDs[$key]]['attributes']['is_view'] = $returnValues['is_view'] ?? NULL;

        $options[$fieldIDs[$key]]['attributes']['format']
          = $options[$fieldIDs[$key]]['attributes']['date_format'] = $returnValues['date_format'] ?? NULL;
        $options[$fieldIDs[$key]]['attributes']['time_format'] = $returnValues['time_format'] ?? NULL;
      }
      $linkAction = array_sum(array_keys($this->links()));
    }

    // Check permissions to edit the contact and the custom fields
    $editPermission = FALSE;
    if ($this->_contactId && $customGroupId) {
      $editPermission = CRM_Core_BAO_CustomGroup::checkGroupAccess($customGroupId, CRM_Core_Permission::EDIT) &&
        CRM_Contact_BAO_Contact_Permission::allow($this->_contactId, CRM_Core_Permission::EDIT);
      if (!$editPermission) {
        $linkAction -= (CRM_Core_Action::COPY + CRM_Core_Action::UPDATE + CRM_Core_Action::DELETE);
      }
    }

    if (!empty($fieldIDs) && $this->_contactId) {
      $result = CRM_Core_BAO_CustomValueTable::getEntityValues($this->_contactId, NULL, $fieldIDs, TRUE);
      $resultCount = count($result);

      if (!empty($fieldIDs)) {
        // get the group info of multi rec fields in listing view
        $fieldInput = $fieldIDs;
        $fieldIdInput = $fieldIDs[0];
      }
      else {
        // if no listing fields exist, take the group title for display
        $nonListingFieldIds = array_keys($multiRecordFields);
        $singleField = CRM_Core_BAO_CustomField::getKeyID($nonListingFieldIds[0]);
        $fieldIdInput = $singleField;
        $singleField = [$singleField];
        $fieldInput = $singleField;
      }
      $customGroupInfo = CRM_Core_BAO_CustomGroup::getGroupTitles($fieldInput);
      $this->_customGroupTitle = $customGroupInfo[$fieldIdInput]['groupTitle'];

      $cgcount = 1;
      $newCgCount = (!$reached) ? $resultCount + 1 : NULL;
      if (!empty($result)) {
        $links = $this->links();
        $pageCheckSum = $this->get('pageCheckSum');
        if ($pageCheckSum) {
          foreach ($links as $key => $link) {
            $links[$key] = $link['qs'] . "&cs=%%cs%%";
          }
        }

        if ($reached) {
          unset($links[CRM_Core_Action::COPY]);
        }

        foreach ($result as $recId => &$value) {
          foreach ($value as $fieldId => &$val) {
            if (is_numeric($fieldId)) {
              $customValue = &$val;
              if (!empty($dateFields) && array_key_exists($fieldId, $dateFields)) {
                $dateFieldsVals[$fieldId][$recId] = CRM_Utils_Date::processDate($result[$recId][$fieldId], NULL, FALSE, 'YmdHis');

                // set date and time format
                switch ($timeFormat) {
                  case 1:
                    $dateFormat[1] = 'g:iA';
                    break;

                  case 2:
                    $dateFormat[1] = 'G:i';
                    break;

                  default:
                    // if time is not selected remove time from value
                    $result[$recId][$fieldId] = substr($result[$recId][$fieldId], 0, 10);
                }
                $result[$recId][$fieldId] = CRM_Utils_Date::processDate($result[$recId][$fieldId], NULL, FALSE, implode(" ", $dateFormat));
              }
              else {
                $customValue = CRM_Core_BAO_CustomField::displayValue($customValue, $fieldId);
              }

              $fieldAttributes = ['class' => "crmf-custom_{$fieldId}_$recId"];
              $attributes[$fieldId][$recId] = $fieldAttributes;

              $actionParams = [
                'recordId' => $recId,
                'gid' => $this->_profileId,
                'id' => $this->_contactId,
              ];
              $op = 'profile.multiValue.row';

              if (!empty($pageCheckSum)) {
                $actionParams['cs'] = $pageCheckSum;
              }

              $value['action'] = CRM_Core_Action::formLink(
                $links,
                $linkAction,
                $actionParams,
                ts('more'),
                FALSE,
                $op,
                'customValue',
                $fieldId
              );
            }
          }
          $cgcount++;
        }
      }
    }

    $headers = [];
    if (!empty($fieldIDs)) {
      foreach ($fieldIDs as $fieldID) {
        $headers[$fieldID] = $customGroupInfo[$fieldID]['fieldLabel'];
      }
    }
    $this->assign('editPermission', $editPermission);
    $this->assign('dateFields', $dateFields);
    $this->assign('dateFieldsVals', $dateFieldsVals);
    $this->assign('cgcount', $cgcount);
    $this->assign('newCgCount', $newCgCount);
    $this->assign('contactId', $this->_contactId);
    $this->assign('customGroupTitle', $this->_customGroupTitle);
    $this->assign('headers', $headers);
    $this->assign('records', $result);
    $this->assign('attributes', $attributes);

    return [$result, $attributes];
  }

  /**
   * Get name of edit form.
   *
   * @return string
   *   classname of edit form
   */
  public function editForm() {
    return '';
  }

  /**
   * Get edit form name.
   *
   * @return string
   *   name of this page
   */
  public function editName() {
    return '';
  }

  /**
   * Get user context.
   *
   * @param null $mode
   *
   * @return string
   *   user context
   */
  public function userContext($mode = NULL) {
    return '';
  }

}
