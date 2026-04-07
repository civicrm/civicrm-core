{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{literal}
  <script type="text/javascript">
    CRM.$(function($) {
      if ($("#is_deceased").is(':not(:checked)')) {
        $("#showDeceasedDate").hide();
      }
      $('#is_deceased').on('change', function() {
        $("#showDeceasedDate").toggle();
      });
    });
  </script>
{/literal}
