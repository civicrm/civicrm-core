<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
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
 */

/**
 * Files required.
 */
class CRM_Campaign_Form_Search_Petition extends CRM_Core_Form {

  /**
   * Are we forced to run a search.
   *
   * @var int
   */
  protected $_force;

  /**
   * Processing needed for buildForm and later.
   */
  public function preProcess() {
    $this->_search = CRM_Utils_Array::value('search', $_GET);
    $this->_force = CRM_Utils_Request::retrieve('force', 'Boolean', $this, FALSE, FALSE);
    $this->_searchTab = CRM_Utils_Request::retrieve('type', 'String', $this, FALSE, 'petition');

    //when we do load tab, lets load the default objects.
    $this->assign('force', ($this->_force || $this->_searchTab) ? TRUE : FALSE);
    $this->assign('searchParams', json_encode($this->get('searchParams')));
    $this->assign('buildSelector', $this->_search);
    $this->assign('searchFor', $this->_searchTab);
    $this->assign('petitionCampaigns', json_encode($this->get('petitionCampaigns')));
    $this->assign('suppressForm', TRUE);

    //set the form title.
    CRM_Utils_System::setTitle(ts('Find Petition'));
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    if ($this->_search) {
      return;
    }

    $attributes = CRM_Core_DAO::getAttribute('CRM_Campaign_DAO_Survey');
    $this->add('text', 'petition_title', ts('Title'), $attributes['title']);

    //campaigns
    $campaigns = CRM_Campaign_BAO_Campaign::getCampaigns(NULL, NULL, FALSE, FALSE, FALSE, TRUE);
    $this->add('select', 'petition_campaign_id', ts('Campaign'), array('' => ts('- select -')) + $campaigns);
    $this->set('petitionCampaigns', $campaigns);
    $this->assign('petitionCampaigns', json_encode($campaigns));

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
