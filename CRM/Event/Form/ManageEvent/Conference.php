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
 * This class generates form components for Conference Slots.
 */
class CRM_Event_Form_ManageEvent_Conference extends CRM_Event_Form_ManageEvent {

  /**
   * Page action.
   */
  public $_action;

  /**
   * Build quick form.
   */
  public function buildQuickForm() {
    $slots = CRM_Core_OptionGroup::values('conference_slot');

    $this->add('select',
      'slot_label_id',
      ts('Conference Slot'),
      array(
        '' => ts('- select -'),
      ) + $slots,
      FALSE
    );

    $this->addEntityRef('parent_event_id', ts('Parent Event'), array(
        'entity' => 'event',
        'placeholder' => ts('- any -'),
        'select' => array('minimumInputLength' => 0),
      )
    );

    parent::buildQuickForm();
  }

  /**
   * Post process form.
   */
  public function postProcess() {
    $params = $this->exportValues();

    $params['id'] = $this->_id;
    CRM_Event_BAO_Event::add($params);

    parent::endPostProcess();
  }

  /**
   * Return a descriptive name for the page, used in wizard header.
   *
   * @return string
   */
  public function getTitle() {
    return ts('Conference Slots');
  }

}
