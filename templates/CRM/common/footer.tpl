{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{crmPermission has='access CiviCRM'}
  {include file="CRM/common/accesskeys.tpl"}
  {if $contactId}
    {include file="CRM/common/contactFooter.tpl"}
  {/if}

  <div class="crm-footer" id="civicrm-footer">
    {if $footer_status_severity}
    <span class="status{if $footer_status_severity gt 3} crm-error{elseif $footer_status_severity gt 2} crm-warning{else} crm-ok{/if}">
      <a href="{crmURL p='civicrm/a/#/status'}">{$footer_status_message}</a>
    </span>&nbsp;
    {else}
      {crmPermission has='administer CiviCRM'}
    <span class="status crm-status-none">
      <a href="{crmURL p='civicrm/a/#/status'}">{ts}System Status{/ts}</a>
    </span>&nbsp;
      {/crmPermission}
    {/if}
    {crmVersion assign=version}
    {ts 1='href="http://www.gnu.org/licenses/agpl-3.0.html" rel="external" target="_blank"' 2='href="https://civicrm.org/" rel="external" target="_blank"' 3=$version}Powered by <a %2>CiviCRM</a> %3, free and open source <a %1>AGPLv3</a> software.{/ts}<br/>
  </div>
  {include file="CRM/common/notifications.tpl"}
{/crmPermission}
