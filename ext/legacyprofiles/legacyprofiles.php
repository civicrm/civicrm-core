<?php

require_once 'legacyprofiles.civix.php';

use CRM_Legacyprofiles_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function legacyprofiles_civicrm_config(&$config): void {
  _legacyprofiles_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function legacyprofiles_civicrm_install(): void {
  _legacyprofiles_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_ufGroupTypes().
 *
 * A standalone profile can also be exposed as a directory listing while this extension is enabled.
 */
function legacyprofiles_civicrm_ufGroupTypes(array &$ufGroupTypes): void {
  if (isset($ufGroupTypes['Profile'])) {
    $ufGroupTypes['Profile'] = ts('Standalone Form or Directory');
  }
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function legacyprofiles_civicrm_enable(): void {
  _legacyprofiles_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_buildForm().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_buildForm
 */
function legacyprofiles_civicrm_buildForm($formName, $form): void {
  if ($formName === 'CRM_UF_Form_Group') {
    $group = ['' => ts('- select -')] + CRM_Core_PseudoConstant::group();
    $form->addElement('select', 'limit_listings_group_id', ts('Limit listings to a specific Group'), $group);
    // Options for Profile Listings
    $form->addElement('advcheckbox', 'is_edit_link', ts('Include profile edit links in search results'));
    $form->addElement('advcheckbox', 'is_uf_link', ts('Include user account information links in search results'));
    $form->addElement('select', 'is_proximity_search', ts('Proximity Search'), [ts('Disabled'), ts('Optional'), ts('Required')]);

    CRM_Core_Region::instance('profile-settings-form')->add([
      'template' => 'CRM/Legacyprofiles/Form/Group.tpl',
    ]);
  }
  if ($formName === 'CRM_UF_Form_Field') {
    $form->add('select',
      'visibility',
      ts('Visibility'),
      CRM_Core_SelectValues::ufVisibility(),
      TRUE,
      ['onChange' => 'showHideSelectorSearch(this.value);']
    );
    $js = ['onChange' => 'mixProfile();'];
    $form->add('advcheckbox', 'in_selector', ts('Results Column?'), NULL, NULL, $js);
    $form->add('advcheckbox', 'is_searchable', ts('Searchable?'), NULL, NULL, $js);

    CRM_Core_Region::instance('profile-field-form')->add([
      'template' => 'CRM/Legacyprofiles/Form/Field.tpl',
    ]);
  }
}
