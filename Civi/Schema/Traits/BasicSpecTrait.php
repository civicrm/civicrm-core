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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
trait BasicSpecTrait {

  /**
   * Symbolic name of the field.
   *
   * Ex: 'first_name'
   *
   * @var string
   */
  public $name;

  /**
   * Backend-facing label. Shown in API, exports, and other configuration
   * systems.
   *
   * If this field is presented to an administrator (e.g. when configuring a
   * screen or configuring process-automation), how the field be entitled?
   *
   * Ex: ts('First Name')
   *
   * @var string
   */
  public $title;

  /**
   * Explanation of the purpose of the field.
   *
   * @var string
   */
  public $description;

  /**
   * @return string
   */
  public function getName() {
    return $this->name;
  }

  /**
   * @param string $name
   *
   * @return $this
   */
  public function setName($name) {
    $this->name = $name;

    return $this;
  }

  /**
   * @return string
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * @param string $title
   *
   * @return $this
   */
  public function setTitle($title) {
    $this->title = $title;
    return $this;
  }

  /**
   * @return string
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * @param string $description
   *
   * @return $this
   */
  public function setDescription($description) {
    $this->description = $description;
    return $this;
  }

}
