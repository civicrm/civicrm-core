{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{include file="CRM/common/dashboard.tpl"}
{include file="CRM/common/chart.tpl"}
{* Alerts for critical configuration settings. *}
{$communityMessages}
<div class="crm-submit-buttons crm-dashboard-controls">
<a href="#" id="crm-dashboard-configure" class="crm-hover-button show-add">
  <i class="crm-i fa-wrench"></i> {ts}Configure Your Dashboard{/ts}
</a>

<a style="float:right;" href="#" class="crm-hover-button show-refresh" style="margin-left: 6px;">
  <i class="crm-i fa-refresh"></i> {ts}Refresh Dashboard Data{/ts}
</a>

</div>
<div class="clear"></div>
<div class="crm-block crm-content-block">
{* Welcome message appears when there are no active dashlets for the current user. *}
<div id="empty-message" class='hiddenElement'>
    <div class="status">
        <div class="font-size12pt bold">{ts}Welcome to your Home Dashboard{/ts}</div>
        <div class="display-block">
            {ts}Your dashboard provides a one-screen view of the data that's most important to you. Graphical or tabular data is pulled from the reports you select, and is displayed in 'dashlets' (sections of the dashboard).{/ts} {help id="id-dash_welcome" file="CRM/Contact/Page/Dashboard.hlp"}
        </div>
    </div>
</div>

<div id="configure-dashlet" class='hiddenElement' style="min-height: 20em;"></div>
<div id="civicrm-dashboard">
  {* You can put anything you like here.  jQuery.dashboard() will remove it. *}
  <noscript>{ts}Javascript must be enabled in your browser in order to use the dashboard features.{/ts}</noscript>
</div>
<div class="clear"></div>
{literal}
<script type="text/javascript">
  CRM.$(function($) {
    $('#crm-dashboard-configure').click(function(e) {
      e.preventDefault();
      $(this).hide();
      if ($("#empty-message").is(':visible')) {
        $("#empty-message").fadeOut(400);
      }
      $("#civicrm-dashboard").fadeOut(400, function() {
        $(".crm-dashboard-controls").hide();
        $("#configure-dashlet").fadeIn(400);
      });
      CRM.loadPage(CRM.url('civicrm/dashlet', 'reset=1'), {target: $("#configure-dashlet")});
    });
  });
</script>
{/literal}
</div>
