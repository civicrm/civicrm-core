{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Display contact-related footer. *}
{strip}
<div class="crm-footer" id="crm-record-log">
  <span class="col1">
    {if $external_identifier}{ts}External ID{/ts}:&nbsp;{$external_identifier}{/if}
    {if $action !== 2}&nbsp; &nbsp;{ts}Contact ID{/ts}:&nbsp;{$contactId}{/if}
  </span>
  {if $lastModified}
    {ts}Last Change by{/ts}: <a href="{crmURL p='civicrm/contact/view' q="action=view&reset=1&cid=`$lastModified.id`"}">{$lastModified.name}</a> ({$lastModified.date|crmDate}) &nbsp;
    {if $changeLog}
      <a href="{crmURL p='civicrm/contact/view' q="reset=1&action=browse&selectedChild=log&cid=`$contactId`"}" class="crm-log-view"><i class="crm-i fa-history" role="img" aria-hidden="true"></i> {ts}View Change Log{/ts}</a>
    {/if}
  {/if}
  {if $created_date}<div class="contact-created-date">{ts}Created{/ts}: {$created_date|crmDate}</div>{/if}
</div>
{/strip}
