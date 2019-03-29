<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
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
