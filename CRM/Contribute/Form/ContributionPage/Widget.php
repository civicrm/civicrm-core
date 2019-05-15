<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 */
class CRM_Contribute_Form_ContributionPage_Widget extends CRM_Contribute_Form_ContributionPage {
  protected $_colors;

  protected $_widget;

  public function preProcess() {
    parent::preProcess();

    $this->_widget = new CRM_Contribute_DAO_Widget();
    $this->_widget->contribution_page_id = $this->_id;
    if (!$this->_widget->find(TRUE)) {
      $this->_widget = NULL;
    }
    else {
      $this->assign('widget_id', $this->_widget->id);

      // check of home url is set, if set then it flash widget might be in use.
      $this->assign('showStatus', FALSE);
      if ($this->_widget->url_homepage) {
        $this->assign('showStatus', TRUE);
      }
    }

    $this->assign('cpageId', $this->_id);

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
        ts('Title Text Color'),
        'color',
        FALSE,
        '#2786C2',
      ],
      'color_bar' => [
        ts('Progress Bar Color'),
        'color',
        FALSE,
        '#2786C2',
      ],
      'color_main_text' => [
        ts('Additional Text Color'),
        'color',
        FALSE,
        '#FFFFFF',
      ],
      'color_main' => [
        ts('Background Color'),
        'color',
        FALSE,
        '#96C0E7',
      ],
      'color_main_bg' => [
        ts('Background Color Top Area'),
        'color',
        FALSE,
        '#B7E2FF',
      ],
      'color_bg' => [
        ts('Border Color'),
        'color',
        FALSE,
        '#96C0E7',
      ],
      'color_about_link' => [
        ts('Button Text Color'),
        'color',
        FALSE,
        '#556C82',
      ],
      'color_button' => [
        ts('Button Background Color'),
        'color',
        FALSE,
        '#FFFFFF',
      ],
      'color_homepage_link' => [
        ts('Homepage Link Color'),
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
      ts('Enable Widget?'),
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

    $this->assign_by_ref('fields', $this->_fields);
    $this->assign_by_ref('colorFields', $this->_colorFields);

    $this->_refreshButtonName = $this->getButtonName('refresh');
    $this->addElement('submit',
      $this->_refreshButtonName,
      ts('Save and Preview')
    );
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
   * @param $self
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
      if (empty($params['about'])) {
        $errors['about'] = ts('About is a required field.');
      }

      foreach ($params as $key => $val) {
        if (substr($key, 0, 6) == 'color_' && empty($params[$key])) {
          $errors[$key] = ts('%1 is a required field.', [1 => $self->_colorFields[$key][0]]);
        }
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
    $params['is_active'] = CRM_Utils_Array::value('is_active', $params, FALSE);
    $params['url_homepage'] = 'null';

    $widget = new CRM_Contribute_DAO_Widget();
    $widget->copyValues($params);
    $widget->save();

    $buttonName = $this->controller->getButtonName();
    if ($buttonName == $this->_refreshButtonName) {
      return;
    }
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
