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
 * This class holds all the Pseudo constants those
 * are specific to Campaign and Survey.
 */
class CRM_Campaign_PseudoConstant extends CRM_Core_PseudoConstant {

  /**
   * Activity types
   * @var array
   */
  private static $activityType;

  /**
   * Campaign Type
   * @var array
   */
  private static $campaignType = [];

  /**
   * Campaign Status
   * @var array
   */
  private static $campaignStatus = [];

  /**
   * Engagement Level
   * @var int
   */
  private static $engagementLevel;

  /**
   * @deprecated. Please use the buildOptions() method in the appropriate BAO object.
   * Get all the survey activity types
   *
   * @param string $returnColumn
   *
   * @return array
   *   array reference of all survey activity types.
   */
  public static function &activityType($returnColumn = 'name') {
    $cacheKey = $returnColumn;
    if (!isset(self::$activityType[$cacheKey])) {
      $campaingCompId = CRM_Core_Component::getComponentID('CiviCampaign');
      if ($campaingCompId) {
        self::$activityType[$cacheKey] = CRM_Core_OptionGroup::values('activity_type',
          FALSE, FALSE, FALSE,
          " AND v.component_id={$campaingCompId}",
          $returnColumn
        );
      }
    }
    asort(self::$activityType[$cacheKey]);
    return self::$activityType[$cacheKey];
  }

  /**
   * @deprecated. Please use the buildOptions() method in the appropriate BAO object.
   * Get all campaign types.
   *
   * The static array campaignType is returned
   *
   *
   * @return array
   *   array reference of all campaign types.
   */
  public static function &campaignType() {
    if (!self::$campaignType) {
      self::$campaignType = CRM_Core_OptionGroup::values('campaign_type');
    }
    asort(self::$campaignType);
    return self::$campaignType;
  }

  /**
   * @deprecated. Please use the buildOptions() method in the appropriate BAO object.
   * Get all campaign status.
   *
   * The static array campaignStatus is returned
   *
   *
   * @return array
   *   array reference of all campaign status.
   */
  public static function &campaignStatus() {
    if (!self::$campaignStatus) {
      self::$campaignStatus = CRM_Core_OptionGroup::values('campaign_status');
    }
    asort(self::$campaignStatus);
    return self::$campaignStatus;
  }

  /**
   * @deprecated. Please use the buildOptions() method in the appropriate BAO object.
   * Get all Engagement Level.
   *
   * The static array Engagement Level is returned
   *
   *
   * @return array
   *   array reference of all Engagement Level.
   */
  public static function &engagementLevel() {
    if (!isset(self::$engagementLevel)) {
      self::$engagementLevel = CRM_Core_OptionGroup::values('engagement_index');
    }

    return self::$engagementLevel;
  }

  /**
   * Flush given pseudoconstant so it can be reread from db
   * next time it's requested.
   *
   *
   * @param bool|string $name pseudoconstant to be flushed
   */
  public static function flush($name = 'cache') {
    if (isset(self::$$name)) {
      self::$$name = NULL;
    }
  }

}
