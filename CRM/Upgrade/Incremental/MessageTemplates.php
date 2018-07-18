<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 */
class CRM_Upgrade_Incremental_MessageTemplates {

  /**
   * Version we are upgrading to.
   *
   * @var string
   */
  protected $upgradeVersion;

  /**
   * @return string
   */
  public function getUpgradeVersion() {
    return $this->upgradeVersion;
  }

  /**
   * @param string $upgradeVersion
   */
  public function setUpgradeVersion($upgradeVersion) {
    $this->upgradeVersion = $upgradeVersion;
  }

  /**
   * CRM_Upgrade_Incremental_MessageTemplates constructor.
   *
   * @param string $upgradeVersion
   */
  public function __construct($upgradeVersion) {
    $this->setUpgradeVersion($upgradeVersion);
  }

  /**
   * Get any templates that have been updated.
   *
   * @return array
   */
  protected function getTemplateUpdates() {
    return [
      [
        'version' => '5.4.alpha1',
        'upgrade_descriptor' => ts('Use email greeting at top where available'),
        'templates' => [
          ['name' => 'membership_online_receipt', 'type' => 'text'],
          ['name' => 'membership_online_receipt', 'type' => 'html'],
          ['name' => 'contribution_online_receipt', 'type' => 'text'],
          ['name' => 'contribution_online_receipt', 'type' => 'html'],
          ['name' => 'event_online_receipt', 'type' => 'text'],
          ['name' => 'event_online_receipt', 'type' => 'html'],
          ['name' => 'event_online_receipt', 'type' => 'subject'],
        ]
      ],
    ];
  }

  /**
   * Get any required template updates.
   *
   * @return array
   */
  public function getTemplatesToUpdate() {
    $templates = $this->getTemplateUpdates();
    $return = [];
    foreach ($templates as $templateArray) {
      if ($templateArray['version'] === $this->getUpgradeVersion()) {
        foreach ($templateArray['templates'] as $template) {
          $return[$template['name'] . '_' . $template['type']] = array_merge($template, $templateArray);
        }
      }
    }
    return $return;
  }

  /**
   * Get the upgrade messages.
   */
  public function getUpgradeMessages() {
    $updates = $this->getTemplatesToUpdate();
    $messages = [];
    foreach ($updates as $key => $value) {
      $templateLabel = civicrm_api3('OptionValue', 'getvalue', [
        'return' => 'label',
        'name' => $value['name'],
        'options' => ['limit' => 1],
      ]);
      $messages[$templateLabel] = $value['upgrade_descriptor'];
    }
    return $messages;
  }

  /**
   * Update message templates.
   */
  public function updateTemplates() {
    $templates = $this->getTemplatesToUpdate();
    foreach ($templates as $template) {
      $workFlowID = CRM_Core_DAO::singleValueQuery("SELECT MAX(id) as id FROM civicrm_option_value WHERE name = %1", [
        1 => [$template['name'], 'String'],
      ]);
      $content = file_get_contents(\Civi::paths()->getPath('[civicrm.root]/xml/templates/message_templates/' . $template['name'] . '_' . $template['type'] . '.tpl'));
      $templatesToUpdate = [];
      $templatesToUpdate[] = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_msg_template WHERE workflow_id = $workFlowID AND is_reserved = 1");
      $defaultTemplateID = CRM_Core_DAO::singleValueQuery("
        SELECT default_template.id FROM civicrm_msg_template reserved
        LEFT JOIN civicrm_msg_template default_template
          ON reserved.workflow_id = default_template.workflow_id
        WHERE reserved.workflow_id = $workFlowID
        AND reserved.is_reserved = 1 AND default_template.is_default = 1 AND reserved.id <> default_template.id
        AND reserved.msg_{$template['type']} = default_template.msg_{$template['type']}
      ");
      if ($defaultTemplateID) {
        $templatesToUpdate[] = $defaultTemplateID;
      }

      CRM_Core_DAO::executeQuery("
        UPDATE civicrm_msg_template SET msg_{$template['type']} = %1 WHERE id IN (" . implode(',', $templatesToUpdate) . ")", [
          1 => [$content, 'String']
          ]
      );
    }
  }

}
