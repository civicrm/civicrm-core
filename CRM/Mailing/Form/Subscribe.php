<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2018
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
SELECT   title, description
  FROM   civicrm_group
 WHERE   id={$this->_groupID}
   AND   visibility != 'User and User Admin Only'
   AND   $groupTypeCondition";

      $dao = CRM_Core_DAO::executeQuery($query);
      if ($dao->fetch()) {
        $this->assign('groupName', $dao->title);
        CRM_Utils_System::setTitle(ts('Subscribe to Mailing List - %1', array(1 => $dao->title)));
      }
      else {
        CRM_Core_Error::statusBounce("The specified group is not configured for this action OR The group doesn't exist.");
      }

      $this->assign('single', TRUE);
    }
    else {
      $this->assign('single', FALSE);
      CRM_Utils_System::setTitle(ts('Mailing List Subscription'));
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
SELECT   id, title, description
  FROM   civicrm_group
 WHERE   ( saved_search_id = 0
    OR     saved_search_id IS NULL )
   AND   visibility != 'User and User Admin Only'
   AND   $groupTypeCondition
ORDER BY title";
      $dao = CRM_Core_DAO::executeQuery($query);
      $rows = array();
      while ($dao->fetch()) {
        $row = array();
        $row['id'] = $dao->id;
        $row['title'] = $dao->title;
        $row['description'] = $dao->description;
        $row['checkbox'] = CRM_Core_Form::CB_PREFIX . $row['id'];
        $this->addElement('checkbox',
          $row['checkbox'],
          NULL, NULL
        );
        $rows[] = $row;
      }
      if (empty($rows)) {
        CRM_Core_Error::fatal(ts('There are no public mailing list groups to display.'));
      }
      $this->assign('rows', $rows);
      $this->addFormRule(array('CRM_Mailing_Form_Subscribe', 'formRule'));
    }

    $addCaptcha = TRUE;

    // if recaptcha is not configured, then dont add it
    // CRM-11316 Only enable ReCAPTCHA for anonymous visitors
    $config = CRM_Core_Config::singleton();
    $session = CRM_Core_Session::singleton();
    $contactID = $session->get('userID');

    if (empty($config->recaptchaPublicKey) ||
      empty($config->recaptchaPrivateKey) ||
      $contactID
    ) {
      $addCaptcha = FALSE;
    }
    else {
      // If this is POST request and came from a block,
      // lets add recaptcha only if already present.
      // Gross hack for now.
      if (!empty($_POST) &&
        !array_key_exists('recaptcha_challenge_field', $_POST)
      ) {
        $addCaptcha = FALSE;
      }
    }

    if ($addCaptcha) {
      // add captcha
      $captcha = CRM_Utils_ReCAPTCHA::singleton();
      $captcha->add($this);
      $this->assign('isCaptcha', TRUE);
    }

    $this->addButtons(array(
        array(
          'type' => 'next',
          'name' => ts('Subscribe'),
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );
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
    return array('_qf_default' => 'Please select one or more mailing lists.');
  }

  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);

    $groups = array();
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

    CRM_Mailing_Event_BAO_Subscribe::commonSubscribe($groups, $params);
  }

}
