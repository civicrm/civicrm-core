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
 * Form helper class for custom data section.
 */
class CRM_Contact_Form_Inline_CustomData extends CRM_Contact_Form_Inline {

  /**
   * Custom group id.
   *
   * @var int
   */
  public $_groupID;

  /**
   * Entity type of the table id.
   *
   * @var string
   */
  protected $_entityType;

  /**
   * Build the form object elements for custom data.
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm(): void {
    parent::buildQuickForm();
    $this->_groupID = CRM_Utils_Request::retrieve('groupID', 'Positive', $this, TRUE, NULL);
    $this->assign('customGroupId', $this->_groupID);
    $type = $this->_contactType;
    $groupCount = CRM_Utils_Request::retrieve('cgcount', 'Positive', $this, FALSE, 1);
    $this->assign('cgCount', $groupCount);

    $extendsEntityColumn = CRM_Utils_Request::retrieve('subName', 'String', $this);
    if ($extendsEntityColumn === 'null') {
      // Is this reachable?
      $extendsEntityColumn = NULL;
    }

    //carry qf key, since this form is not inheriting core form.
    if ($qfKey = CRM_Utils_Request::retrieve('qfKey', 'String')) {
      $this->assign('qfKey', $qfKey);
    }

    $typeCheck = CRM_Utils_Request::retrieve('type', 'String');
    $urlGroupId = CRM_Utils_Request::retrieve('groupID', 'Positive');
    if (isset($typeCheck) && $urlGroupId) {
      $this->_groupID = $urlGroupId;
    }
    else {
      $this->_groupID = CRM_Utils_Request::retrieve('groupID', 'Positive', $this);
    }

    $gid = (isset($this->_groupID)) ? $this->_groupID : NULL;

    $groupTree = CRM_Core_BAO_CustomGroup::getTree($type,
      NULL,
      $this->getContactID(),
      $gid,
      CRM_Contact_BAO_Contact::getContactSubType($this->getContactID()),
      $extendsEntityColumn
    );

    if (property_exists($this, '_customValueCount') && !empty($groupTree)) {
      $this->_customValueCount = CRM_Core_BAO_CustomGroup::buildCustomDataView($this, $groupTree, TRUE, NULL, NULL, NULL, $this->getContactID());
    }
    // we should use simplified formatted groupTree
    $groupTree = CRM_Core_BAO_CustomGroup::formatGroupTree($groupTree, $groupCount, $this);

    if (isset($this->_groupTree) && is_array($this->_groupTree)) {
      $keys = array_keys($groupTree);
      foreach ($keys as $key) {
        $this->_groupTree[$key] = $groupTree[$key];
      }
    }
    else {
      $this->_groupTree = $groupTree;
    }
    $this->addElement('hidden', 'hidden_custom', 1);
    $this->addElement('hidden', "hidden_custom_group_count[{$this->_groupID}]", CRM_Utils_Request::retrieve('cgcount', 'Positive', $this, FALSE, 1));
    CRM_Core_BAO_CustomGroup::buildQuickForm($this, $this->_groupTree);
    // This form only applies to a single group so this loop always runs once
    foreach ($this->_groupTree as $group_id => $cd_edit) {
      $this->assign('group_id', $group_id);
      $this->assign('cd_edit', $cd_edit);
    }
  }

  /**
   * Set defaults for the form.
   *
   * @return array
   */
  public function setDefaultValues(): array {
    $defaults = [];
    CRM_Core_BAO_CustomGroup::setDefaults($this->_groupTree, $defaults, FALSE, FALSE, $this->get('action'));
    return $defaults;
  }

  /**
   * Process the form.
   */
  public function postProcess(): void {
    // Process / save custom data
    // Get the form values and groupTree
    $params = $this->getSubmittedValues();
    CRM_Core_BAO_CustomValueTable::postProcess($params,
      'civicrm_contact',
      $this->getContactID(),
      $this->_entityType
    );

    $this->log();

    CRM_Contact_BAO_GroupContactCache::opportunisticCacheFlush();

    $this->response();
  }

}
