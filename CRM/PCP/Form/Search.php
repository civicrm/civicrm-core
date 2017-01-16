<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                               |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 * $Id$
 *
 */
class CRM_PCP_Form_Search extends CRM_Core_Form {
  public $_context;

  public function preProcess() {
  }

  /**
   * @return array
   */
  public function setDefaultValues() {
    $defaults = array();
    $pageType = CRM_Utils_Request::retrieve('page_type', 'String', $this);
    $defaults['page_type'] = !empty($pageType) ? $pageType : '';

    return $defaults;
  }

  public function buildQuickForm() {
    $this->add('text', 'title', ts('Find'),
      CRM_Core_DAO::getAttribute('CRM_PCP_DAO_PCP', 'title')
    );
    
    $this->add('text', 'supporter', ts('Supporter Name or Email'),
      CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'sort_name')
    );
    
    $status = array('' => ts('- select -')) + CRM_Core_OptionGroup::values("pcp_status");
    $types = array(
      '' => ts('- select -'),
      'contribute' => ts('Contribution'),
      'event' => ts('Event'),
    );
    $contribPages = CRM_Contribute_PseudoConstant::contributionPage();
    $eventPages   = CRM_Event_PseudoConstant::event(NULL, FALSE, "( is_template IS NULL OR is_template != 1 )");

    $this->addElement('select', 'status_id', ts('Status'), $status);
    $this->addElement('select', 'page_type', ts('Source Type'), $types);
    $this->add('select', 'page_id', ts('Contribution Page'), $contribPages, FALSE, array('class' => 'crm-select2', 'placeholder' => ts('- any -')));
    $this->add('select', 'event_id', ts('Event Page'), $eventPages, FALSE, array('class' => 'crm-select2', 'placeholder' => ts('- any -')));
    $this->addDate('start_date', ts('Starts on or After'), FALSE);
    $this->addDate('end_date', ts('Ends on or Before'), FALSE);
    
    $this->addButtons(array(
      array(
        'type' => 'refresh',
        'name' => ts('Search'),
        'isDefault' => TRUE,
      ),
    ));

    parent::buildQuickForm();
    $this->assign('suppressForm', TRUE);
  }

  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);
    $parent = $this->controller->getParent();
    if (!empty($params)) {
      $fields = array('title', 'page_type', 'page_id', 'event_id', 'status_id','active_status', 'inactive_status');
      foreach ($fields as $field) {
        if (isset($params[$field]) &&
          !CRM_Utils_System::isNull($params[$field])
        ) {
          $parent->set($field, $params[$field]);
        }
        else {
          $parent->set($field, NULL);
        }
      }
    }
  }

}
