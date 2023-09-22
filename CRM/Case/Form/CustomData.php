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
   * @var string
   */
  private $customGroupTitle;

  /**
   * Pre processing work done here.
   *
   * gets session variables for table name, id of entity in table, type of
   * entity and stores them.
   *
   * @throws \CRM_Core_Exception
   */
  public function preProcess(): void {
    $groupID = CRM_Utils_Request::retrieve('groupID', 'Positive', $this, TRUE);
    $this->_entityID = CRM_Utils_Request::retrieve('entityID', 'Positive', $this, TRUE);
    $this->_subTypeID = CRM_Utils_Request::retrieve('subType', 'Positive', $this, TRUE);
    $contactID = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);

    $groupTree = CRM_Core_BAO_CustomGroup::getTree('Case',
      NULL,
      $this->_entityID,
      $groupID,
      $this->_subTypeID
    );
    // simplified formatted groupTree
    $groupTree = CRM_Core_BAO_CustomGroup::formatGroupTree($groupTree, 1, $this);
    // Array contains only one item
    foreach ($groupTree as $groupValues) {
      $this->customGroupTitle = $groupValues['title'];
      $this->setTitle(ts('Edit %1', [1 => $groupValues['title']]));
    }

    $this->_defaults = [];
    CRM_Core_BAO_CustomGroup::setDefaults($groupTree, $this->_defaults);
    $this->setDefaults($this->_defaults);

    CRM_Core_BAO_CustomGroup::buildQuickForm($this, $groupTree);

    //need to assign custom data type and subtype to the template
    $this->assign('entityID', $this->_entityID);
    $this->assign('groupID', $groupID);
    $this->assign('subType', $this->_subTypeID);
    $this->assign('contactID', $contactID);
    $this->assign('cgCount');
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm(): void {
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
   *
   * @throws \CRM_Core_Exception
   */
  public function postProcess(): void {
    $params = $this->controller->exportValues($this->_name);

    $transaction = new CRM_Core_Transaction();

    CRM_Core_BAO_CustomValueTable::postProcess($params,
      'civicrm_case',
      $this->_entityID,
      'Case'
    );
    $contactID = CRM_Utils_Request::retrieve('cid', 'Positive', $this);

    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url('civicrm/contact/view/case', "reset=1&id={$this->_entityID}&cid={$contactID}&action=view"));

    $formattedDetails = $this->formatCustomDataChangesForDetail($params);
    if (!empty($formattedDetails)) {
      $activityTypeID = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Change Custom Data');
      $activityParams = [
        'activity_type_id' => $activityTypeID,
        'source_contact_id' => $session->get('userID'),
        'is_auto' => TRUE,
        'subject' => $this->customGroupTitle . ' : change data',
        'status_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_status_id', 'Completed'),
        'target_contact_id' => $contactID,
        'details' => $formattedDetails,
        'activity_date_time' => date('YmdHis'),
      ];
      $activity = CRM_Activity_BAO_Activity::create($activityParams);

      $caseParams = [
        'activity_id' => $activity->id,
        'case_id' => $this->_entityID,
      ];
      CRM_Case_BAO_Case::processCaseActivity($caseParams);
    }

    $transaction->commit();
  }

  /**
   * Format the custom data changes as [label]: [old value] => [new value]
   *
   * @param array $params New custom field values from form
   *
   * @return string
   * @throws \CRM_Core_Exception
   */
  public function formatCustomDataChangesForDetail(array $params): string {
    $formattedDetails = [];
    foreach ($params as $customField => $newCustomValue) {
      if (strpos($customField, 'custom_') === 0) {
        if (($this->_defaults[$customField] ?? '') === $newCustomValue) {
          // Don't show values that did not change
          continue;
        }
        // We need custom field ID from custom_XX_1
        [, $customFieldId] = explode('_', $customField);

        if (!empty($customFieldId) && is_numeric($customFieldId)) {
          // Got a custom field ID
          $label = civicrm_api3('CustomField', 'getvalue', ['id' => $customFieldId, 'return' => 'label']);

          // Convert dropdown and other machine values to human labels.
          // Money is special for non-US locales because at this point it's in human format so we don't
          // want to try to convert it.
          $oldValue = $this->_defaults[$customField] ?? '';
          $newValue = $newCustomValue;
          if ('Money' !== (string) civicrm_api3('CustomField', 'getvalue', ['id' => $customFieldId, 'return' => 'data_type'])) {
            $oldValue = civicrm_api3('CustomValue', 'getdisplayvalue', [
              'custom_field_id' => $customFieldId,
              'entity_id' => $this->_entityID,
              'custom_field_value' => $oldValue,
            ]);
            $oldValue = $oldValue['values'][$customFieldId]['display'];
            $newValue = civicrm_api3('CustomValue', 'getdisplayvalue', [
              'custom_field_id' => $customFieldId,
              'entity_id' => $this->_entityID,
              'custom_field_value' => $newCustomValue,
            ]);
            $newValue = $newValue['values'][$customFieldId]['display'];
          }
          $formattedDetails[] = $label . ': ' . $oldValue . ' => ' . $newValue;
        }

      }
    }

    return implode('<br/>', $formattedDetails);
  }

}
