<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.2                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2012                                |
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
 * @copyright CiviCRM LLC (c) 2004-2012
 * $Id$
 *
 */

/**
 * This class generates form components for Contribution Type
 *
 */
class CRM_Contribute_Form_ContributionType extends CRM_Contribute_Form {

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    parent::buildQuickForm();

    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }

    $this->applyFilter('__ALL__', 'trim');
    $this->add('text', 'name', ts('Name'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_ContributionType', 'name'), TRUE);
    $this->addRule('name', ts('A contribution type with this name already exists. Please select another name.'), 'objectExists', array('CRM_Contribute_DAO_ContributionType', $this->_id));

    $this->add('text', 'description', ts('Description'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_ContributionType', 'description'));
    $this->add('text', 'accounting_code', ts('Accounting Code'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_ContributionType', 'accounting_code'));

    $this->add('checkbox', 'is_deductible', ts('Tax-deductible?'));
    $this->add('checkbox', 'is_active', ts('Enabled?'));

    if ($this->_action == CRM_Core_Action::UPDATE && CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionType', $this->_id, 'is_reserved')) {
      $this->freeze(array('name', 'description', 'is_active'));
    }
  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Contribute_BAO_ContributionType::del($this->_id);
      CRM_Core_Session::setStatus(ts('Selected contribution type has been deleted.'));
    }
    else {

      $params = $ids = array();
      // store the submitted values in an array
      $params = $this->exportValues();

      if ($this->_action & CRM_Core_Action::UPDATE) {
        $ids['contributionType'] = $this->_id;
      }

      $contributionType = CRM_Contribute_BAO_ContributionType::add($params, $ids);
      CRM_Core_Session::setStatus(ts('The contribution type \'%1\' has been saved.', array(1 => $contributionType->name)));
    }
  }
}

