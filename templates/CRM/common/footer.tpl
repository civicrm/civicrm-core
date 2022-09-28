{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if call_user_func(array('CRM_Core_Permission','check'), 'access CiviCRM')}
  {include file="CRM/common/accesskeys.tpl"}
  {if $contactId}
    {include file="CRM/common/contactFooter.tpl"}
  {/if}

  <div class="crm-footer" id="civicrm-footer">
    {crmVersion assign=version}
    {ts}Powered by CiviCRM{/ts} <a href="https://download.civicrm.org/about/{$version}" rel="external" target="_blank">{$version}</a>.
    {if $footer_status_severity}
      <span class="status{if $footer_status_severity gt 3} crm-error{elseif $footer_status_severity gt 2} crm-warning{else} crm-ok{/if}">
      <a href="{crmURL p='civicrm/a/#/status'}">{$footer_status_message}</a>
    </span>
    {/if}
    {ts 1='href="http://www.gnu.org/licenses/agpl-3.0.html" rel="external" target="_blank"'}CiviCRM is openly available under the <a %1>GNU AGPL License</a>.{/ts}<br/>
    <a href="https://civicrm.org/download" rel="external" target="_blank">{ts}Download CiviCRM.{/ts}</a> &nbsp; &nbsp;
    <a href="https://lab.civicrm.org/groups/dev/-/issues" rel="external" target="_blank">{ts}View issues and report bugs.{/ts}</a> &nbsp; &nbsp;
    {capture assign=docUrlText}{ts}Online documentation.{/ts}{/capture}
    {docURL page="" text=$docUrlText}
  </div>
  {include file="CRM/common/notifications.tpl"}
{/if}
