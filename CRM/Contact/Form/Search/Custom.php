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
class CRM_Contact_Form_Search_Custom extends CRM_Contact_Form_Search {

  protected $_customClass = NULL;

  public function preProcess() {
    $this->set('searchFormName', 'Custom');

    $this->set('context', 'custom');

    $csID = CRM_Utils_Request::retrieve('csid', 'Integer', $this);
    $ssID = CRM_Utils_Request::retrieve('ssID', 'Integer', $this);
    $gID = CRM_Utils_Request::retrieve('gid', 'Integer', $this);

    list($this->_customSearchID,
      $this->_customSearchClass,
      $formValues
    ) = CRM_Contact_BAO_SearchCustom::details($csID, $ssID, $gID);

    if (!$this->_customSearchID) {
      CRM_Core_Error::fatal('Could not get details for custom search.');
    }

    if (!empty($formValues)) {
      $this->_formValues = $formValues;
    }

    // set breadcrumb to return to Custom Search listings page
    $breadCrumb = array(array('title' => ts('Custom Searches'),
        'url' => CRM_Utils_System::url('civicrm/contact/search/custom/list',
          'reset=1'
        ),
      ));
    CRM_Utils_System::appendBreadCrumb($breadCrumb);

    // use the custom selector
    self::$_selectorName = 'CRM_Contact_Selector_Custom';

    $this->set('customSearchID', $this->_customSearchID);
    $this->set('customSearchClass', $this->_customSearchClass);

    parent::preProcess();

    // instantiate the new class
    eval('$this->_customClass = new ' . $this->_customSearchClass . '( $this->_formValues );');
  }

  function setDefaultValues() {
    if (method_exists($this->_customSearchClass, 'setDefaultValues')) {
      return $this->_customClass->setDefaultValues();
    }
    return $this->_formValues;
  }

  function buildQuickForm() {
    $this->_customClass->buildForm($this);

    parent::buildQuickForm();
  }

  function getTemplateFileName() {

    $ext = CRM_Extension_System::singleton()->getMapper();

    if ($ext->isExtensionClass(CRM_Utils_System::getClassName($this->_customClass))) {
      $fileName = $ext->getTemplatePath(CRM_Utils_System::getClassName($this->_customClass)) . '/' . $ext->getTemplateName(CRM_Utils_System::getClassName($this->_customClass));
    }
    else {
      $fileName = $this->_customClass->templateFile();
    }

    return $fileName ? $fileName : parent::getTemplateFileName();
  }

  function postProcess() {
    $this->set('isAdvanced', '3');
    $this->set('isCustom', '1');

    // get user submitted values
    // get it from controller only if form has been submitted, else preProcess has set this
    if (!empty($_POST)) {
      $this->_formValues = $this->controller->exportValues($this->_name);

      $this->_formValues['customSearchID'] = $this->_customSearchID;
      $this->_formValues['customSearchClass'] = $this->_customSearchClass;
    }

    //use the custom selector
    self::$_selectorName = 'CRM_Contact_Selector_Custom';

    parent::postProcess();
  }

  public function getTitle() {
    return ts('Custom Search');
  }
}

