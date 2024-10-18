<?php

require_once 'eventcart.civix.php';
use CRM_Event_Cart_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function eventcart_civicrm_config(&$config) {
  if (isset(Civi::$statics[__FUNCTION__])) {
    return;
  }
  Civi::$statics[__FUNCTION__] = 1;
  Civi::dispatcher()->addListener('hook_civicrm_pageRun', ['CRM_Event_Cart_PageCallback', 'run']);

  _eventcart_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function eventcart_civicrm_install() {
  _eventcart_civix_civicrm_install();
}

/**
 * Add the conference session variable to the template.
 *
 * @param array $params
 * @param string $template
 */
function eventcart_civicrm_alterMailParams(&$params, $template) {
  $workflow = $params['workflow'] ?? '';
  if (($workflow === 'event_online_receipt' || $workflow === 'participant_confirm') && !empty($params['tokenContact']['participant']['id'])) {
    $params['tplParams']['conference_sessions'] = CRM_Event_Cart_BAO_Conference::get_participant_sessions($params['tokenContact']['participant']['id']);
  }
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function eventcart_civicrm_enable() {
  _eventcart_civix_civicrm_enable();
}

function eventcart_civicrm_tabset($name, &$tabs) {
  if ($name === 'civicrm/event/manage') {
    $tabs['conference'] = [
      'title' => E::ts('Conference Slots'),
      'link' => NULL,
      'valid' => TRUE,
      'active' => TRUE,
      'current' => FALSE,
      'class' => 'ajaxForm',
      'url' => 'civicrm/event/manage/conference',
      'field' => 'slot_label_id',
    ];
  }
}
