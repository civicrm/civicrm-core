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
{* Display contact-related footer. *}
{strip}
<div class="crm-footer" id="crm-record-log">
  <span class="col1">
    {if !empty($external_identifier)}{ts}External ID{/ts}:&nbsp;{$external_identifier}{/if}
    {if $action NEQ 2}&nbsp; &nbsp;{ts}CiviCRM ID{/ts}:&nbsp;{$contactId}{/if}
  </span>
  {if !empty($lastModified)}
    {ts}Last Change by{/ts}: <a href="{crmURL p='civicrm/contact/view' q="action=view&reset=1&cid=`$lastModified.id`"}">{$lastModified.name}</a> ({$lastModified.date|crmDate}) &nbsp;
    {if !empty($changeLog)}
      <a href="{crmURL p='civicrm/contact/view' q="reset=1&action=browse&selectedChild=log&cid=`$contactId`"}" class="crm-log-view">&raquo; {ts}View Change Log{/ts}</a>
    {/if}
  {/if}
	{if !empty($created_date)}<div class="contact-created-date">{ts}Created{/ts}: {$created_date|crmDate}</div>{/if}
</div>
{/strip}
