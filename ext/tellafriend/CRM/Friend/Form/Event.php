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
 * This class generates form components for Tell A Friend
 *
 */
class CRM_Friend_Form_Event extends CRM_Event_Form_ManageEvent {

  /**
   * Tell a friend id in db.
   *
   * @var int
   */
  public $_friendId;

  public function preProcess() {
    parent::preProcess();
    $this->setSelectedChild('friend');
  }

  /**
   * Set default values for the form.
   *
   *
   * @return void
   */
  public function setDefaultValues() {
    $defaults = [];

    if (isset($this->_id)) {
      $defaults['entity_table'] = 'civicrm_event';
      $defaults['entity_id'] = $this->_id;
      CRM_Friend_BAO_Friend::getValues($defaults);
      if (!empty($defaults['id'])) {
        $defaults['tf_id'] = $defaults['id'] ?? NULL;
        $this->_friendId = $defaults['tf_id'];
        // lets unset the 'id' since it conflicts with eventID (or contribID)
        // CRM-12667
        unset($defaults['id']);
      }
      $defaults['tf_title'] = $defaults['title'] ?? NULL;
      $defaults['tf_is_active'] = $defaults['is_active'] ?? NULL;
      $defaults['tf_thankyou_title'] = $defaults['thankyou_title'] ?? NULL;
      $defaults['tf_thankyou_text'] = $defaults['thankyou_text'] ?? NULL;
    }

    if (!$this->_friendId) {
      $defaults['intro'] = ts('Help us spread the word about this event. Use the space below to personalize your email message - let your friends know why you\'re attending. Then fill in the name(s) and email address(es) and click \'Send Your Message\'.');
      $defaults['suggested_message'] = ts('Thought you might be interested in checking out this event. I\'m planning on attending.');
      $defaults['tf_thankyou_text'] = ts('Thanks for spreading the word about this event to your friends.');
      $defaults['tf_title'] = ts('Tell a Friend');
      $defaults['tf_thankyou_title'] = ts('Thanks for Spreading the Word');
    }

    return $defaults;
  }

  /**
   * Build the form object.
   *
   * @return void
   */
  public function buildQuickForm() {
    if (isset($this->_id)) {
      $defaults['entity_table'] = 'civicrm_event';
      $defaults['entity_id'] = $this->_id;
      CRM_Friend_BAO_Friend::getValues($defaults);
      $this->_friendId = $defaults['id'] ?? NULL;
    }

    CRM_Friend_BAO_Friend::buildFriendForm($this);
    parent::buildQuickForm();
  }

  /**
   * Process the form submission.
   *
   *
   * @return void
   */
  public function postProcess() {
    // get the submitted form values.
    $formValues = $this->controller->exportValues($this->_name);

    // let's unset event id
    unset($formValues['id']);

    $formValues['entity_table'] = 'civicrm_event';
    $formValues['entity_id'] = $this->_id;
    $formValues['title'] = $formValues['tf_title'];
    $formValues['is_active'] = $formValues['tf_is_active'] ?? FALSE;
    $formValues['thankyou_title'] = $formValues['tf_thankyou_title'] ?? NULL;
    $formValues['thankyou_text'] = $formValues['tf_thankyou_text'] ?? NULL;

    if (($this->_action & CRM_Core_Action::UPDATE) && $this->_friendId) {
      $formValues['id'] = $this->_friendId;
    }

    CRM_Friend_BAO_Friend::addTellAFriend($formValues);

    // Update tab "disabled" css class
    $this->ajaxResponse['tabValid'] = !empty($formValues['tf_is_active']);

    parent::endPostProcess();
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   */
  public function getTitle() {
    return ts('Tell a Friend');
  }

}
