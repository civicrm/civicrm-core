{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
      <div id="browseValues_{$contactType}">
        <div>
        {strip}
          <table id="options_{$contactType}" class="display mergecontact">
            <thead>
            <tr>
              <th>{ts 1=$contactType}%1 Rules{/ts}</th>
              <th>{ts}Usage{/ts}</th>
              <th></th>
            </tr>
            </thead>
            {foreach from=$rows item=row}
              <tr class="{cycle values="odd-row,even-row"}">
                <td>{$row.title}</td>
                <td>{$row.used_display}</td>
                <td>{$row.action|replace:'xx':$row.id}</td>
              </tr>
            {/foreach}
          </table>
        {/strip}
       </div>
       <div style="float:right">
            {crmButton q="action=add&contact_type=$contactType&reset=1" icon="plus-circle"}{ts 1=$contactType}Add Rule for %1s{/ts}{/crmButton}
        </div>
      </div>
    {/foreach}
    {/if}

{/if}
