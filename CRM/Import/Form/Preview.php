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
 * This class previews the uploaded file and returns summary statistics.
 *
 * TODO: CRM-11254 - if preProcess and postProcess functions can be reconciled between the 5 child classes,
 * those classes can be removed entirely and this class will not need to be abstract
 */
abstract class CRM_Import_Form_Preview extends CRM_Core_Form {

  /**
   * Return a descriptive name for the page, used in wizard header.
   *
   * @return string
   */
  public function getTitle() {
    return ts('Preview');
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {

    // FIXME: This is a hack...
    // The tpl contains javascript that starts the import on form submit
    // Since our back/cancel buttons are of html type "submit" we have to prevent a form submit event when they are clicked
    // Hacking in some onclick js to make them act more like links instead of buttons
    $path = CRM_Utils_System::currentPath();
    $query = ['_qf_MapField_display' => 'true'];
    $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String');
    if (CRM_Utils_Rule::qfKey($qfKey)) {
      $query['qfKey'] = $qfKey;
    }
    $previousURL = CRM_Utils_System::url($path, $query, FALSE, NULL, FALSE);
    $cancelURL = CRM_Utils_System::url($path, 'reset=1', FALSE, NULL, FALSE);

    $this->addButtons([
      [
        'type' => 'back',
        'name' => ts('Previous'),
        'js' => ['onclick' => "location.href='{$previousURL}'; return false;"],
      ],
      [
        'type' => 'next',
        'name' => ts('Import Now'),
        'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
        'js' => ['onclick' => "location.href='{$cancelURL}'; return false;"],
      ],
    ]);
  }

  /**
   * Set status url for ajax.
   */
  public function setStatusUrl() {
    $statusID = $this->get('statusID');
    if (!$statusID) {
      $statusID = md5(uniqid(rand(), TRUE));
      $this->set('statusID', $statusID);
    }
    $statusUrl = CRM_Utils_System::url('civicrm/ajax/status', "id={$statusID}", FALSE, NULL, FALSE);
    $this->assign('statusUrl', $statusUrl);
  }

}
