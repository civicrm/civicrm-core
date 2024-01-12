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

/**
 * This class holds all the Pseudo constants that are specific to Mass mailing. This avoids
 * polluting the core class and isolates the mass mailer class.
 */
class CRM_Mailing_PseudoConstant extends CRM_Core_PseudoConstant {

  /**
   * Status options for A/B tests.
   * @var array
   */
  private static $abStatus;

  /**
   * Test criteria for A/B tests.
   * @var array
   */
  private static $abTestCriteria;

  /**
   * Winner criteria for A/B tests.
   * @var array
   */
  private static $abWinnerCriteria;

  /**
   * Mailing templates
   * @var array
   */
  private static $template;

  /**
   * Completed mailings
   * @var array
   */
  private static $completed;

  /**
   * Mailing components
   * @var array
   */
  private static $component;

  /**
   * Default component id's, indexed by component type
   * @var array
   */
  private static $defaultComponent;

  /**
   * Mailing Types
   * @var array
   */
  private static $mailingTypes;

  /**
   * @return array
   */
  public static function abStatus() {
    if (!is_array(self::$abStatus)) {
      self::$abStatus = [
        'Draft' => ts('Draft'),
        'Testing' => ts('Testing'),
        'Final' => ts('Final'),
      ];
    }
    return self::$abStatus;
  }

  /**
   * @return array
   */
  public static function abTestCriteria() {
    if (!is_array(self::$abTestCriteria)) {
      self::$abTestCriteria = [
        'subject' => ts('Test different "Subject" lines'),
        'from' => ts('Test different "From" lines'),
        'full_email' => ts('Test entirely different emails'),
      ];
    }
    return self::$abTestCriteria;
  }

  /**
   * @return array
   */
  public static function abWinnerCriteria() {
    if (!is_array(self::$abWinnerCriteria)) {
      self::$abWinnerCriteria = [
        'open' => ts('Open'),
        'unique_click' => ts('Total Unique Clicks'),
        'link_click' => ts('Total Clicks on a particular link'),
      ];
    }
    return self::$abWinnerCriteria;
  }

  /**
   * @return array
   */
  public static function mailingTypes() {
    if (!is_array(self::$mailingTypes)) {
      self::$mailingTypes = [
        'standalone' => ts('Standalone'),
        'experiment' => ts('Experimental'),
        'winner' => ts('Winner'),
      ];
    }
    return self::$mailingTypes;
  }

  /**
   * Get all the mailing components of a particular type.
   *
   * @param $type
   *   The type of component needed.
   *
   * @return array
   *   array reference of all mailing components
   */
  public static function &component($type = NULL) {
    $name = $type ?: 'ALL';

    if (!self::$component || !array_key_exists($name, self::$component)) {
      if (!self::$component) {
        self::$component = [];
      }
      if (!$type) {
        self::$component[$name] = NULL;
        CRM_Core_PseudoConstant::populate(self::$component[$name], 'CRM_Mailing_BAO_MailingComponent');
      }
      else {
        // we need to add an additional filter for $type
        self::$component[$name] = [];

        $object = new CRM_Mailing_BAO_MailingComponent();
        $object->component_type = $type;
        $object->selectAdd();
        $object->selectAdd("id, name");
        $object->orderBy('component_type, is_default, name');
        $object->is_active = 1;
        $object->find();
        while ($object->fetch()) {
          self::$component[$name][$object->id] = $object->name;
        }
      }
    }
    return self::$component[$name];
  }

  /**
   * Determine the default mailing component of a given type.
   *
   * @param $type
   *   The type of component needed.
   * @param $undefined
   *   The value to use if no default is defined.
   *
   * @return int
   *   The ID of the default mailing component.
   */
  public static function &defaultComponent($type, $undefined = NULL) {
    if (!self::$defaultComponent) {
      $queryDefaultComponents = "SELECT id, component_type
                FROM    civicrm_mailing_component
                WHERE   is_active = 1
                AND     is_default = 1
                GROUP BY component_type, id";

      $dao = CRM_Core_DAO::executeQuery($queryDefaultComponents);

      self::$defaultComponent = [];
      while ($dao->fetch()) {
        self::$defaultComponent[$dao->component_type] = $dao->id;
      }
    }
    $value = self::$defaultComponent[$type] ?? $undefined;
    return $value;
  }

  /**
   * Get all the mailing templates.
   *
   *
   * @return array
   *   array reference of all mailing templates if any
   */
  public static function &template() {
    if (!self::$template) {
      CRM_Core_PseudoConstant::populate(self::$template, 'CRM_Mailing_DAO_Mailing', TRUE, 'name', 'is_template');
    }
    return self::$template;
  }

  /**
   * Get all the completed mailing.
   *
   *
   * @param null $mode
   *
   * @return array
   *   array reference of all mailing templates if any
   */
  public static function &completed($mode = NULL) {
    if (!self::$completed) {
      $mailingACL = CRM_Mailing_BAO_Mailing::mailingACL();
      $mailingACL .= $mode == 'sms' ? " AND sms_provider_id IS NOT NULL " : "";

      CRM_Core_PseudoConstant::populate(self::$completed,
        'CRM_Mailing_DAO_Mailing',
        FALSE,
        'name',
        'is_completed',
        $mailingACL
      );
    }
    return self::$completed;
  }

  /**
   * Labels for advanced search against mailing summary.
   *
   * @param string $field
   * @return array
   */
  public static function &yesNoOptions($field) {
    static $options;
    if (!$options) {
      $options = [
        'bounce' => [
          'N' => ts('Successful '),
          'Y' => ts('Bounced '),
        ],
        'delivered' => [
          'Y' => ts('Successful '),
          'N' => ts('Bounced '),
        ],
        'open' => [
          'Y' => ts('Opened '),
          'N' => ts('Unopened/Hidden '),
        ],
        'click' => [
          'Y' => ts('Clicked '),
          'N' => ts('Not Clicked '),
        ],
        'reply' => [
          'Y' => ts('Replied '),
          'N' => ts('No Reply '),
        ],
      ];
    }
    return $options[$field];
  }

  /**
   * Flush given pseudoconstant so it can be reread from db
   * next time it's requested.
   *
   *
   * @param bool|string $name pseudoconstant to be flushed
   */
  public static function flush($name = 'template') {
    if (isset(self::$$name)) {
      self::$$name = NULL;
    }
  }

}
