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
class CRM_Friend_Form_Contribute extends CRM_Contribute_Form_ContributionPage {

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
   * Set default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   *
   * @return void
   */
  public function setDefaultValues() {
    $defaults = [];

    if (isset($this->_id)) {
      $defaults['entity_table'] = 'civicrm_contribution_page';
      $defaults['entity_id'] = $this->_id;
      CRM_Friend_BAO_Friend::getValues($defaults);
      $this->_friendId = $defaults['id'] ?? NULL;
      $defaults['tf_title'] = $defaults['title'] ?? NULL;
      $defaults['tf_is_active'] = $defaults['is_active'] ?? NULL;
      $defaults['tf_thankyou_title'] = $defaults['thankyou_title'] ?? NULL;
      $defaults['tf_thankyou_text'] = $defaults['thankyou_text'] ?? NULL;
    }

    if (!$this->_friendId) {
      $defaults['intro'] = ts('Help us spread the word and leverage the power of your contribution by telling your friends. Use the space below to personalize your email message - let your friends know why you support us. Then fill in the name(s) and email address(es) and click \'Send Your Message\'.');
      $defaults['suggested_message'] = ts('Thought you might be interested in learning about and helping this organization. I think they do important work.');
      $defaults['tf_thankyou_text'] = ts('Thanks for telling your friends about us and supporting our efforts. Together we can make a difference.');
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
      $defaults['entity_table'] = 'civicrm_contribution_page';
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

    $formValues['entity_table'] = 'civicrm_contribution_page';
    $formValues['entity_id'] = $this->_id;
    $formValues['title'] = $formValues['tf_title'];
    $formValues['is_active'] = CRM_Utils_Array::value('tf_is_active', $formValues, FALSE);
    $formValues['thankyou_title'] = $formValues['tf_thankyou_title'] ?? NULL;
    $formValues['thankyou_text'] = $formValues['tf_thankyou_text'] ?? NULL;

    if (($this->_action & CRM_Core_Action::UPDATE) && $this->_friendId) {
      $formValues['id'] = $this->_friendId;
    }

    CRM_Friend_BAO_Friend::addTellAFriend($formValues);
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
