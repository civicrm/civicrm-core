{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
{include file="CRM/common/dashboard.tpl"}
{include file="CRM/common/openFlashChart.tpl"}
{* Alerts for critical configuration settings. *}
{if ! $fromEmailOK || ! $ownerOrgOK || ! $defaultMailboxOK}
    <div class="help">
    <div class="finalconf-intro">
      {ts}There are a few things to setup before using your site ...{/ts}
    </div>
    {if ! $ownerOrgOK}
        <div class="finalconf-button">
            <a href="{$fixOrgUrl}" id="fixOrgUrl" class="button"><span><div class="icon settings-icon"></div>{ts}Go{/ts}</span></a>
        </div>
      <div class="finalconf-itemdesc">{ts}Please enter your organization's name and primary address.{/ts}</div>
      <h4 class="finalconf-item"><div class="icon alert-icon"></div> &nbsp;{ts}Organization Name{/ts}</h4>
      <div style="clear:both"></div>
  {/if}
    {if ! $fromEmailOK}
        <div class="finalconf-button">
            <a href="{$fixEmailUrl}" id="fixOrgUrl" class="button"><span><div class="icon settings-icon"></div>{ts}Go{/ts}</span></a>
        </div>
      <div class="finalconf-itemdesc">{ts}Please enter a default FROM Email Address (for system-generated emails).{/ts}</div>
      <h4 class="finalconf-item"><div class="icon alert-icon"></div> &nbsp;{ts}From Email Address{/ts}</h4>
      <div style="clear:both"></div>
    {/if}
    {if ! $defaultMailboxOK}
        <div class="finalconf-button">
            <a href="{$fixDefaultMailbox}" id="fixDefaultMailbox" class="button"><span><div class="icon settings-icon"></div>{ts}Go{/ts}</span></a>
        </div>
        <div class="finalconf-itemdesc">{ts}Please configure a default mailbox for CiviMail.{/ts} (<a href="http://http://book.civicrm.org/user/current/initial-set-up/email-system-configuration/0" title="{ts}opens online user guide in a new window{/ts}" target="_blank">{ts}learn more{/ts}</a>)</div>
        <h4 class="finalconf-item"><div class="icon alert-icon"></div> &nbsp;{ts}Default CiviMail Mailbox{/ts}</h4>
        <div style="clear:both"></div>
    {/if}
    </div>
{/if}
{$communityMessages}
<div class="crm-submit-buttons">
<a href="#" id="crm-dashboard-configure" class="button show-add">
  <span><div class="icon settings-icon"></div>{ts}Configure Your Dashboard{/ts}</span></a>

<a style="display:none;" href="{crmURL p="civicrm/dashboard" q="reset=1"}" class="button show-done" style="margin-left: 6px;">
  <span><div class="icon check-icon"></div>{ts}Done{/ts}</span></a>

<a style="float:right;" href="{crmURL p="civicrm/dashboard" q="reset=1&resetCache=1"}" class="button show-refresh" style="margin-left: 6px;">
  <span> <div class="icon refresh-icon"></div>{ts}Refresh Dashboard Data{/ts}</span></a>

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

<div id="configure-dashlet" class='hiddenElement'></div>
<div id="civicrm-dashboard">
  {* You can put anything you like here.  jQuery.dashboard() will remove it. *}
  <noscript>{ts}Javascript must be enabled in your browser in order to use the dashboard features.{/ts}</noscript>
</div>
<div class="clear"></div>
{literal}
<script type="text/javascript">
  cj(function($) {
    $('#crm-dashboard-configure').click(function() {
      $.ajax({
         url: CRM.url('civicrm/dashlet', 'reset=1&snippet=1'),
         success: function( content ) {
           $("#civicrm-dashboard, #crm-dashboard-configure, .show-refresh, #empty-message").hide();
           $('.show-done').show();
           $("#configure-dashlet").show().html(content);
         }
      });
      return false;
    });
  });
  cj().crmAccordions();
</script>
{/literal}
</div>
