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
class CRM_Campaign_Form_Search_Campaign extends CRM_Core_Form {

  /**
   * Are we forced to run a search
   *
   * @var int
   * @access protected
   */
  protected $_force;

  /**
   * processing needed for buildForm and later
   *
   * @return void
   * @access public
   */ function preProcess() {
    $this->_search    = CRM_Utils_Array::value('search', $_GET);
    $this->_force     = CRM_Utils_Request::retrieve('force', 'Boolean', $this, FALSE, FALSE);
    $this->_searchTab = CRM_Utils_Request::retrieve('type', 'String', $this, FALSE, 'campaign');

    //when we do load tab, lets load the default objects.
    $this->assign('force', ($this->_force || $this->_searchTab) ? TRUE : FALSE);
    $this->assign('searchParams', json_encode($this->get('searchParams')));
    $this->assign('buildSelector', $this->_search);
    $this->assign('searchFor', $this->_searchTab);
    $this->assign('campaignTypes', json_encode($this->get('campaignTypes')));
    $this->assign('campaignStatus', json_encode($this->get('campaignStatus')));
    $this->assign('suppressForm', TRUE);

    //set the form title.
    CRM_Utils_System::setTitle(ts('Find Campaigns'));
  }

  /**
   * Build the form
   *
   * @access public
   *
   * @return void
   */
  function buildQuickForm() {
    if ($this->_search) {
      return;
    }

    $attributes = CRM_Core_DAO::getAttribute('CRM_Campaign_DAO_Campaign');
    $this->add('text', 'campaign_title', ts('Title'), $attributes['title']);

    //campaign description.
    $this->add('text', 'description', ts('Description'), $attributes['description']);

    //campaign start date.
    $this->addDate('start_date', ts('From'), FALSE, array('formatType' => 'searchDate'));

    //campaign end date.
    $this->addDate('end_date', ts('To'), FALSE, array('formatType' => 'searchDate'));

    //campaign type.
    $campaignTypes = CRM_Campaign_PseudoConstant::campaignType();
    $this->add('select', 'campaign_type_id', ts('Campaign Type'),
      array(
        '' => ts('- select -')) + $campaignTypes
    );

    $this->set('campaignTypes', $campaignTypes);
    $this->assign('campaignTypes', json_encode($campaignTypes));

    //campaign status
    $campaignStatus = CRM_Campaign_PseudoConstant::campaignStatus();
    $this->addElement('select', 'status_id', ts('Campaign Status'),
      array(
        '' => ts('- select -')) + $campaignStatus
    );
    $this->set('campaignStatus', $campaignStatus);
    $this->assign('campaignStatus', json_encode($campaignStatus));

    //build the array of all search params.
    $this->_searchParams = array();
    foreach ($this->_elements as $element) {
      $name = $element->_attributes['name'];
      $label = $element->_label;
      if ($name == 'qfKey') {
        continue;
      }
      $this->_searchParams[$name] = ($label) ? $label : $name;
    }
    $this->set('searchParams', $this->_searchParams);
    $this->assign('searchParams', json_encode($this->_searchParams));
  }
}

