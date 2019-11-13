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
class CRM_Campaign_Form_Search_Campaign extends CRM_Core_Form {

  /**
   * Explicitly declare the entity api name.
   *
   * @return string
   */
  public function getDefaultEntity() {
    return 'Campaign';
  }

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
   * Build the form object.
   */
  public function buildQuickForm() {
    if ($this->_search) {
      return;
    }

    $attributes = CRM_Core_DAO::getAttribute('CRM_Campaign_DAO_Campaign');
    $this->add('text', 'campaign_title', ts('Title'), $attributes['title']);

    //campaign description.
    $this->add('text', 'description', ts('Description'), $attributes['description']);

    $this->add('datepicker', 'start_date', ts('Campaign Start Date'), [], FALSE, ['time' => FALSE]);
    $this->add('datepicker', 'end_date', ts('Campaign End Date'), [], FALSE, ['time' => FALSE]);

    //campaign type.
    $campaignTypes = CRM_Campaign_PseudoConstant::campaignType();
    $this->add('select', 'campaign_type_id', ts('Campaign Type'),
      [
        '' => ts('- select -'),
      ] + $campaignTypes
    );

    $this->set('campaignTypes', $campaignTypes);
    $this->assign('campaignTypes', json_encode($campaignTypes));

    //campaign status
    $campaignStatus = CRM_Campaign_PseudoConstant::campaignStatus();
    $this->addElement('select', 'status_id', ts('Campaign Status'),
      [
        '' => ts('- select -'),
      ] + $campaignStatus
    );
    $this->set('campaignStatus', $campaignStatus);
    $this->assign('campaignStatus', json_encode($campaignStatus));

    //active campaigns
    $this->addElement('select', 'is_active', ts('Is Active?'), [
      '' => ts('- select -'),
      '0' => ts('Yes'),
      '1' => ts('No'),
    ]);

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
