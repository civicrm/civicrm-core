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
 * Form helper class for an Email object.
 */
class CRM_Contact_Form_Edit_Email {

  /**
   * Build the form object elements for an email object.
   *
   * @param CRM_Core_Form $form
   *   Reference to the form object.
   * @param int $blockCount
   *   Block number to build.
   * @param bool $blockEdit
   *   Is it block edit.
   */
  public static function buildQuickForm(&$form, $blockCount = NULL, $blockEdit = FALSE) {
    // passing this via the session is AWFUL. we need to fix this
    if (!$blockCount) {
      CRM_Core_Error::deprecatedWarning('pass in blockCount');
      $blockId = ($form->get('Email_Block_Count')) ? $form->get('Email_Block_Count') : 1;
    }
    else {
      $blockId = $blockCount;
    }

    $form->applyFilter('__ALL__', 'trim');

    //Email box
    $form->addField("email[$blockId][email]", [
      'entity' => 'email',
      'aria-label' => ts('Email %1', [1 => $blockId]),
      'label' => ts('Email %1', [1 => $blockId]),
    ]);
    $form->addRule("email[$blockId][email]", ts('Email is not valid.'), 'email');
    if (isset($form->_contactType) || $blockEdit) {
      //Block type
      $form->addField("email[$blockId][location_type_id]", ['entity' => 'email', 'placeholder' => NULL, 'class' => 'eight', 'option_url' => NULL, 'title' => ts('Email Location %1', [1 => $blockId]), 'type' => 'Select']);

      //TODO: Refactor on_hold field to select.
      $multipleBulk = CRM_Core_BAO_Email::isMultipleBulkMail();

      //On-hold select
      if ($multipleBulk) {
        $holdOptions = [
          0 => ts('- select -'),
          1 => ts('On Hold Bounce'),
          2 => ts('On Hold Opt Out'),
        ];
        $form->addElement('select', "email[$blockId][on_hold]", '', $holdOptions);
      }
      else {
        $form->addField("email[$blockId][on_hold]", ['entity' => 'email', 'type' => 'advcheckbox', 'aria-label' => ts('On Hold for Email %1?', [1 => $blockId])]);
      }

      //Bulkmail checkbox
      $form->assign('multipleBulk', $multipleBulk);
      $js = [
        'id' => 'Email_' . $blockId . '_IsBulkmail',
        'aria-label' => ts('Bulk Mailing for Email %1?', [1 => $blockId]),
        'onChange' => "if (CRM.$(this).is(':checked')) {
          CRM.$('.crm-email-bulkmail input').not(this).prop('checked', false);
        }",
      ];

      $form->addElement('advcheckbox', "email[$blockId][is_bulkmail]", NULL, '', $js);

      //is_Primary radio
      $js = [
        'id' => 'Email_' . $blockId . '_IsPrimary',
        'aria-label' => ts('Email %1 is primary?', [1 => $blockId]),
        'class' => 'crm-email-is_primary',
        'onChange' => "if (CRM.$(this).is(':checked')) {
          CRM.$('.crm-email-is_primary').not(this).prop('checked', false);
        }",
      ];

      $form->addElement('radio', "email[$blockId][is_primary]", '', '', '1', $js);
    }
  }

}
