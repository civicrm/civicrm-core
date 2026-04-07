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
 */
class CRM_Case_Form_CustomData extends CRM_Core_Form implements CRM_Case_Form_CaseFormInterface {

  public function getCaseID(): int {
    if (!isset($this->_entityID)) {
      $this->_entityID = (int) CRM_Utils_Request::retrieve('entityID', 'Positive', $this, TRUE);
    }
    return $this->_entityID;
  }

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
    $this->getCaseID();
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
    $params = $this->getSubmittedValues();

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
    foreach ($params as $fieldKey => $newCustomValue) {
      if (str_starts_with($fieldKey, 'custom_')) {
        if (($this->_defaults[$fieldKey] ?? '') === $newCustomValue) {
          // Don't show values that did not change
          continue;
        }
        // We need custom field ID from custom_XX_1
        [, $customFieldId] = explode('_', $fieldKey);

        if (!empty($customFieldId) && is_numeric($customFieldId)) {
          // valid custom field id
          $customFieldId = (int) $customFieldId;

          // check field exists and get meta
          $customField = CRM_Core_BAO_CustomField::getField($customFieldId);

          if ($customField) {
            // label from custom field
            $label = $customField['label'];

            // before/after values from form
            $oldValue = $this->_defaults[$fieldKey] ?? '';
            $newValue = $newCustomValue;

            // Convert dropdown and other machine values to human labels.
            $oldValue = $this->formatDisplayValue($oldValue, $customFieldId, $customField['data_type']);
            $newValue = $this->formatDisplayValue($newValue, $customFieldId, $customField['data_type']);

            $formattedDetails[] = $label . ': ' . $oldValue . ' => ' . $newValue;
          }

        }

      }
    }

    return implode('<br/>', $formattedDetails);
  }

  private function formatDisplayValue(mixed $value, int $customFieldId, string $customFieldDataType): string {
    switch ($customFieldDataType) {
      case 'Money':
      case 'Float':
        // Money and Float are special for non-US locales because at this point
        // it's in human format so we don't want to try to convert it.
        return $value;

      case 'File':
        // File is tricky - updating the case file reuses the same File ID.
        // This makes saving /civicrm/file? urls meaningless as
        // every URL will just render the latest version of the file...
        //
        // It also doesn't make sense to permanently save a url containing a time-limited checksum
        //
        // So for now we just save the filename before and after
        //
        // @todo consider updating the handling so new files are saved with new IDs,
        // and then stashing the file ids somewhere that live urls can be rendered
        // dynamically? (or is the expectation that old versions of files are totally gone forever?)

        // new values come through with the whole file path in the `name` key
        $filename = NULL;
        if (!empty($value['name'])) {
          $filename = basename($value['name']);
        }

        // old values come through with the filename in the `data` key
        if (!empty($value['data'])) {
          $filename = $value['data'];
        }

        if ($filename) {
          // remove hash so we don't expose this
          return CRM_Utils_File::cleanFileName($filename);
        }

        return ts('No file');

      default:
        return civicrm_api3('CustomValue', 'getdisplayvalue', [
          'custom_field_id' => $customFieldId,
          'entity_id' => $this->_entityID,
          'custom_field_value' => $value,
        ])['values'][$customFieldId]['display'];
    }
  }

}
