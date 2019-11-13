<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
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
    $this->add('select', 'petition_campaign_id', ts('Campaign'), ['' => ts('- select -')] + $campaigns);
    $this->set('petitionCampaigns', $campaigns);
    $this->assign('petitionCampaigns', json_encode($campaigns));

    //build the array of all search params.
    $this->_searchParams = [];
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
