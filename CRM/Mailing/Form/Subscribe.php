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
class CRM_Mailing_Form_Subscribe extends CRM_Core_Form {
  protected $_groupID = NULL;

  public function preProcess() {
    parent::preProcess();
    $this->_groupID = CRM_Utils_Request::retrieve('gid', 'Integer', $this,
      FALSE, NULL, 'REQUEST'
    );

    // ensure that there is a destination, if not set the destination to the
    // referrer string
    if (!$this->controller->getDestination()) {
      $this->controller->setDestination(NULL, TRUE);
    }

    if ($this->_groupID) {
      $groupTypeCondition = CRM_Contact_BAO_Group::groupTypeCondition('Mailing');

      // make sure requested qroup is accessible and exists
      $query = "
SELECT   title, frontend_title, description, frontend_description
  FROM   civicrm_group
 WHERE   id={$this->_groupID}
   AND   visibility != 'User and User Admin Only'
   AND   $groupTypeCondition";

      $dao = CRM_Core_DAO::executeQuery($query);
      if ($dao->fetch()) {
        $this->assign('groupName', !empty($dao->frontend_title) ? $dao->frontend_title : $dao->title);
        $this->setTitle(ts('Subscribe to Mailing List - %1', [1 => !empty($dao->frontend_title) ? $dao->frontend_title : $dao->title]));
      }
      else {
        CRM_Core_Error::statusBounce("The specified group is not configured for this action OR The group doesn't exist.");
      }

      $this->assign('single', TRUE);
    }
    else {
      $this->assign('single', FALSE);
      $this->setTitle(ts('Mailing List Subscription'));
    }
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    // add the email address
    $this->add('text',
      'email',
      ts('Email'),
      CRM_Core_DAO::getAttribute('CRM_Core_DAO_Email',
        'email'
      ),
      TRUE
    );
    $this->addRule('email', ts("Please enter a valid email address."), 'email');

    if (!$this->_groupID) {
      // create a selector box of all public groups
      $groupTypeCondition = CRM_Contact_BAO_Group::groupTypeCondition('Mailing');

      $query = "
SELECT   id, title, frontend_title, description, frontend_description
  FROM   civicrm_group
 WHERE   ( saved_search_id = 0
    OR     saved_search_id IS NULL )
   AND   visibility != 'User and User Admin Only'
   AND   $groupTypeCondition
ORDER BY title";
      $dao = CRM_Core_DAO::executeQuery($query);
      $rows = [];
      while ($dao->fetch()) {
        $row = [];
        $row['id'] = $dao->id;
        $row['title'] = $dao->frontend_title;
        $row['description'] = $dao->frontend_description;
        $row['checkbox'] = CRM_Core_Form::CB_PREFIX . $row['id'];
        $this->addElement('checkbox',
          $row['checkbox'],
          NULL, NULL
        );
        $rows[] = $row;
      }
      if (empty($rows)) {
        throw new CRM_Core_Exception(ts('There are no public mailing list groups to display.'));
      }
      $this->assign('rows', $rows);
      $this->addFormRule(['CRM_Mailing_Form_Subscribe', 'formRule']);
    }

    $this->addButtons([
      [
        'type' => 'next',
        'name' => ts('Subscribe'),
        'isDefault' => TRUE,
      ],
    ]);
  }

  /**
   * @param $fields
   *
   * @return array|bool
   */
  public static function formRule($fields) {
    foreach ($fields as $name => $dontCare) {
      if (substr($name, 0, CRM_Core_Form::CB_PREFIX_LEN) == CRM_Core_Form::CB_PREFIX) {
        return TRUE;
      }
    }
    return ['_qf_default' => 'Please select one or more mailing lists.'];
  }

  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);

    $groups = [];
    if ($this->_groupID) {
      $groups[] = $this->_groupID;
    }
    else {
      foreach ($params as $name => $dontCare) {
        if (substr($name, 0, CRM_Core_Form::CB_PREFIX_LEN) == CRM_Core_Form::CB_PREFIX) {
          $groups[] = substr($name, CRM_Core_Form::CB_PREFIX_LEN);
        }
      }
    }

    CRM_Mailing_Event_BAO_MailingEventSubscribe::commonSubscribe($groups, $params);
  }

}
