<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 */

/**
 * Builds a form of shortcodes that can be added to WP posts.
 *
 * Use hook_civicrm_preProcess to modify this list.
 */
class CRM_Core_Form_ShortCode extends CRM_Core_Form {
  /**
   * List of entities supported by shortcodes, and their form properties.
   *
   * Keys should be the "component" string for the shortcode
   * Values should be an array with label and select.
   * Select can be NULL if there is no entity to select.
   * Otherwise it contains the shortcode key for this entity id (usually 'id') plus an array of params for the EntityRef field
   * @see CRM_Core_Form::addEntityRef
   *
   * @var array
   *   [component => [
   *     label => Option Label
   *     select => key + EntityRef params
   *   ]]
   */
  public $components = array();

  /**
   * List of radio option groups to display on the form
   *
   * Control the conditional logic of showing/hiding each group via the "components" array.
   * Or set 'components' => TRUE if it applies to all
   *
   * @var array
   *   [key, components, options]
   */
  public $options = array();


  /**
   * Build form data. Can be modified via hook_civicrm_preProcess.
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
        'entity' => 'UFGroup',
        'select' => array('minimumInputLength' => 0),
        'api' => array(
          'params' => array(
            'id' => $this->profileAccess(),
          ),
        ),
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
          'api' => array(
            'params' => array(
              'activity_type_id' => "Petition",
            ),
          ),
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
        'components' => TRUE,
        'label' => ts('If you only insert one shortcode, you can choose to override all page content with the content of the shortcode.'),
        'options' => array(
          '0' => ts("Don't override"),
          '1' => ts('Override page content'),
        ),
      ),
    );
  }

  /**
   * Build form elements based on the above metadata.
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
      if ($field['components'] === TRUE) {
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

  /**
   * The CiviCRM api (and therefore EntityRef) does not support OR logic, ACLs or joins.
   *
   * I'm not proud of this, but here's a workaround to pre-filter the api params
   *
   * @return array
   */
  private function profileAccess() {
    $sql = "
      SELECT g.id
      FROM   civicrm_uf_group g, civicrm_uf_join j
      WHERE  g.is_active = 1
      AND    j.is_active = 1
      AND    ( group_type LIKE '%Individual%'
         OR    group_type LIKE '%Contact%' )
      AND    g.id = j.uf_group_id
      AND    j.module = 'Profile'
      ";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $ids = array();
    while ($dao->fetch()) {
      $ids[] = $dao->id;
    }
    return array('IN' => $ids);
  }

  // No postProccess fn; this form never gets submitted

}
