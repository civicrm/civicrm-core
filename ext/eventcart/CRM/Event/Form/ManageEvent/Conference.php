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
use Civi\Api4\Event;

/**
 * This class generates form components for Conference Slots.
 */
class CRM_Event_Form_ManageEvent_Conference extends CRM_Event_Form_ManageEvent {

  /**
   * Page action.
   * @var int
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
      [
        '' => ts('- select -'),
      ] + $slots,
      FALSE
    );

    $this->addEntityRef('parent_event_id', ts('Parent Event'), [
      'entity' => 'Event',
      'placeholder' => ts('- any -'),
      'select' => ['minimumInputLength' => 0],
    ]);

    parent::buildQuickForm();
  }

  /**
   * Post process form.
   */
  public function postProcess() {
    $params = $this->exportValues();

    $params['id'] = $this->_id;
    Event::save(FALSE)->addRecord($params)->execute();

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
