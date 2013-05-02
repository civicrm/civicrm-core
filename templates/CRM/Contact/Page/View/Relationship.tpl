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
{* Relationship tab within View Contact - browse, and view relationships for a contact *}
{if !empty($cdType) }
  {include file="CRM/Custom/Form/CustomData.tpl"}
{else}
 <div class="view-content">
   {if $action eq 1 or $action eq 2 or $action eq 4 or $action eq 8} {* add, update or view *}
    {include file="CRM/Contact/Form/Relationship.tpl"}
  {/if}
<div class="crm-block crm-content-block">
  {if $action NEQ 1 AND $action NEQ 2 AND $permission EQ 'edit'}
        <div class="action-link">
            <a accesskey="N" href="{crmURL p='civicrm/contact/view/rel' q="cid=`$contactId`&action=add&reset=1"}" class="button"><span><div class="icon add-icon"></div>{ts}Add Relationship{/ts}</span></a>
        </div>
  {/if}
  {include file="CRM/common/jsortable.tpl" useAjax=0}
  {* start of code to show current relationships *}
  {if $currentRelationships}
    {* show browse table for any action *}
      <div id="current-relationships">
        {if $relationshipTabContext} {*to show the title and links only when viewed from relationship tab, not from dashboard*}
         <h3>{ts}Current Relationships{/ts}</h3>
        {/if}
        {strip}
        <table id="current_relationship" class="display">
        <thead>
        <tr>
            <th>{ts}Relationship{/ts}</th>
            <th></th>
            <th id="start_date">{ts}Start{/ts}</th>
            <th id="end_date">{ts}End{/ts}</th>
            <th>{ts}City{/ts}</th>
            <th>{ts}State/Prov{/ts}</th>
            <th>{ts}Email{/ts}</th>
            <th>{ts}Phone{/ts}</th>
            <th></th>
            <th class="hiddenElement"></th>
            <th class="hiddenElement"></th>
        </tr>
        </thead>
        {foreach from=$currentRelationships item=rel}
            {*assign var = "rtype" value = "" }
            {if $rel.contact_a eq $contactId }
                {assign var = "rtype" value = "a_b" }
            {else}
                {assign var = "rtype" value = "b_a" }
            {/if*}

            <tr id="rel_{$rel.id}" class="{cycle values="odd-row,even-row"} row-relationship {if $rel.is_permission_a_b eq 1 or $rel.is_permission_b_a eq 1}row-highlight{/if}">

            {if $relationshipTabContext}
                <td class="bold">
                   <a href="{crmURL p='civicrm/contact/view/rel' q="action=view&reset=1&selectedChild=rel&cid=`$contactId`&id=`$rel.id`&rtype=`$rel.rtype`"}">{$rel.relation}</a>
      {if ($rel.cid eq $rel.contact_id_a and $rel.is_permission_a_b eq 1) OR
          ($rel.cid eq $rel.contact_id_b and $rel.is_permission_b_a eq 1) }
                <span id="permission-b-a" class="crm-marker permission-relationship"> *</span>
            {/if}
    </td>
                <td>
       <a href="{crmURL p='civicrm/contact/view' q="action=view&reset=1&cid=`$rel.cid`"}">{$rel.name}</a>
            {if ($contactId eq $rel.contact_id_a and $rel.is_permission_a_b eq 1) OR
          ($contactId eq $rel.contact_id_b and $rel.is_permission_b_a eq 1) }
              <span id="permission-a-b" class="crm-marker permission-relationship"> *</span>
            {/if}
    </td>
            {else}
                <td class="bold">{$rel.relation}</strong></td>
                <td>{$rel.name}</td>
            {/if}
                <td class="crm-rel-start_date">{$rel.start_date}</td>
                <td class="crm-rel-end_date">{$rel.end_date}</td>
                <td>{$rel.city}</td>
                <td>{$rel.state}</td>
                <td>{$rel.email}</td>
                <td>{$rel.phone}</td>
                <td class="nowrap">{$rel.action|replace:'xx':$rel.id}</td>
                <td class="start_date hiddenElement">{$rel.start_date|crmDate}</td>
                <td class="end_date hiddenElement">{$rel.end_date|crmDate}</td>
            </tr>
        {/foreach}
        </table>
        {/strip}
        </div>

        {if $relationshipTabContext}
            <div id="permission-legend" class="crm-content-block">
                 <span class="crm-marker">* </span>{ts}Indicates a permissioned relationship. This contact can be viewed and updated by the other.{/ts}
            </div>
        {/if}
{/if}
{* end of code to show current relationships *}

{if NOT ($currentRelationships or $inactiveRelationships) }

  {if $action NEQ 1} {* show 'no relationships' message - unless already in 'add' mode. *}
       <div class="messages status no-popup">
            <div class="icon inform-icon"></div>
           {capture assign=crmURL}{crmURL p='civicrm/contact/view/rel' q="cid=`$contactId`&action=add&reset=1"}{/capture}
           {if $permission EQ 'edit'}
                    {ts 1=$crmURL}There are no Relationships entered for this contact. You can <a accesskey="N" href='%1'>add one</a>.{/ts}
                {elseif ! $relationshipTabContext}
                    {ts}There are no related contacts / organizations on record for you.{/ts}
                {else}
                    {ts}There are no Relationships entered for this contact.{/ts}
                {/if}
        </div>
  {/if}
{/if}
</div>
<div class="spacer"></div>

{* start of code to show inactive relationships *}
{if $inactiveRelationships}
    {* show browse table for any action *}
      <div id="inactive-relationships">
        <p></p>
        <div class="label font-red">{ts}Inactive Relationships{/ts}</div>
        <div class="description">{ts}These relationships are Disabled OR have a past End Date.{/ts}</div>
        {strip}
        <table id="inactive_relationship" class="display">
        <thead>
        <tr>
            <th>{ts}Relationship{/ts}</th>
            <th></th>
            <th>{ts}City{/ts}</th>
            <th>{ts}State/Prov{/ts}</th>
            <th>{ts}Phone{/ts}</th>
            <th id="dis-end_date">{ts}End Date{/ts}</th>
            <th></th>
            <th class="hiddenElement"></th>
        </tr>
        </thead>
        {foreach from=$inactiveRelationships item=rel}
          {assign var = "rtype" value = "" }
          {if $rel.contact_a > 0 }
            {assign var = "rtype" value = "b_a" }
          {else}
            {assign var = "rtype" value = "a_b" }
          {/if}
          <tr id="rel_{$rel.id}" class="{cycle values="odd-row,even-row"}">
            <td class="bold">{$rel.relation}</td>
            <td><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$rel.cid`"}">{$rel.name}</a></td>
            <td>{$rel.city}</td>
            <td>{$rel.state}</td>
            <td>{$rel.phone}</td>
            <td>{$rel.end_date}</td>
            <td class="nowrap">{$rel.action|replace:'xx':$rel.id}</td>
            <td class="dis-end_date hiddenElement">{$rel.end_date|crmDate}</td>
          </tr>
        {/foreach}
        </table>
        {/strip}
        </div>
{/if}

{* end of code to show inactive relationships *}


</div>
{/if} {* close of custom data else*}

{if !empty($searchRows) }
 {*include custom data js file*}
 {include file="CRM/common/customData.tpl"}
{/if}
