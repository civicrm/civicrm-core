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
{capture assign=customURL}{crmURL p='civicrm/admin/custom/group' q="reset=1"}{/capture}
{capture assign=docLink}{docURL page="user/organising-your-data/relationships"}{/capture}
<div id="help">
    <p>{ts}Relationship types describe relationships between people, households and organizations. Relationship types labels describe the relationship from the perspective of each of the two entities (e.g. Parent <-> Child, Employer <-> Employee). For some types of relationships, the labels may be the same in both directions (e.g. Spouse <-> Spouse).{/ts} {$docLink}</p>
    <p>{ts 1=$customURL}You can define as many additional relationships types as needed to cover the types of relationships you want to track. Once a relationship type is created, you may also define custom fields to extend relationship information for that type from <a href='%1'>Administer CiviCRM &raquo; Custom Data</a>.{/ts}{help id='id-relationship-types'} </p>
</div>

{if $action eq 1 or $action eq 2 or $action eq 4 or $action eq 8}
   {include file="CRM/Admin/Form/RelationshipType.tpl"}
{else}

{if $rows}
{if !($action eq 1 and $action eq 2)}
    <div class="action-link">
      <a href="{crmURL q="action=add&reset=1"}" id="newRelationshipType" class="button"><span><div class="icon add-icon"></div>{ts}Add Relationship Type{/ts}</span></a>
    </div>
{/if}

<div id="ltype">

    {strip}
  {* handle enable/disable actions*}
  {include file="CRM/common/enableDisable.tpl"}
    {include file="CRM/common/jsortable.tpl"}
        <table id="options" class="display">
        <thead>
        <tr>
          <th id="sortable">{ts}Relationship A to B{/ts}</th>
          <th>{ts}Relationship B to A{/ts}</th>
          <th>{ts}Contact Type A{/ts}</th>
          <th>{ts}Contact Type B{/ts}</th>
          <th></th>
        </tr>
        </thead>
        {foreach from=$rows item=row}
        <tr id="row_{$row.id}" class="{cycle values="odd-row,even-row"} {$row.class}{if NOT $row.is_active} disabled{/if} crm-relationship">
            <td class="crm-relationship-label_a_b">{$row.label_a_b}</td>
            <td class="crm-relationship-label_b_a">{$row.label_b_a}</td>
            <td class="crm-relationship-contact_type_a_display">
                {if $row.contact_type_a_display} {$row.contact_type_a_display}
                {if $row.contact_sub_type_a} - {$row.contact_sub_type_a} {/if}{else} {ts}All Contacts{/ts} {/if} </td>
            <td class="crm-relationship-contact_type_b_display">
                {if $row.contact_type_b_display} {$row.contact_type_b_display}
                {if $row.contact_sub_type_b} - {$row.contact_sub_type_b}{/if} {else} {ts}All Contacts{/ts} {/if} </td>
            <td>{$row.action|replace:'xx':$row.id}</td>
        </tr>
        {/foreach}
        </table>
        {/strip}

        {if !($action eq 1 and $action eq 2)}
            <div class="action-link">
              <a href="{crmURL q="action=add&reset=1"}" id="newRelationshipType" class="button"><span><div class="icon add-icon"></div>{ts}Add Relationship Type{/ts}</span></a>
            </div>
        {/if}
</div>
{else}
    <div class="messages status no-popup">
        <div class="icon inform-icon"></div>
        {capture assign=crmURL}{crmURL p='civicrm/admin/reltype' q="action=add&reset=1"}{/capture}
        {ts 1=$crmURL}There are no relationship types present. You can <a href='%1'>add one</a>.{/ts}
    </div>
{/if}
{/if}