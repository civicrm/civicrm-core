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
 * Form helper class for an Communication Preferences object.
 */
class CRM_Contact_Form_Edit_CommunicationPreferences {

  /**
   * Greetings.
   *
   * @var array
   */
  public static $greetings = [];

  /**
   * Build the form object elements for Communication Preferences object.
   *
   * @param CRM_Core_Form $form
   *   Reference to the form object.
   */
  public static function buildQuickForm(&$form) {
    // since the pcm - preferred communication method is logically
    // grouped hence we'll use groups of HTML_QuickForm

    // checkboxes for DO NOT phone, email, mail
    // we take labels from SelectValues
    $privacy = $commPreference = [];

    $form->addField('preferred_language');

    $privacyOptions = CRM_Core_SelectValues::privacy();

    // Add is_opt_out as a separate checkbox
    $form->addField('is_opt_out', ['label' => $privacyOptions['is_opt_out']]);
    unset($privacyOptions['is_opt_out']);

    foreach ($privacyOptions as $name => $label) {
      $privacy[] = $form->createElement('advcheckbox', $name, NULL, $label);
    }
    if (!empty($privacyOptions)) {
      $form->addGroup($privacy, 'privacy', ts('Privacy'), '&nbsp;<br/>');
      $commPreference['privacy'] = $privacyOptions;
    }

    // preferred communication method
    $comm = CRM_Contact_BAO_Contact::buildOptions('preferred_communication_method');
    if (!empty($comm)) {
      $form->addField('preferred_communication_method', ['type' => 'CheckBoxGroup']);
      $commPreference['preferred_communication_method'] = $comm;
    }

    //using for display purpose.
    $form->assign('commPreference', $commPreference);

    $form->addField('communication_style_id', ['type' => 'RadioGroup']);
    //check contact type and build filter clause accordingly for greeting types, CRM-4575
    $greetings = self::getGreetingFields($form->_contactType);

    foreach ($greetings as $greeting => $fields) {
      $filter = [
        'contact_type' => $form->_contactType,
        'greeting_type' => $greeting,
      ];

      // Add addressee in Contact form.
      $greetingTokens = CRM_Core_PseudoConstant::greeting($filter);

      // Instead of showing smarty token with/o conditional logic in Drop down
      // list, show processed token (Only in Contact Edit mode).
      // Get Description of each greeting.
      $greetingTokensDescription = CRM_Core_PseudoConstant::greeting($filter, 'description');
      if ($form->_contactId) {
        $renderedGreetingTokens = CRM_Core_TokenSmarty::render($greetingTokens,
          [
            'contactId' => $form->_contactId,
          ]
        );
        foreach ($greetingTokens as $key => &$emailGreetingString) {
          if ($emailGreetingString) {
            $emailGreetingString = $renderedGreetingTokens[$key];
            $emailGreetingString = CRM_Core_DAO::escapeString(CRM_Utils_String::stripSpaces($emailGreetingString));
            if (!empty($greetingTokensDescription[$key])) {
              // Append description to processed greeting.
              $emailGreetingString .= ' ( ' . $greetingTokensDescription[$key] . ' )';
            }
          }
        }
      }
      if (!empty($greetingTokens)) {
        $form->addElement('select', $fields['field'], $fields['label'],
          [
            '' => ts('- select -'),
          ] + $greetingTokens
        );
        //custom addressee
        $form->addElement('text', $fields['customField'], $fields['customLabel'],
          CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', $fields['customField']), $fields['js']
        );
      }
    }
  }

  /**
   * Global form rule.
   *
   * @param array $fields
   *   The input form values.
   * @param array $files
   *   The uploaded files if any.
   * @param CRM_Contact_Form_Edit_CommunicationPreferences $self
   *
   * @return bool|array
   *   true if no errors, else array of errors
   */
  public static function formRule($fields, $files, $self) {
    //CRM-4575

    $greetings = self::getGreetingFields($self->_contactType);
    foreach ($greetings as $greeting => $details) {
      $customizedValue = CRM_Core_PseudoConstant::getKey('CRM_Contact_BAO_Contact', $details['field'], 'Customized');
      if (($fields[$details['field']] ?? NULL) == $customizedValue && empty($fields[$details['customField']])) {
        $errors[$details['customField']] = ts('Custom  %1 is a required field if %1 is of type Customized.',
          [1 => $details['label']]
        );
      }
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Set default values for the form.
   *
   * @param CRM_Core_Form $form
   * @param array $defaults
   */
  public static function setDefaultValues(&$form, &$defaults) {

    if (!empty($defaults['preferred_language'])) {
      $languages = CRM_Contact_BAO_Contact::buildOptions('preferred_language');
      $defaults['preferred_language'] = CRM_Utils_Array::key($defaults['preferred_language'], $languages);
    }

    // CRM-7119: set preferred_language to default if unset
    if (empty($defaults['preferred_language'])) {
      if ($form->_action == CRM_Core_Action::ADD) {
        if (($defContactLanguage = CRM_Core_I18n::getContactDefaultLanguage()) != FALSE) {
          $defaults['preferred_language'] = $defContactLanguage;
        }
      }
    }

    if (empty($defaults['communication_style_id'])) {
      $defaults['communication_style_id'] = array_pop(CRM_Core_OptionGroup::values('communication_style', TRUE, NULL, NULL, 'AND is_default = 1'));
    }

    //set default from greeting types CRM-4575, CRM-9739
    if ($form->_action & CRM_Core_Action::ADD) {
      foreach (CRM_Contact_BAO_Contact::$_greetingTypes as $greeting) {
        if (empty($defaults[$greeting . '_id'])) {
          if ($defaultGreetingTypeId = CRM_Contact_BAO_Contact_Utils::defaultGreeting($form->_contactType, $greeting)
          ) {
            $defaults[$greeting . '_id'] = $defaultGreetingTypeId;
          }
        }
      }
    }
    else {
      foreach (CRM_Contact_BAO_Contact::$_greetingTypes as $greeting) {
        $name = "{$greeting}_display";
        $form->assign($name, $defaults[$name] ?? NULL);
      }
    }
  }

  /**
   *  Set array of greeting fields.
   *
   * @param string $contactType
   */
  public static function getGreetingFields($contactType) {
    if (empty(self::$greetings[$contactType])) {
      self::$greetings[$contactType] = [];

      $js = [
        'onfocus' => "if (!this.value) {  this.value='Dear ';} else return false",
        'onblur' => "if ( this.value == 'Dear') {  this.value='';} else return false",
      ];

      self::$greetings[$contactType] = [
        'addressee' => [
          'field' => 'addressee_id',
          'customField' => 'addressee_custom',
          'label' => ts('Addressee'),
          'customLabel' => ts('Custom Addressee'),
          'js' => NULL,
        ],
        'email_greeting' => [
          'field' => 'email_greeting_id',
          'customField' => 'email_greeting_custom',
          'label' => ts('Email Greeting'),
          'customLabel' => ts('Custom Email Greeting'),
          'js' => $js,
        ],
        'postal_greeting' => [
          'field' => 'postal_greeting_id',
          'customField' => 'postal_greeting_custom',
          'label' => ts('Postal Greeting'),
          'customLabel' => ts('Custom Postal Greeting'),
          'js' => $js,
        ],
      ];
    }

    return self::$greetings[$contactType];
  }

}
