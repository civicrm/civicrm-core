<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Contribute_Form_ContributionPage_Widget extends CRM_Contribute_Form_ContributionPage {

  /**
   * Configuration for each form field
   *
   * @var array
   * @internal
   */
  public $_fields = [];

  /**
   * Configuration for each color field
   *
   * @var array
   * @internal
   */
  public $_colorFields = [];

  /**
   * @var CRM_Contribute_DAO_Widget
   */
  protected $_widget;

  public function preProcess() {
    parent::preProcess();
    $this->setSelectedChild('widget');

    $this->_widget = new CRM_Contribute_DAO_Widget();
    $this->_widget->contribution_page_id = $this->_id;
    $this->_widget->find(TRUE);
    $this->assign('widget_id', !empty($this->_widget->id) ? $this->_widget->id : NULL);
    $this->assign('cpageId', $this->_id);
    $this->assign('widgetExternUrl', CRM_Utils_System::externUrl('extern/widget', "cpageId={$this->_id}&widgetId=" . ($this->_widget->id ?? '') . "&format=3"));

    $config = CRM_Core_Config::singleton();
    $title = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionPage',
      $this->_id,
      'title'
    );

    $this->_fields = [
      'title' => [
        ts('Title'),
        'text',
        FALSE,
        $title,
      ],
      'url_logo' => [
        ts('URL to Logo Image'),
        'text',
        FALSE,
        NULL,
      ],
      'button_title' => [
        ts('Button Title'),
        'text',
        FALSE,
        ts('Contribute!'),
      ],
    ];

    $this->_colorFields = [
      'color_title' => [
        ts('Title Text'),
        'color',
        FALSE,
        '#2786C2',
      ],
      'color_main_bg' => [
        ts('Title Background'),
        'color',
        FALSE,
        '#B7E2FF',
      ],
      'color_bar' => [
        ts('Progress Bar'),
        'color',
        FALSE,
        '#2786C2',
      ],
      'color_main_text' => [
        ts('Additional Text'),
        'color',
        FALSE,
        '#FFFFFF',
      ],
      'color_main' => [
        ts('Background'),
        'color',
        FALSE,
        '#96C0E7',
      ],
      'color_bg' => [
        ts('Border'),
        'color',
        FALSE,
        '#96C0E7',
      ],
      'color_about_link' => [
        ts('Button Text'),
        'color',
        FALSE,
        '#556C82',
      ],
      'color_button' => [
        ts('Button Background'),
        'color',
        FALSE,
        '#FFFFFF',
      ],
      'color_homepage_link' => [
        ts('Homepage Link'),
        'color',
        FALSE,
        '#FFFFFF',
      ],
    ];
  }

  /**
   * Set default values for the form.
   */
  public function setDefaultValues() {
    $defaults = [];
    // check if there is a widget already created
    if ($this->_widget) {
      CRM_Core_DAO::storeValues($this->_widget, $defaults);
    }
    else {
      foreach ($this->_fields as $name => $val) {
        $defaults[$name] = $val[3];
      }
      foreach ($this->_colorFields as $name => $val) {
        $defaults[$name] = $val[3];
      }
      $defaults['about'] = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionPage',
        $this->_id,
        'intro_text'
      );
    }

    $showHide = new CRM_Core_ShowHideBlocks();
    $showHide->addHide('id-colors');
    $showHide->addToTemplate();
    return $defaults;
  }

  public function buildQuickForm() {
    $attributes = CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Widget');

    $this->addElement('checkbox',
      'is_active',
      ts('Enable the Widget'),
      NULL,
      ['onclick' => "widgetBlock(this)"]
    );

    $this->add('wysiwyg', 'about', ts('About'), $attributes['about']);

    foreach ($this->_fields as $name => $val) {
      $this->add($val[1],
        $name,
        $val[0],
        $attributes[$name],
        $val[2]
      );
    }
    foreach ($this->_colorFields as $name => $val) {
      $this->add($val[1],
        $name,
        $val[0],
        $attributes[$name],
        $val[2]
      );
    }

    $this->assign('fields', $this->_fields);
    $this->assign('colorFields', $this->_colorFields);

    parent::buildQuickForm();
    $this->addFormRule(['CRM_Contribute_Form_ContributionPage_Widget', 'formRule'], $this);
  }

  /**
   * Validation.
   *
   * @param array $params
   *   (ref.) an assoc array of name/value pairs.
   *
   * @param $files
   * @param self $self
   *
   * @return bool|array
   *   mixed true or array of errors
   */
  public static function formRule($params, $files, $self) {
    $errors = [];
    if (!empty($params['is_active'])) {
      if (empty($params['title'])) {
        $errors['title'] = ts('Title is a required field.');
      }
    }
    return empty($errors) ? TRUE : $errors;
  }

  public function postProcess() {
    //to reset quickform elements of next (pcp) page.
    if ($this->controller->getNextName('Widget') == 'PCP') {
      $this->controller->resetPage('PCP');
    }

    // get the submitted form values.
    $params = $this->controller->exportValues($this->_name);

    if ($this->_widget) {
      $params['id'] = $this->_widget->id;
    }
    $params['contribution_page_id'] = $this->_id;
    $params['is_active'] ??= FALSE;
    $params['url_homepage'] = 'null';

    $widget = new CRM_Contribute_DAO_Widget();
    $widget->copyValues($params);
    $widget->save();

    parent::endPostProcess();
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   */
  public function getTitle() {
    return ts('Widget Settings');
  }

}
