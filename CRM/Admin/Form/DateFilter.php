<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
 +--------------------------------------------------------------------+
 | Copyright (C) 2011 Marty Wright                                    |
 | Licensed to CiviCRM under the Academic Free License version 3.0.   |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 */

/**
 * This class generates form components for Label Format Settings
 *
 */
class CRM_Admin_Form_DateFilter extends CRM_Admin_Form_Options {

  public function preProcess() {
    $this->set('gName', 'relative_date_filters');
    parent::preprocess();
    $session = CRM_Core_Session::singleton();
    CRM_Core_Resources::singleton()->addScriptFile('civicrm', 'templates/CRM/Admin/Page/DateFilter.js');
    $url = "civicrm/admin/relative_date_filters";
    $params = "reset=1";
    $session->pushUserContext(CRM_Utils_System::url($url, $params));

  }

  /**
   * Set default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   *
   * @return array
   */
  public function setDefaultValues() {
    // FIXME: Move this code into the BAO once we have a new syntax.
    $defaults = parent::setDefaultValues();
    $defaults['relative_terms'] = strstr($defaults['value'], ".", TRUE);
    $defaults['units'] = substr($defaults['value'], strpos($defaults['value'], ".")+1);

    return $defaults;
  }

  public function buildQuickForm() {
    parent::buildQuickForm();
    $this->add('select', 'relative_terms', ts('Relative Date Terms'), CRM_Core_SelectValues::getRelativeDateTerms(), FALSE, array('class' => 'required'));
    $this->add('select', 'units', ts('Units'), CRM_Core_SelectValues::getRelativeDateUnits(), FALSE, array('class' => 'required'));
    $this->addDate('preview_date', ts('Preview Date'), false);
  }

  public function postProcess() {
    $params = $this->exportValues();
    // FIXME: This line will change when we implement a new relative date filter
    // syntax.
    $params['value'] = $params['relative_terms'] . "." . $params['units'];
    $groupParams = array('name' => ($this->_gName));
    $optionValue = CRM_Core_OptionValue::addOptionValue($params, $groupParams, $this->_action, $this->_id);

    CRM_Core_Session::setStatus(ts('The %1 \'%2\' has been saved.', array(
      1 => $this->_gLabel,
      2 => $optionValue->label,
    )), ts('Saved'), 'success');

//    $this->controller->setDestination( $url ); 
    CRM_Core_Error::debug_var('xaxaxa', $this->controller);
  }
}
