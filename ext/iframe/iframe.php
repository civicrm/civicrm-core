<?php

require_once 'iframe.civix.php';

use CRM_Iframe_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function iframe_civicrm_config(&$config, ?array $flags = NULL): void {
  _iframe_civix_civicrm_config($config);
  if ($flags['civicrm']) {
    \Civi::paths()->register('civicrm.iframe', function() {
      return [
        'path' => \Civi::paths()->getPath('[cms.root]/iframe.php'),
        'url' => \Civi::paths()->getUrl('[cms.root]/iframe.php', 'absolute'),
      ];
    });
  }
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function iframe_civicrm_install(): void {
  _iframe_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function iframe_civicrm_enable(): void {
  _iframe_civix_civicrm_enable();
}
