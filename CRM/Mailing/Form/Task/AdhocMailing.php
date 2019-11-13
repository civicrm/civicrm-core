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
 * Given the selected contacts, prepare a mailing with a hidden group.
 */
class CRM_Mailing_Form_Task_AdhocMailing extends CRM_Contact_Form_Task {

  public function preProcess() {
    parent::preProcess();
    $templateTypes = CRM_Mailing_BAO_Mailing::getTemplateTypes();
    list ($groupId, $ssId) = $this->createHiddenGroup();
    $mailing = civicrm_api3('Mailing', 'create', [
      'name' => "",
      'campaign_id' => NULL,
      'replyto_email' => "",
      'template_type' => $templateTypes[0]['name'],
      'template_options' => ['nonce' => 1],
      'subject' => "",
      'body_html' => "",
      'body_text' => "",
      'groups' => [
        'include' => [$groupId],
        'exclude' => [],
        'base' => [],
      ],
      'mailings' => [
        'include' => [],
        'exclude' => [],
      ],
    ]);

    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/a/', NULL, TRUE, '/mailing/' . $mailing['id']));
  }

}
