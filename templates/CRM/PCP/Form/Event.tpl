{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div id="pcp-form" class="crm-block crm-form-block crm-contribution-contributionpage-pcp-form-block">
{if !$profile}
  {capture assign=pUrl}{crmURL p='civicrm/admin/uf/group' q="reset=1"}{/capture}
  <div class="status message">
  {ts 1=$pUrl}No Profile with a user account registration option has been configured / enabled for your site. You need to <a href='%1'>configure a Supporter profile</a> first. It will be used to collect or update basic information from users while they are creating a Personal Campaign Page.{/ts}
  </div>
{/if}
<div class="help">
{ts}Allow constituents to create their own personal fundraising pages linked to this event.{/ts} {help id="id-pcp_intro_help"}
</div>
{include file="CRM/PCP/Form/PCP.tpl" context="event" pageId=$eventId}
