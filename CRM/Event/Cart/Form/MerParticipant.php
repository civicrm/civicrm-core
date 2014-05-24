<?php

/**
 * Class CRM_Event_Cart_Form_MerParticipant
 */
class CRM_Event_Cart_Form_MerParticipant extends CRM_Core_Form {
  public $participant = NULL;

  /**
   * @param null|object $participant
   */
  function __construct($participant) {
    parent::__construct();
    //XXX
    $this->participant = $participant;
  }

  /**
   * @param $form
   */
  function appendQuickForm(&$form) {
    $textarea_size = array('size' => 30, 'maxlength' => 60);
    $form->add('text', $this->email_field_name(), ts('Email Address'), $textarea_size, TRUE);

    list(
      $custom_fields_pre,
      $custom_fields_post
    ) = $this->get_participant_custom_data_fields($this->participant->event_id);

    foreach ($custom_fields_pre as $key => $field) {
      CRM_Core_BAO_UFGroup::buildProfile($form, $field, CRM_Profile_Form::MODE_CREATE, $this->participant->id);
    }
    foreach ($custom_fields_post as $key => $field) {
      CRM_Core_BAO_UFGroup::buildProfile($form, $field, CRM_Profile_Form::MODE_CREATE, $this->participant->id);
    }
    $custom = CRM_Utils_Array::value('custom', $form->getTemplate()->_tpl_vars, array());
    $form->assign('custom', array_merge($custom, array(
          $this->html_field_name('customPre') => $custom_fields_pre,
          $this->html_field_name('customPost') => $custom_fields_post,
          $this->html_field_name('number') => $this->name(),
        )));
  }

  /**
   * @param $event_id
   *
   * @return array
   */
  function get_profile_groups($event_id) {
    $ufJoinParams = array(
      'entity_table' => 'civicrm_event',
      'module' => 'CiviEvent',
      'entity_id' => $event_id,
    );
    $group_ids = CRM_Core_BAO_UFJoin::getUFGroupIds($ufJoinParams);
    return $group_ids;
  }

  /**
   * @return array
   */
  function get_participant_custom_data_fields() {
    list($custom_pre_id, $custom_post_id) = self::get_profile_groups($this->participant->event_id);

    $pre_fields = $post_fields = array();
    if ($custom_pre_id && CRM_Core_BAO_UFGroup::filterUFGroups($custom_pre_id, $this->participant->contact_id)) {
      $pre_fields = CRM_Core_BAO_UFGroup::getFields($custom_pre_id, FALSE, CRM_Core_Action::ADD);
    }
    if ($custom_post_id && CRM_Core_BAO_UFGroup::filterUFGroups($custom_post_id, $this->participant->contact_id)) {
      $post_fields = CRM_Core_BAO_UFGroup::getFields($custom_post_id, FALSE, CRM_Core_Action::ADD);
    }

    return array($pre_fields, $post_fields);
  }

  /**
   * @return string
   */
  function email_field_name() {
    return $this->html_field_name("email");
  }

  /**
   * @param $event_id
   * @param $participant_id
   * @param $field_name
   *
   * @return string
   */
  static function full_field_name($event_id, $participant_id, $field_name) {
    return "event[$event_id][participant][$participant_id][$field_name]";
  }

  /**
   * @param $field_name
   *
   * @return string
   */
  function html_field_name($field_name) {
    return self::full_field_name($this->participant->event_id, $this->participant->id, $field_name);
  }

  /**
   * @return string
   */
  function name() {
    return "Participant {$this->participant->get_participant_index()}";
  }

  //XXX poor name
  /**
   * @param $participant
   *
   * @return CRM_Event_Cart_Form_MerParticipant
   */
  static public function get_form($participant) {
    return new CRM_Event_Cart_Form_MerParticipant($participant);
  }

  /**
   * @return array
   */
  function setDefaultValues() {
    $defaults = array(
      $this->html_field_name('email') => $this->participant->email,
    );
    list($custom_fields_pre, $custom_fields_post) = $this->get_participant_custom_data_fields($this->participant->event_id);
    $all_fields = $custom_fields_pre + $custom_fields_post;
    $flat = array();
    CRM_Core_BAO_UFGroup::setProfileDefaults($this->participant->contact_id, $all_fields, $flat);
    foreach ($flat as $name => $field) {
      $defaults["field[{$this->participant->id}][{$name}]"] = $field;
    }
    return $defaults;
  }
}

