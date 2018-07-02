<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 * Class to retrieve information about a contribution page.
 */
class CRM_Contribute_BAO_Widget extends CRM_Contribute_DAO_Widget {

  /**
   * Gets all campaign related data and returns it as a std class.
   *
   * @param int $contributionPageID
   * @param int $widgetID
   * @param bool $includePending
   *
   * @return object
   */
  public static function getContributionPageData($contributionPageID, $widgetID, $includePending = FALSE) {
    $config = CRM_Core_Config::singleton();

    $data = array();
    $data['currencySymbol'] = $config->defaultCurrencySymbol;

    if (empty($contributionPageID) ||
      CRM_Utils_Type::validate($contributionPageID, 'Integer') == NULL
    ) {
      $data['is_error'] = TRUE;
      CRM_Core_Error::debug_log_message("$contributionPageID is not set");
      return $data;
    }

    $widget = new CRM_Contribute_DAO_Widget();
    $widget->contribution_page_id = $contributionPageID;
    if (!$widget->find(TRUE)) {
      $data['is_error'] = TRUE;
      CRM_Core_Error::debug_log_message("$contributionPageID is not found");
      return $data;
    }

    $data['is_error'] = FALSE;
    if (!$widget->is_active) {
      $data['is_active'] = FALSE;
    }

    $data['is_active'] = TRUE;
    $data['title'] = $widget->title;
    $data['logo'] = $widget->url_logo;
    $data['button_title'] = $widget->button_title;
    $data['about'] = $widget->about;

    //check if pending status needs to be included
    $status = '1';
    if ($includePending) {
      $status = '1,2';
    }

    $query = "
            SELECT count( id ) as count,
            sum( total_amount) as amount
            FROM   civicrm_contribution
            WHERE  is_test = 0
            AND    contribution_status_id IN ({$status})
            AND    contribution_page_id = %1";
    $params = array(1 => array($contributionPageID, 'Integer'));
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    if ($dao->fetch()) {
      $data['num_donors'] = (int) $dao->count;
      $data['money_raised'] = (int) $dao->amount;
    }
    else {
      $data['num_donors'] = $data['money_raised'] = $data->money_raised = 0;
    }

    $data['money_raised_amount'] = CRM_Utils_Money::format($data['money_raised']);

    $query = "
            SELECT goal_amount, start_date, end_date, is_active
            FROM   civicrm_contribution_page
            WHERE  id = %1";
    $params = array(1 => array($contributionPageID, 'Integer'));
    $dao = CRM_Core_DAO::executeQuery($query, $params);

    $data['campaign_start'] = '';
    $startDate = NULL;
    if ($dao->fetch()) {
      $data['money_target'] = (int) $dao->goal_amount;

      // conditions that needs to be handled
      // 1. Campaign is not active - no text
      // 2. Campaign start date greater than today - show start date
      // 3. Campaign end date is set and greater than today - show end date
      // 4. If no start and end date or no end date and start date greater than today, then it's ongoing
      if ($dao->is_active) {
        $data['campaign_start'] = ts('Campaign is ongoing');

        // check for time being between start and end date
        $now = time();
        if ($dao->start_date) {
          $startDate = CRM_Utils_Date::unixTime($dao->start_date);
          if ($startDate &&
            $startDate >= $now
          ) {
            $data['is_active'] = FALSE;
            $data['campaign_start'] = ts('Campaign starts on %1', array(
                1 => CRM_Utils_Date::customFormat($dao->start_date, $config->dateformatFull),
              )
            );
          }
        }

        if ($dao->end_date) {
          $endDate = CRM_Utils_Date::unixTime($dao->end_date);
          if ($endDate &&
            $endDate < $now
          ) {
            $data['is_active'] = FALSE;
            $data['campaign_start'] = ts('Campaign ended on %1',
              array(
                1 => CRM_Utils_Date::customFormat($dao->end_date, $config->dateformatFull),
              )
            );
          }
          elseif ($startDate >= $now) {
            $data['campaign_start'] = ts('Campaign starts on %1',
              array(
                1 => CRM_Utils_Date::customFormat($dao->start_date, $config->dateformatFull),
              )
            );
          }
          else {
            $data['campaign_start'] = ts('Campaign ends on %1',
              array(
                1 => CRM_Utils_Date::customFormat($dao->end_date, $config->dateformatFull),
              )
            );
          }
        }
      }
      else {
        $data['is_active'] = FALSE;
      }
    }
    else {
      $data['is_active'] = FALSE;
    }

    $data['money_raised_percentage'] = 0;
    if ($data['money_target'] > 0) {
      $percent = $data['money_raised'] / $data['money_target'];
      $data['money_raised_percentage'] = (round($percent, 2)) * 100 . "%";
      $data['money_target_display'] = CRM_Utils_Money::format($data['money_target']);
      $data['money_raised'] = ts('Raised %1 of %2', array(
          1 => CRM_Utils_Money::format($data['money_raised']),
          2 => $data['money_target_display'],
        ));
    }
    else {
      $data['money_raised'] = ts('Raised %1', array(1 => CRM_Utils_Money::format($data['money_raised'])));
    }

    $data['money_low'] = 0;
    $data['num_donors'] = $data['num_donors'] . " " . ts('Donors');
    $data['home_url'] = "<a href='{$config->userFrameworkBaseURL}' class='crm-home-url' style='color:" . $widget->color_homepage_link . "'>" . ts('Learn more.') . "</a>";

    // if is_active is false, show this link and hide the contribute button
    $data['homepage_link'] = $widget->url_homepage;

    $data['colors'] = array();

    $data['colors']["title"] = $widget->color_title;
    $data['colors']["button"] = $widget->color_button;
    $data['colors']["bar"] = $widget->color_bar;
    $data['colors']["main_text"] = $widget->color_main_text;
    $data['colors']["main"] = $widget->color_main;
    $data['colors']["main_bg"] = $widget->color_main_bg;
    $data['colors']["bg"] = $widget->color_bg;
    $data['colors']["about_link"] = $widget->color_about_link;

    return $data;
  }

}
