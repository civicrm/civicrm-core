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
{include file="CRM/common/accesskeys.tpl"}
{if !empty($contactId)}
  {include file="CRM/common/contactFooter.tpl"}
{/if}

<div class="crm-footer" id="civicrm-footer">
  {crmVersion assign=version}
  {ts 1=$version}Powered by CiviCRM %1.{/ts}
  {if !empty($newer_civicrm_version)}
    <span class="status">{ts 1=$newer_civicrm_version}A newer version (%1){/ts}
    <a href="http://civicrm.org/download">{ts}is available for download{/ts}</a>.</span>
  {/if}
  {ts 1='http://www.gnu.org/licenses/agpl-3.0.html'}CiviCRM is openly available under the <a href='%1'>GNU AGPL License</a>.{/ts}<br/>
  <a href="http://civicrm.org/download">{ts}Download CiviCRM.{/ts}</a> &nbsp; &nbsp;
  <a href="http://issues.civicrm.org/jira/browse/CRM?report=com.atlassian.jira.plugin.system.project:roadmap-panel">{ts}View issues and report bugs.{/ts}</a> &nbsp; &nbsp;
  {docURL page="" text="Online documentation."}
</div>
{include file="CRM/common/notifications.tpl"}
