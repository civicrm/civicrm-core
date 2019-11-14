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
      $("#{/literal}{$relativeName}{literal}").change(function() {
        var n = cj(this).parent().parent();
        if ($(this).val() == "0") {
          $(".crm-absolute-date-range", n).show();
        } else {
          $(".crm-absolute-date-range", n).hide();
          $(':text', n).val('');
        }
      }).change();
    });
  </script>
{/literal}
