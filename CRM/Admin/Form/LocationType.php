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
 * This class generates form components for Location Type
 *
 */
class CRM_Admin_Form_LocationType extends CRM_Admin_Form {

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
    $this->add('text',
      'name',
      ts('Name'),
      CRM_Core_DAO::getAttribute('CRM_Core_DAO_LocationType', 'name'),
      TRUE
    );
    $this->addRule('name',
      ts('Name already exists in Database.'),
      'objectExists',
      array('CRM_Core_DAO_LocationType', $this->_id)
    );
    $this->addRule('name',
      ts('Name can only consist of alpha-numeric characters'),
      'variable'
    );

    $this->add('text', 'display_name', ts('Display Name'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_LocationType', 'display_name'));
    $this->add('text', 'vcard_name', ts('vCard Name'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_LocationType', 'vcard_name'));

    $this->add('text', 'description', ts('Description'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_LocationType', 'description'));

    $this->add('checkbox', 'is_active', ts('Enabled?'));
    $this->add('checkbox', 'is_default', ts('Default?'));
    if ($this->_action == CRM_Core_Action::UPDATE && CRM_Core_DAO::getFieldValue('CRM_Core_DAO_LocationType', $this->_id, 'is_reserved')) {
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
    CRM_Utils_System::flushCache('CRM_Core_DAO_LocationType');

    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Core_BAO_LocationType::del($this->_id);
      CRM_Core_Session::setStatus(ts('Selected Location type has been deleted.'), ts('Record Deleted'), 'success');
      return;
    }

    // store the submitted values in an array
    $params = $this->exportValues();
    $params['is_active'] = CRM_Utils_Array::value('is_active', $params, FALSE);
    $params['is_default'] = CRM_Utils_Array::value('is_default', $params, FALSE);

    // action is taken depending upon the mode
    $locationType = new CRM_Core_DAO_LocationType();
    $locationType->name = $params['name'];
    $locationType->display_name = $params['display_name'];
    $locationType->vcard_name = $params['vcard_name'];
    $locationType->description = $params['description'];
    $locationType->is_active = $params['is_active'];
    $locationType->is_default = $params['is_default'];

    if ($params['is_default']) {
      $query = "UPDATE civicrm_location_type SET is_default = 0";
      CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);
    }

    if ($this->_action & CRM_Core_Action::UPDATE) {
      $locationType->id = $this->_id;
    }

    $locationType->save();

    CRM_Core_Session::setStatus(ts("The location type '%1' has been saved.",
        array(1 => $locationType->name)
      ), ts('Saved'), 'success');
  }
}
