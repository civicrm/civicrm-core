<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * Page for displaying list of Option Value
 */
class CRM_Admin_Page_OptionValue extends CRM_Core_Page_Basic {

  /**
   * The action links that we need to display for the browse screen
   .     *
   * @var array
   * @static
   */
  static $_links = NULL;

  static $_gid = NULL;

  /**
   * The option group name
   *
   * @var string
   * @static
   */
  static $_gName = NULL;

  /**
   * Get BAO Name
   *
   * @return string Classname of BAO.
   */
  function getBAOName() {
    return 'CRM_Core_BAO_OptionValue';
  }

  /**
   * Get action Links
   *
   * @return array (reference) of action links
   */
  function &links() {
    if (!(self::$_links)) {
      self::$_links = array(
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Edit'),
          'url' => 'civicrm/admin/optionValue',
          'qs' => 'action=update&id=%%id%%&gid=%%gid%%&reset=1',
          'title' => ts('Edit Option Value'),
        ),
        CRM_Core_Action::DISABLE => array(
          'name' => ts('Disable'),
          'extra' => 'onclick = "enableDisable( %%id%%,\'' . 'CRM_Core_BAO_OptionValue' . '\',\'' . 'enable-disable' . '\' );"',
          'ref' => 'disable-action',
          'title' => ts('Disable Option Value'),
        ),
        CRM_Core_Action::ENABLE => array(
          'name' => ts('Enable'),
          'extra' => 'onclick = "enableDisable( %%id%%,\'' . 'CRM_Core_BAO_OptionValue' . '\',\'' . 'disable-enable' . '\' );"',
          'ref' => 'enable-action',
          'title' => ts('Enable Option Value'),
        ),
        CRM_Core_Action::DELETE => array(
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/optionValue',
          'qs' => 'action=delete&id=%%id%%&gid=%%gid%%',
          'title' => ts('Delete Option Value'),
        ),
      );
    }
    return self::$_links;
  }

  /**
   * Run the page.
   *
   * This method is called after the page is created. It checks for the
   * type of action and executes that action.
   * Finally it calls the parent's run method.
   *
   * @return void
   * @access public
   *
   */
  function run() {
    $this->_gid = CRM_Utils_Request::retrieve('gid', 'Positive',
      $this, FALSE, 0
    );
    $this->assign('gid', $this->_gid);

    if ($this->_gid) {
      //get optionGroup name in case of email/postal greeting or addressee, CRM-4575
      $this->_gName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', $this->_gid, 'name');

      $groupTitle = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', $this->_gid, 'title', 'id');
      // Some option groups do not have a title set
      if (!$groupTitle) {
        $groupTitle = $this->_gName;
      }
      CRM_Utils_System::setTitle(ts('%1 - Option Values', array(1 => $groupTitle)));
    }
    $breadCrumb = array(array('title' => ts('Option Groups'),
        'url' => CRM_Utils_System::url('civicrm/admin/options',
          'reset=1'
        ),
      ));
    CRM_Utils_System::appendBreadCrumb($breadCrumb);

    return parent::run();
  }

  /**
   * Browse all options value.
   *
   *
   * @return void
   * @access public
   * @static
   */
  function browse() {
    $dao = new CRM_Core_DAO_OptionValue();

    $dao->option_group_id = $this->_gid;

    if (in_array($this->_gName, CRM_Core_OptionGroup::$_domainIDGroups)) {
      $dao->domain_id = CRM_Core_Config::domainID();
    }

    if ($this->_gName == 'encounter_medium') {
      $mediumIds = CRM_Case_BAO_Case::getUsedEncounterMediums();
    }
    elseif ($this->_gName == 'case_status') {
      $caseStatusIds = CRM_Case_BAO_Case::getUsedCaseStatuses();
    }
    elseif ($this->_gName == 'case_type') {
      $caseTypeIds = CRM_Case_BAO_Case::getUsedCaseType();
    }

    $dao->orderBy('name');
    $dao->find();

    $optionValue = array();
    while ($dao->fetch()) {
      $optionValue[$dao->id] = array();
      CRM_Core_DAO::storeValues($dao, $optionValue[$dao->id]);
      // form all action links
      $action = array_sum(array_keys($this->links()));
      if ($dao->is_default) {
        $optionValue[$dao->id]['default_value'] = '[x]';
      }
      //do not show default option for email/postal greeting and addressee, CRM-4575
      if (!in_array($this->_gName, array(
        'email_greeting', 'postal_greeting', 'addressee'))) {
        $this->assign('showIsDefault', TRUE);
      }
      // update enable/disable links depending on if it is is_reserved or is_active
      if ($dao->is_reserved) {
        $action = CRM_Core_Action::UPDATE;
      }
      else {
        if ($dao->is_active) {
          $action -= CRM_Core_Action::ENABLE;
        }
        else {
          $action -= CRM_Core_Action::DISABLE;
        }

        if ((($this->_gName == 'encounter_medium') && in_array($dao->value, $mediumIds)) ||
          (($this->_gName == 'case_status') && in_array($dao->value, $caseStatusIds)) ||
          (($this->_gName == 'case_type') && in_array($dao->value, $caseTypeIds))
        ) {
          $action -= CRM_Core_Action::DELETE;
        }
      }

      $optionValue[$dao->id]['action'] = CRM_Core_Action::formLink(self::links(), $action,
        array('id' => $dao->id, 'gid' => $this->_gid)
      );
    }

    $this->assign('rows', $optionValue);
  }

  /**
   * Get name of edit form
   *
   * @return string Classname of edit form.
   */
  function editForm() {
    return 'CRM_Admin_Form_OptionValue';
  }

  /**
   * Get edit form name
   *
   * @return string name of this page.
   */
  function editName() {
    return 'Options Values';
  }

  /**
   * Get user context.
   *
   * @return string user context.
   */
  function userContext($mode = NULL) {
    return 'civicrm/admin/optionValue';
  }
}

