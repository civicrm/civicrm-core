<?php

require_once 'civi_member.civix.php';

function civi_member_civicrm_tabset($name, &$tabs, $context): void {
  if ($name === 'civicrm/admin/contribute') {
    if (!empty($context['contribution_page_id'])) {
      $tabs['membership'] = [
        'title' => ts('Memberships'),
        'weight' => -1,
        'link' => NULL,
        'valid' => FALSE,
        'active' => FALSE,
        'current' => FALSE,
        'class' => FALSE,
        'extra' => FALSE,
        'template' => FALSE,
        'count' => FALSE,
        'icon' => FALSE,
      ];
    }
    else {
      $tabs[\CRM_Core_Action::VIEW] = [
        'name' => ts('Membership Settings'),
        'title' => ts('Membership Settings'),
        'url' => $context['urlString'] . 'membership',
        'qs' => $context['urlParams'],
        'uniqueName' => 'membership',
        // This should come after Title but before thank-you and receipting.
        'weight' => -1,
      ];
    }
  }

}
