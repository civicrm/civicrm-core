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

namespace Civi\Schema\Traits;

/**
 * If a field will be presented in GUIs (e.g. data-entry fields or
 * data-columns), then use GuiSpecTrait to describe its typical/default appearance..
 *
 * @package Civi\Schema\Traits
 */
trait GuiSpecTrait {

  /**
   * User-facing label, shown on most forms and displays
   *
   * Default label to use when presenting this field to an end-user (e.g.
   * on a data-entry form or a data-column view).
   *
   * @var string
   */
  public $label;

  /**
   * Default widget to use when presenting this field.
   *
   * @var string
   *   Ex: 'RichTextEditor'
   */
  public $inputType;

  /**
   * Can the field be translated.
   *
   * @var bool
   */
  public $localizable = FALSE;

  /**
   * @var array
   */
  public $inputAttrs = [];

  /**
   * @var string
   */
  public $helpPre;

  /**
   * @var string
   */
  public $helpPost;

  /**
   * @return string
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * @return string
   */
  public function getInputType() {
    return $this->inputType;
  }

  /**
   * @param string $inputType
   *
   * @return $this
   */
  public function setInputType($inputType) {
    $this->inputType = $inputType;
    return $this;
  }

  /**
   * @return array
   */
  public function getInputAttrs() {
    return $this->inputAttrs;
  }

  /**
   * @param array $inputAttrs
   *
   * @return $this
   */
  public function setInputAttrs($inputAttrs) {
    $this->inputAttrs = $inputAttrs;
    return $this;
  }

  /**
   * @param string $attrName
   * @param $attrValue
   * @return $this
   */
  public function setInputAttr(string $attrName, $attrValue) {
    $this->inputAttrs[$attrName] = $attrValue;
    return $this;
  }

  /**
   * @return bool
   */
  public function getLocalizable() {
    return $this->localizable;
  }

  /**
   * @param bool $localizable
   *
   * @return $this
   */
  public function setLocalizable(bool $localizable) {
    $this->localizable = $localizable;
    return $this;
  }

  /**
   * @param string $label
   *
   * @return $this
   */
  public function setLabel($label) {
    $this->label = $label;
    return $this;
  }

  /**
   * @param string|null $helpPre
   */
  public function setHelpPre($helpPre) {
    $this->helpPre = is_string($helpPre) && strlen($helpPre) ? $helpPre : NULL;
  }

  /**
   * @param string|null $helpPost
   */
  public function setHelpPost($helpPost) {
    $this->helpPost = is_string($helpPost) && strlen($helpPost) ? $helpPost : NULL;
  }

}
