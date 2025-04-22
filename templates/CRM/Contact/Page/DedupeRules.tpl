{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $action eq 1 or $action eq 2}
  {include file="CRM/Contact/Form/DedupeRules.tpl"}
{elseif $action eq 4}
{include file="CRM/Contact/Form/DedupeFind.tpl"}
{else}
    <div class="help">
       {ts}Manage the rules used to identify potentially duplicate contact records. Scan for duplicates using a selected rule and merge duplicate contact data as needed.{/ts} {help id="id-dedupe-intro"}
    </div>
    {if $hasperm_administer_dedupe_rules}
       <div class="action-link">
        <a href="{crmURL p='civicrm/dedupe/exception' q='reset=1'}" class="button"><span>{ts}View the Dedupe Exceptions{/ts}</span></a>
        </div>
    {/if}
    {if $brows}
    {include file="CRM/common/jsortable.tpl"}
    {foreach from=$brows key=contactType item=rows}
      <div id="browseValues_{$contactType}" class="crm-clearfix">
        <div>
        {strip}
          <table id="options_{$contactType}" class="display mergecontact">
            <thead>
            <tr>
              <th>{ts 1=$contactTypes.$contactType}%1 Rules{/ts}</th>
              <th>{ts}Usage{/ts}</th>
              <th id="nosort"><span class="sr-only">{ts}Actions{/ts}</span></th>
            </tr>
            </thead>
            {foreach from=$rows item=row}
              <tr class="{cycle values="odd-row,even-row"}">
                <td>{$row.title}</td>
                <td>{$row.used_display}</td>
                <td>{$row.action|smarty:nodefaults|replace:'xx':$row.id}</td>
              </tr>
            {/foreach}
          </table>
        {/strip}
       </div>
       <div style="float:right">
            {crmButton q="action=add&contact_type=$contactType&reset=1" icon="plus-circle"}{ts 1=$contactTypes.$contactType}Add %1 Rule{/ts}{/crmButton}
        </div>
      </div>
    {/foreach}
    {/if}

{/if}
