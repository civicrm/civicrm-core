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
 *
 * @deprecated in CiviCRM 5.66, will be removed around CiviCRM 5.76.
 */
class CRM_Contact_Form_Location {

  /**
   * Build the form object.
   *
   * @deprecated in CiviCRM 5.66, will be removed around CiviCRM 5.76.
   * @param CRM_Core_Form $form
   */
  public static function buildQuickForm(&$form) {
    CRM_Core_Error::deprecatedFunctionWarning('internal core function, take a copy');
    // required for subsequent AJAX requests.
    $ajaxRequestBlocks = [];
    $generateAjaxRequest = 0;

    //build 1 instance of all blocks, without using ajax ...
    foreach ($form->_blocks as $blockName => $label) {
      $name = strtolower($blockName);

      $instances = [1];
      if (!empty($_POST[$name]) && is_array($_POST[$name])) {
        $instances = array_keys($_POST[$name]);
      }
      elseif (property_exists($form, '_values') && !empty($form->_values[$name]) && is_array($form->_values[$name])) {
        $instances = array_keys($form->_values[$name]);
      }

      foreach ($instances as $instance) {
        if ($instance == 1) {
          $form->assign('addBlock', FALSE);
          $form->assign('blockId', $instance);
        }
        else {
          //we are going to build other block instances w/ AJAX
          $generateAjaxRequest++;
          $ajaxRequestBlocks[$blockName][$instance] = TRUE;
        }
        switch ($blockName) {
          case 'Email':
            // setDefaults uses this to tell which instance
            $form->set('Email_Block_Count', $instance);
            CRM_Contact_Form_Edit_Email::buildQuickForm($form, $instance);
            // Only display the signature fields if this contact has a CMS account
            // because they can only send email if they have access to the CRM
            $ufID = $form->_contactId && CRM_Core_BAO_UFMatch::getUFId($form->_contactId);
            $form->assign('isAddSignatureFields', (bool) $ufID);
            if ($ufID) {
              $form->add('textarea', "email[$instance][signature_text]", ts('Signature (Text)'),
                ['rows' => 2, 'cols' => 40]
              );
              $form->add('wysiwyg', "email[$instance][signature_html]", ts('Signature (HTML)'),
                ['rows' => 2, 'cols' => 40]
              );
            }
            break;

          default:
            // @todo This pattern actually adds complexity compared to filling out a switch statement
            // for the limited number of blocks - as we also have to receive the block count
            $form->set($blockName . '_Block_Count', $instance);
            $formName = 'CRM_Contact_Form_Edit_' . $blockName;
            $formName::buildQuickForm($form);
        }

      }
    }

    //assign to generate AJAX request for building extra blocks.
    $form->assign('generateAjaxRequest', $generateAjaxRequest);
    $form->assign('ajaxRequestBlocks', $ajaxRequestBlocks);
  }

}
