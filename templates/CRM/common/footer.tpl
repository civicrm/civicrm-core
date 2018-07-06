{*
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
*}
{if call_user_func(array('CRM_Core_Permission','check'), 'access CiviCRM')}
  {include file="CRM/common/accesskeys.tpl"}
  {if !empty($contactId)}
    {include file="CRM/common/contactFooter.tpl"}
  {/if}

  <div class="crm-footer" id="civicrm-footer">
    {crmVersion assign=version}
    {ts}Powered by CiviCRM{/ts} <a href="https://download.civicrm.org/about/{$version}">{$version}</a>.
    {if !empty($footer_status_severity)}
      <span class="status{if $footer_status_severity gt 3} crm-error{elseif $footer_status_severity gt 2} crm-warning{else} crm-ok{/if}">
      <a href="{crmURL p='civicrm/a/#/status'}">{$footer_status_message}</a>
    </span>
    {/if}
    {ts 1='http://www.gnu.org/licenses/agpl-3.0.html'}CiviCRM is openly available under the <a href='%1'>GNU AGPL License</a>.{/ts}<br/>
    <a href="https://civicrm.org/download">{ts}Download CiviCRM.{/ts}</a> &nbsp; &nbsp;
    <a href="https://lab.civicrm.org/groups/dev/-/issues">{ts}View issues and report bugs.{/ts}</a> &nbsp; &nbsp;
    {docURL page="" text="Online documentation."}
  </div>
  {include file="CRM/common/notifications.tpl"}
{/if}
