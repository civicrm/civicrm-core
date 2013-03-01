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
 * Files required
 */
class CRM_Contact_Form_Search_Simple extends CRM_Core_Form {
  protected $_params;

  public function preProcess() {
    $this->assign('rows', $this->get('rows'));

    $this->_params = $this->controller->exportValues($this->_name);
  }

  public function buildQuickForm() {
    $config = CRM_Core_Config::singleton();

    $this->add('select',
      'country_id',
      ts('Country'),
      array('' => ts('- select -')) + CRM_Core_PseudoConstant::country()
    );

    $countryID = isset($_POST['country_id']) ? $_POST['country_id'] : NULL;
    if (!$countryID) {
      $countryID = isset($this->_params['country_id']) ? $this->_params['country_id'] : NULL;
    }
    if ($countryID) {
      $this->add('select',
        'state_province_id',
        ts('State'),
        array('' => ts('- select a state -')) + CRM_Core_PseudoConstant::stateProvinceForCountry($countryID)
      );
    }
    else {
      $this->add('select',
        'state_province_id',
        ts('State'),
        array('' => ts('- select a country first -'))
      );
    }

    $stateCountryURL = CRM_Utils_System::url('civicrm/ajax/jqState');
    $this->assign('stateCountryURL', $stateCountryURL);
    $this->addButtons(array(
        array(
          'type' => 'refresh',
          'name' => ts('Search'),
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );
  }

  public function postProcess() {
    $this->_params = $this->controller->exportValues($this->_name);
    CRM_Core_Error::debug($this->_params);
    CRM_Utils_System::civiExit();
  }
}

