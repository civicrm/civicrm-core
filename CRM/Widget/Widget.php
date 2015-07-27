<?php
/*
 * Copyright (C) 2007 Jacob Singh, Sam Lerner
 * Licensed to CiviCRM under the Academic Free License version 3.0.
 *
 * Modified and improved upon by CiviCRM LLC (c) 2007
 */

/**
 * Class CRM_Widget_Widget
 */
class CRM_Widget_Widget {

  static $_methodTable;

  public function initialize() {
    if (!self::$_methodTable) {
      self::$_methodTable = array(
        'getContributionPageData' => array(
          'description' => 'Gets all campaign related data and returns it as a std class.',
          'access' => 'remote',
          'arguments' => array(
            'contributionPageID',
            'widgetID',
          ),
        ),
        'getEmbedCode' => array(
          'description' => 'Gets embed code.  Perhaps overkill, but we can track dropoffs in this case. by # of people reqeusting emebed code / number of unique instances.',
          'access' => 'remote',
          'arguments' => array(
            'contributionPageID',
            'widgetID',
            'format',
          ),
        ),
      );
    }
  }

  public function &methodTable() {
    self::initialize();

    return self::$_methodTable;
  }

  /**
   * Not implemented - registers an action and unique widget ID.  Useful for stats and debugging
   *
   * @param int $contributionPageID
   * @param string $widgetID
   * @param string $action
   *
   * @return string
   */
  public function registerRequest($contributionPageID, $widgetID, $action) {
    return "I registered a request to $action on $contributionPageID from $widgetID";
  }

  /**
   * Gets all campaign related data and returns it as a std class.
   *
   * @param int $contributionPageID
   * @param string $widgetID
   *
   * @return object
   */
  public function getContributionPageData($contributionPageID, $widgetID) {
    $config = CRM_Core_Config::singleton();

    self::registerRequest($contributionPageID, $widgetID, __FUNCTION__);

    $data = new stdClass();

    if (empty($contributionPageID) ||
      CRM_Utils_Type::validate($contributionPageID, 'Integer') == NULL
    ) {
      $data->is_error = TRUE;
      CRM_Core_Error::debug_log_message("$contributionPageID is not set");
      return $data;
    }

    $widget = new CRM_Contribute_DAO_Widget();
    $widget->contribution_page_id = $contributionPageID;
    if (!$widget->find(TRUE)) {
      $data->is_error = TRUE;
      CRM_Core_Error::debug_log_message("$contributionPageID is not found");
      return $data;
    }

    $data->is_error = FALSE;
    if (!$widget->is_active) {
      $data->is_active = FALSE;
    }

    $data->is_active = TRUE;
    $data->title = $widget->title;
    $data->logo = $widget->url_logo;
    $data->button_title = $widget->button_title;
    $data->button_url = CRM_Utils_System::url('civicrm/contribute/transact',
      "reset=1&id=$contributionPageID",
      TRUE, NULL, FALSE, TRUE
    );
    $data->about = $widget->about;

    $query = "
SELECT count( id ) as count,
       sum( total_amount) as amount
FROM   civicrm_contribution
WHERE  is_test = 0
AND    contribution_status_id = 1
AND    contribution_page_id = %1";
    $params = array(1 => array($contributionPageID, 'Integer'));
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    if ($dao->fetch()) {
      $data->num_donors = $dao->count;
      $data->money_raised = $dao->amount;
    }
    else {
      $data->num_donors = $data->money_raised = 0;
    }

    $query = "
SELECT goal_amount, start_date, end_date, is_active
FROM   civicrm_contribution_page
WHERE  id = %1";
    $params = array(1 => array($contributionPageID, 'Integer'));
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    if ($dao->fetch()) {
      $data->money_target = $dao->goal_amount;
      $data->campaign_start = CRM_Utils_Date::customFormat($dao->start_date, $config->dateformatFull);
      $data->campaign_end = CRM_Utils_Date::customFormat($dao->end_date, $config->dateformatFull);

      // check for time being between start and end date
      $now = time();
      if ($dao->start_date) {
        $startDate = CRM_Utils_Date::unixTime($dao->start_date);
        if ($startDate &&
          $startDate >= $now
        ) {
          $data->is_active = FALSE;
        }
      }

      if ($dao->end_date) {
        $endDate = CRM_Utils_Date::unixTime($dao->end_date);
        if ($endDate &&
          $endDate < $now
        ) {
          $data->is_active = FALSE;
        }
      }
    }
    else {
      $data->is_active = FALSE;
    }

    // if is_active is false, show this link and hide the contribute button
    $data->homepage_link = $widget->url_homepage;

    // movie clip colors, must be in '0xRRGGBB' format
    $data->colors = array();

    $hexPrefix = '0x';
    $data->colors["title"] = str_replace('#', $hexPrefix, $widget->color_title);
    $data->colors["button"] = str_replace('#', $hexPrefix, $widget->color_button);
    $data->colors["bar"] = str_replace('#', $hexPrefix, $widget->color_bar);
    $data->colors["main_text"] = str_replace('#', $hexPrefix, $widget->color_main_text);
    $data->colors["main"] = str_replace('#', $hexPrefix, $widget->color_main);
    $data->colors["main_bg"] = str_replace('#', $hexPrefix, $widget->color_main_bg);
    $data->colors["bg"] = str_replace('#', $hexPrefix, $widget->color_bg);

    // these two have colors as normal hex format
    // because they're being used in a CSS object
    $data->colors["about_link"] = str_replace('#', $hexPrefix, $widget->color_about_link);
    $data->colors["homepage_link"] = str_replace('#', $hexPrefix, $widget->color_homepage_link);

    return $data;
  }

  /**
   * Gets embed code.  Perhaps overkill, but we can track dropoffs in this case.
   * by # of people reqeusting emebed code / number of unique instances.
   *
   * @param int $contributionPageID
   * @param string $widgetID
   * @param string $format
   *   Either myspace or normal.
   *
   * @return string
   */
  public function getEmbedCode($contributionPageID, $widgetID, $format = "normal") {
    self::registerRequest($contributionPageID, $widgetID, __FUNCTION__);
    return "<embed>.......................</embed>" .
    print_r(func_get_args(), 1);
  }

}
