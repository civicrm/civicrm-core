<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 */

/**
 * Builds a form of shortcodes that can be added to WP posts
 * Use hook_civicrm_preProcess to modify this list
 */
class CRM_Core_Form_ShortCode extends CRM_Core_Form {
  /**
   * List of entities supported by shortcodes, and their form properties
   *
   * @var array
   */
  public $components = array();

  /**
   * List of options to display on the form
   *
   * @var array
   */
  public $options = array();


  /**
   * Build form data. Overridable via hook_civicrm_preProcess
   *
   * @return void
   */
  public function preProcess() {
    $config = CRM_Core_Config::singleton();

    $this->components['user-dashboard'] = array(
      'label' => ts("User Dashboard"),
      'select' => NULL,
    );
    $this->components['profile'] = array(
      'label' => ts("Profile"),
      'select' => array(
        'key' => 'gid',
        'entity' => 'Profile',
        'select' => array('minimumInputLength' => 0),
      ),
    );

    if (in_array('CiviContribute', $config->enableComponents)) {
      $this->components['contribution'] = array(
        'label' => ts("Contribution Page"),
        'select' => array(
          'key' => 'id',
          'entity' => 'ContributionPage',
          'select' => array('minimumInputLength' => 0),
        ),
      );
    }

    if (in_array('CiviEvent', $config->enableComponents)) {
      $this->components['event'] = array(
        'label' => ts("Event Page"),
        'select' => array(
          'key' => 'id',
          'entity' => 'Event',
          'select' => array('minimumInputLength' => 0),
        ),
      );
    }

    if (in_array('CiviCampaign', $config->enableComponents)) {
      $this->components['petition'] = array(
        'label' => ts("Petition"),
        'select' => array(
          'key' => 'id',
          'entity' => 'Survey',
          'select' => array('minimumInputLength' => 0),
        ),
      );
    }

    $this->options = array(
      array(
        'key' => 'action',
        'components' => array('event'),
        'options' => array(
          'info' => ts('Event Info Page'),
          'register' => ts('Event Registration Page'),
        ),
      ),
      array(
        'key' => 'mode',
        'components' => array('contribution', 'event'),
        'options' => array(
          'live' => ts('Live Mode'),
          'test' => ts('Test Drive'),
        ),
      ),
      array(
        'key' => 'mode',
        'components' => array('profile'),
        'options' => array(
          'create' => ts('Create'),
          'edit' => ts('Edit'),
          'view' => ts('View'),
          'search' => ts('Search/Public Directory'),
        ),
      ),
      array(
        'key' => 'hijack',
        'components' => '*',
        'label' => ts('If you only insert one shortcode, you can choose to override all page content with the content of the shortcode.'),
        'options' => array(
          '0' => ts("Don't override"),
          '1' => ts('Override page content'),
        ),
      ),
    );
  }

  /**
   * Build form elements based on the above metadata
   *
   * @return void
   */
  public function buildQuickForm() {
    CRM_Core_Resources::singleton()
      ->addScriptFile('civicrm', 'js/crm.insert-shortcode.js');

    $components = CRM_Utils_Array::collect('label', $this->components);
    $data = CRM_Utils_Array::collect('select', $this->components);

    $this->add('select', 'component', NULL, $components, FALSE, array('class' => 'crm-select2', 'data-key' => 'component', 'data-entities' => json_encode($data)));
    $this->add('text', 'entity', NULL, array('placeholder' => ts('- select -')));

    $options = $defaults = array();
    foreach ($this->options as $num => $field) {
      $this->addRadio("option_$num", CRM_Utils_Array::value('label', $field), $field['options'], array('allowClear' => FALSE, 'data-key' => $field['key']));
      if ($field['components'] === '*') {
        $field['components'] = array_keys($this->components);
      }
      $options["option_$num"] = $field;

      // Select 1st option as default
      $keys = array_keys($field['options']);
      $defaults["option_$num"] = $keys[0];
    }

    $this->assign('options', $options);
    $this->assign('selects', array_keys(array_filter($data)));
    $this->setDefaults($defaults);
  }

  // No postProccess fn; this form never gets submitted

}
