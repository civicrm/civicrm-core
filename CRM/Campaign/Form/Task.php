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
 * This class generates form components for relationship.
 */
class CRM_Campaign_Form_Task extends CRM_Core_Form_Task {

  /**
   * The array that holds all the voter ids
   *
   * @var array
   */
  protected $_voterIds;

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    $values = $this->controller->exportValues('Search');

    $this->_task = $values['task'];

    $ids = $this->getSelectedIDs($values);

    if (!$ids) {
      $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String', $this);
      $cacheKey = "civicrm search {$qfKey}";
      $allCids = Civi::service('prevnext')->getSelection($cacheKey, "getall");
      $ids = array_keys($allCids[$cacheKey]);
      $this->assign('totalSelectedVoters', count($ids));
    }

    if (!empty($ids)) {
      $this->_componentClause = 'contact_a.id IN ( ' . implode(',', $ids) . ' ) ';
      $this->assign('totalSelectedVoters', count($ids));
    }
    $this->_voterIds = $this->_contactIds = $this->_componentIds = $ids;

    $this->assign('totalSelectedContacts', count($this->_contactIds));
    $this->setNextUrl('survey');
  }

  /**
   * Given the voter id, compute the contact id
   * since its used for things like send email
   */
  public function setContactIDs() {
    $this->_contactIds = $this->_voterIds;
  }

}
