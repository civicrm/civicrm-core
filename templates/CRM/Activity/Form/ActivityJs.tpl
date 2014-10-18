{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
*}
{* added onload javascript for source contact*}
{literal}
<script type="text/javascript">
  /**
   * Function to check activity status in relavent to activity date
   *
   * @param message JSON object.
   */
  function activityStatus(message) {
    var activityDate =  cj("#activity_date_time_display").datepicker('getDate');
    if (activityDate) {
      var
        // Ignore time, only compare dates
        today = new Date().setHours(0,0,0,0),
        activityStatusId = cj('#status_id').val();
      if (activityStatusId == 2 && today < activityDate) {
        return confirm(message.completed);
      }
      else if (activityStatusId == 1 && today > activityDate) {
        return confirm(message.scheduled);
      }
    }
  }

</script>
{/literal}
