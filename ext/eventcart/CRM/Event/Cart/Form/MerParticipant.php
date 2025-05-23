<?php

/**
 * Class CRM_Event_Cart_Form_MerParticipant
 * @fixme What is a MerParticipant!
 */
class CRM_Event_Cart_Form_MerParticipant extends CRM_Core_Form {

  /**
   * @var \CRM_Event_BAO_Participant
   */
  public $participant = NULL;

  /**
   * @param null|object $participant
   */
  public function __construct($participant) {
    parent::__construct();
    $this->participant = $participant;
  }

  /**
   * @param \CRM_Core_Form $form
   */
  public function appendQuickForm(&$form) {
    $textarea_size = ['size' => 30, 'maxlength' => 60];
    $form->add('text', $this->email_field_name(), ts('Email Address'), $textarea_size, TRUE);

    list($custom_fields_pre, $custom_fields_post) = $this->get_participant_custom_data_fields();

    foreach ($custom_fields_pre as $key => $field) {
      CRM_Core_BAO_UFGroup::buildProfile($form, $field, CRM_Profile_Form::MODE_CREATE, $this->participant->id);
    }
    foreach ($custom_fields_post as $key => $field) {
      CRM_Core_BAO_UFGroup::buildProfile($form, $field, CRM_Profile_Form::MODE_CREATE, $this->participant->id);
    }
    $custom = $form->getTemplateVars()['custom'] ?? [];
    $form->assign('custom', array_merge($custom, [
      $this->html_field_name('customPre') => $custom_fields_pre,
      $this->html_field_name('customPost') => $custom_fields_post,
      $this->html_field_name('number') => $this->name(),
    ]));
  }

  /**
   * @param int $event_id
   *
   * @return array
   */
  public static function get_profile_groups($event_id) {
    $ufJoinParams = [
      'entity_table' => 'civicrm_event',
      'module' => 'CiviEvent',
      'entity_id' => $event_id,
    ];
    $group_ids = CRM_Core_BAO_UFJoin::getUFGroupIds($ufJoinParams);
    return $group_ids;
  }

  /**
   * @return array
   * @throws \CRM_Core_Exception
   */
  public function get_participant_custom_data_fields() {
    list($custom_pre_id, $custom_post_id) = self::get_profile_groups($this->participant->event_id);

    $pre_fields = $post_fields = [];
    if ($custom_pre_id && CRM_Core_BAO_UFGroup::filterUFGroups($custom_pre_id, $this->participant->contact_id)) {
      $pre_fields = CRM_Core_BAO_UFGroup::getFields($custom_pre_id, FALSE, CRM_Core_Action::ADD);
    }
    if ($custom_post_id && CRM_Core_BAO_UFGroup::filterUFGroups($custom_post_id, $this->participant->contact_id)) {
      $post_fields = CRM_Core_BAO_UFGroup::getFields($custom_post_id, FALSE, CRM_Core_Action::ADD);
    }

    return [$pre_fields, $post_fields];
  }

  /**
   * @return string
   */
  public function email_field_name() {
    return $this->html_field_name("email");
  }

  /**
   * @param int $event_id
   * @param int $participant_id
   * @param string $field_name
   *
   * @return string
   */
  public static function full_field_name($event_id, $participant_id, $field_name) {
    return "event[$event_id][participant][$participant_id][$field_name]";
  }

  /**
   * @param string $field_name
   *
   * @return string
   */
  public function html_field_name($field_name) {
    return self::full_field_name($this->participant->event_id, $this->participant->id, $field_name);
  }

  /**
   * @return string
   */
  public function name() {
    return "Participant {$this->participant->get_participant_index()}";
  }

  /**
   * @param \CRM_Event_BAO_Participant $participant
   *
   * @return CRM_Event_Cart_Form_MerParticipant
   */
  public static function get_form($participant) {
    return new CRM_Event_Cart_Form_MerParticipant($participant);
  }

  /**
   * @return array
   * @throws \CRM_Core_Exception
   */
  public function setDefaultValues() {
    $defaults = [
      $this->html_field_name('email') => $this->participant->email,
    ];
    list($custom_fields_pre, $custom_fields_post) = $this->get_participant_custom_data_fields();
    $all_fields = $custom_fields_pre + $custom_fields_post;
    $flat = [];
    CRM_Core_BAO_UFGroup::setProfileDefaults($this->participant->contact_id, $all_fields, $flat);
    foreach ($flat as $name => $field) {
      $defaults["field[{$this->participant->id}][{$name}]"] = $field;
    }
    return $defaults;
  }

}
