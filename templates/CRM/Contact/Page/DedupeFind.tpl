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
{if $action eq 2 || $action eq 16}
<div class="form-item">
  <table class='pagerDisplay'>
    <thead>
    <tr class="columnheader"><th id="nosort">{ts}Contact{/ts} 1</th><th id="nosort">{ts}Contact{/ts} 2 ({ts}Duplicate{/ts})</th><th id="nosort">{ts}Threshold{/ts}</th><th id="nosort">&nbsp;</th></tr>
    </thead>
  </table>
  {include file="CRM/common/jsortable.tpl" sourceUrl=$sourceUrl useAjax=1 }
  {if $cid}
    <table style="width: 45%; float: left; margin: 10px;">
      <tr class="columnheader"><th colspan="2">{ts 1=$main_contacts[$cid]}Merge %1 with{/ts}</th></tr>
      {foreach from=$dupe_contacts[$cid] item=dupe_name key=dupe_id}
        {if $dupe_name}
          {capture assign=link}<a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=$dupe_id"}">{$dupe_name}</a>{/capture}
          {capture assign=merge}<a href="{crmURL p='civicrm/contact/merge' q="reset=1&cid=$cid&oid=$dupe_id"}">{ts}merge{/ts}</a>{/capture}
          <tr class="{cycle values="odd-row,even-row"}">
      <td>{$link}</td>
      <td style="text-align: right">{$merge}</td>
      <td style="text-align: right"><a id='notDuplicate' href="#" title={ts}not a duplicate{/ts} onClick="processDupes( {$main.srcID}, {$main.dstID}, 'dupe-nondupe' );return false;">{ts}not a duplicate{/ts}</a></td>
      </tr>
        {/if}
      {/foreach}
    </table>
  {/if}
</div>

{if $context eq 'search'}
   <a href="{$backURL}" class="button"><span>{ts}Done{/ts}</span></a>
{else}
   {if $gid}
      {capture assign=backURL}{crmURL p="civicrm/contact/dedupefind" q="reset=1&rgid=`$rgid`&gid=`$gid`&action=renew" a=1}{/capture}
   {else}
      {capture assign=backURL}{crmURL p="civicrm/contact/dedupefind" q="reset=1&rgid=`$rgid`&action=renew" a=1}{/capture}
   {/if}
   <a href="{$backURL}" title="{ts}Refresh List of Duplicates{/ts}" onclick="return confirm('{ts escape="js"}This will refresh the duplicates list. Click OK to proceed.{/ts}');" class="button"><span>{ts}Refresh Duplicates{/ts}</span></a>

   {if $gid}
      {capture assign=backURL}{crmURL p="civicrm/contact/dedupefind" q="reset=1&rgid=`$rgid`&gid=`$gid`&action=map" a=1}{/capture}
   {else}
      {capture assign=backURL}{crmURL p="civicrm/contact/dedupefind" q="reset=1&rgid=`$rgid`&action=map" a=1}{/capture}
   {/if}
   <a href="{$backURL}" title="{ts}Batch Merge Duplicate Contacts{/ts}" onclick="return confirm('{ts escape="js"}This will run the batch merge process on the listed duplicates. The operation will run in safe mode - only records with no direct data conflicts will be merged. Click OK to proceed if you are sure you wish to run this operation.{/ts}');" class="button"><span>{ts}Batch Merge Duplicates{/ts}</span></a>

   {capture assign=backURL}{crmURL p="civicrm/contact/deduperules" q="reset=1" a=1}{/capture}
  <a href="{$backURL}" class="button crm-button-type-cancel"><span>{ts}Done{/ts}</span></a>
{/if}
<div style="clear: both;"></div>
{else}
{include file="CRM/Contact/Form/DedupeFind.tpl"}
{/if}

{* process the dupe contacts *}
{include file='CRM/common/dedupe.tpl'}
{literal}
<script type="text/javascript">
var oTable = null;
</script>
{/literal}
