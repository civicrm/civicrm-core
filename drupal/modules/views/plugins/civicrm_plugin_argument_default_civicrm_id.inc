<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
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
 * @file Contains a plugin to extract CiviCRM IDs from the URL.
 */

/**
 * Class civicrm_plugin_argument_default_civicrm_id
 */
class civicrm_plugin_argument_default_civicrm_id extends views_plugin_argument_default {

  /**
   * List the options relevant for this plugin.
   */
  public function option_definition() {
    $options = parent::option_definition();
    $options['id_type'] = array('default' => 'Contact');
    return $options;
  }

  /**
   * Provide the default form for setting options.
   */
  public function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['id_type'] = array(
      '#type' => 'select',
      '#title' => t('CiviCRM ID type'),
      '#description' => t('Select the entity type of the ID to extract from the URL'),
      '#options' => array(
        'Contact' => t('Contact'),
        'Contribution page' => t('Contribution page'),
      ),
      '#default_value' => $this->options['id_type'],
      '#required' => TRUE,
    );
  }

  /**
   * Get the default argument.
   */
  public function get_argument() {

    switch ($this->options['id_type']) {

      // Contact ID is set using the param 'cid'
      case 'Contact':
        if (!empty($_GET['cid']) && is_numeric($_GET['cid'])) {
          return $_GET['cid'];
        }
        break;

      // Contribution page ID is set using the param 'id'
      case 'Contribution page':
        if (
          strpos(current_path(), 'contribute/transact') !== FALSE
          && !empty($_GET['id'])
          && is_numeric($_GET['id'])
        ) {
          return $_GET['id'];
        }
        break;

    }

    // Return FALSE if we haven't returned yet
    return FALSE;

  }

}
