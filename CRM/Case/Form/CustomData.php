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
 * This class generates form components for custom data
 *
 * It delegates the work to lower level subclasses and integrates the changes
 * back in. It also uses a lot of functionality with the CRM API's, so any change
 * made here could potentially affect the API etc. Be careful, be aware, use unit tests.
 */
class CRM_Case_Form_CustomData extends CRM_Core_Form {

  /**
   * The entity id, used when editing/creating custom data
   *
   * @var int
   */
  protected $_entityID;

  /**
   * Entity sub type of the table id.
   *
   * @var string
   */
  protected $_subTypeID;

  /**
   * Pre processing work done here.
   *
   * gets session variables for table name, id of entity in table, type of entity and stores them.
   */
  public function preProcess() {
    $this->_groupID = CRM_Utils_Request::retrieve('groupID', 'Positive', $this, TRUE);
    $this->_entityID = CRM_Utils_Request::retrieve('entityID', 'Positive', $this, TRUE);
    $this->_subTypeID = CRM_Utils_Request::retrieve('subType', 'Positive', $this, TRUE);
    $this->_contactID = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);

    $groupTree = CRM_Core_BAO_CustomGroup::getTree('Case',
      NULL,
      $this->_entityID,
      $this->_groupID,
      $this->_subTypeID
    );
    // simplified formatted groupTree
    $groupTree = CRM_Core_BAO_CustomGroup::formatGroupTree($groupTree, 1, $this);
    // Array contains only one item
    foreach ($groupTree as $groupValues) {
      $this->_customTitle = $groupValues['title'];
      CRM_Utils_System::setTitle(ts('Edit %1', [1 => $groupValues['title']]));
    }

    $this->_defaults = [];
    CRM_Core_BAO_CustomGroup::setDefaults($groupTree, $this->_defaults);
    $this->setDefaults($this->_defaults);

    CRM_Core_BAO_CustomGroup::buildQuickForm($this, $groupTree);

    //need to assign custom data type and subtype to the template
    $this->assign('entityID', $this->_entityID);
    $this->assign('groupID', $this->_groupID);
    $this->assign('subType', $this->_subTypeID);
    $this->assign('contactID', $this->_contactID);
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    // make this form an upload since we dont know if the custom data injected dynamically
    // is of type file etc
    $this->addButtons([
      [
        'type' => 'upload',
        'name' => ts('Save'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ]);
  }

  /**
   * Process the user submitted custom data values.
   */
  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);

    $transaction = new CRM_Core_Transaction();

    CRM_Core_BAO_CustomValueTable::postProcess($params,
      'civicrm_case',
      $this->_entityID,
      'Case'
    );

    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url('civicrm/contact/view/case', "reset=1&id={$this->_entityID}&cid={$this->_contactID}&action=view"));

    $activityTypeID = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Change Custom Data');
    $activityParams = [
      'activity_type_id' => $activityTypeID,
      'source_contact_id' => $session->get('userID'),
      'is_auto' => TRUE,
      'subject' => $this->_customTitle . " : change data",
      'status_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_status_id', 'Completed'),
      'target_contact_id' => $this->_contactID,
      'details' => $this->formatCustomDataChangesForDetail($params),
      'activity_date_time' => date('YmdHis'),
    ];
    $activity = CRM_Activity_BAO_Activity::create($activityParams);

    $caseParams = [
      'activity_id' => $activity->id,
      'case_id' => $this->_entityID,
    ];
    CRM_Case_BAO_Case::processCaseActivity($caseParams);

    $transaction->commit();
  }

  /**
   * Format the custom data changes as [label]: [old value] => [new value]
   *
   * @param array $params New custom field values from form
   *
   * @return string
   * @throws \CiviCRM_API3_Exception
   */
  public function formatCustomDataChangesForDetail($params) {
    $formattedDetails = [];
    foreach ($params as $customField => $newCustomValue) {
      if (substr($customField, 0, 7) == 'custom_') {
        if ($this->_defaults[$customField] == $newCustomValue) {
          // Don't show values that did not change
          continue;
        }
        // We need custom field ID from custom_XX_1
        list($_, $customFieldId, $_) = explode('_', $customField);

        if (!empty($customFieldId) && is_numeric($customFieldId)) {
          // Got a custom field ID
          $label = civicrm_api3('CustomField', 'getvalue', ['id' => $customFieldId, 'return' => 'label']);
          $oldValue = civicrm_api3('CustomValue', 'getdisplayvalue', [
            'custom_field_id' => $customFieldId,
            'entity_id' => $this->_entityID,
            'custom_field_value' => $this->_defaults[$customField],
          ]);
          $oldValue = $oldValue['values'][$customFieldId]['display'];
          $newValue = civicrm_api3('CustomValue', 'getdisplayvalue', [
            'custom_field_id' => $customFieldId,
            'entity_id' => $this->_entityID,
            'custom_field_value' => $newCustomValue,
          ]);
          $newValue = $newValue['values'][$customFieldId]['display'];
          $formattedDetails[] = $label . ': ' . $oldValue . ' => ' . $newValue;
        }

      }
    }

    return implode('<br/>', $formattedDetails);
  }

}
