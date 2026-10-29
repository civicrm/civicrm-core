<?php

require_once 'tellafriend.civix.php';

use CRM_Tellafriend_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function tellafriend_civicrm_config(&$config): void {
  _tellafriend_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function tellafriend_civicrm_install(): void {
  _tellafriend_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function tellafriend_civicrm_enable(): void {
  _tellafriend_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_tabset().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_tabset
 */
function tellafriend_civicrm_tabset($tabsetName, &$tabs, $context) {
  // This hook behaves in very different ways depending on the $context
  // and if we add our links here, the classic "Manage Contribution Pages"
  // will fatal because of odd logic on the 'bit' attribute implicitly set
  if (!empty($context['urlParams'])) {
    return;
  }
  if ($tabsetName === 'civicrm/event/manage' || $tabsetName === 'civicrm/admin/contribute') {
    $default = [
      'link' => NULL,
      'valid' => TRUE,
      'active' => TRUE,
      'current' => FALSE,
      'class' => FALSE,
      'extra' => FALSE,
      'template' => FALSE,
      'count' => FALSE,
      'icon' => FALSE,
      'url' => '',
      'field' => 'friend',
    ];
    $isFromBrowsePage = ($tabs['settings']['url'] ?? '') === 'civicrm/event/manage/settings' && empty($tabs['friend']);
    if ($isFromBrowsePage) {
      $default['url'] = 'civicrm/event/manage/friend';
    }
    $tabs['friend'] = ['title' => ts('Tell a Friend')] + $default;
  }
}

/**
 * Implements hook_civicrm_buildForm().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_buildForm/
 */
function tellafriend_civicrm_buildForm(string $formName, CRM_Core_Form $form): void {
  if ($formName === 'CRM_Event_Form_Registration_ThankYou') {
    $params = [
      'entity_id' => $form->getEventID(),
      'entity_table' => 'civicrm_event',
    ];
    $data = [];
    CRM_Friend_BAO_Friend::retrieve($params, $data);
    if (!empty($data['is_active'])) {
      $friendText = $data['title'];
      $action = $form->isTest() ? '&action=preview' : '';
      $friendURL = CRM_Utils_System::url('civicrm/friend',
        "eid={$form->getEventID()}&reset=1{$action}&pcomponent=event"
      );
      $html = '<div id="tell-a-friend" class="crm-section tell_friend_link-section">
            <a href="' . htmlentities($friendURL) . '" title="' . htmlentities($friendText) . '" class="button"><span><i class="crm-i fa-chevron-right" role="img" aria-hidden="true"></i> ' . CRM_Utils_String::purifyHTML($friendText) . '</span></a>
       </div><br /><br />';
      $extensionHtml = (array) $form->get_template_vars('extensionHtml');
      $extensionHtml[] = $html;
      $form->assign('extensionHtml', $extensionHtml);
    }
  }
  elseif ($formName === 'CRM_Contribute_Form_Contribution_ThankYou') {
    $values = [
      'entity_id' => $form->_id,
      'entity_table' => 'civicrm_contribution_page',
    ];
    $data = [];
    CRM_Friend_BAO_Friend::retrieve($values, $data);
    $tellAFriend = FALSE;
    $friendURL = NULL;
    $friendText = NULL;
    $subUrl = '';

    if ($form->_pcpId) {
      if (!empty($form->_pcpBlock['is_tellfriend_enabled'])) {
        $friendText = ts('Tell a Friend');
        $subUrl = "eid={$form->_pcpId}&blockId={$form->_pcpBlock['id']}&pcomponent=pcp";
        $tellAFriend = TRUE;
      }
    }
    elseif (!empty($data['is_active'])) {
      $friendText = $data['title'];
      $subUrl = "eid={$form->_id}&pcomponent=contribute";
      $tellAFriend = TRUE;
    }

    if ($tellAFriend) {
      if ($form->_action & CRM_Core_Action::PREVIEW) {
        $friendURL = CRM_Utils_System::url('civicrm/friend', "reset=1&action=preview&{$subUrl}");
      }
      else {
        $friendURL = CRM_Utils_System::url('civicrm/friend', "reset=1&{$subUrl}");
      }
      $form->assign('friendText', $friendText);
      $form->assign('friendURL', $friendURL);
    }
  }
}

/**
 * Implements hook_civicrm_check().
 */
function tellafriend_civicrm_check(&$messages) {
  $messages[] = new CRM_Utils_Check_Message(
    'tellafriend',
    ts('The Tell-a-Friend feature will be removed from CiviCRM in a future release. This feature allows users to send an email to their friends in order to promote an event or a contribution page. The feature was seldom used and it does not work well because of how email works today. It has also largely been replaced by social media links. If you do use this feature, please share your opinion on the CiviCRM Gitlab <a %1>issue dev/core#1036</a> or email info@civicrm.org. If the feature is not used, you can <a %1>disable this extension</a>. Otherwise, you can hide this alert for now. Depending on the feedback, the feature might simply be removed or it might be moved to a community-managed (third-party) extension.', [1 => 'href="https://lab.civicrm.org/dev/core/-/issues/1036" target="_blank"']),
    ts('Tell-a-Friend feature removal'),
    \Psr\Log\LogLevel::WARNING,
    'fa-flag'
  );
}
