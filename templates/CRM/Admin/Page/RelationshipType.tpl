{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{capture assign=customURL}{crmURL p='civicrm/admin/custom/group' q="reset=1"}{/capture}
{capture assign=docLink}{docURL page="user/organising-your-data/relationships"}{/capture}

{if $action eq 1 or $action eq 2 or $action eq 4 or $action eq 8}
   {include file="CRM/Admin/Form/RelationshipType.tpl"}
{else}
  <div class="help">
    <p>{ts}Relationship types describe relationships between people, households and organizations. Relationship types labels describe the relationship from the perspective of each of the two entities (e.g. Parent &gt;-&lt; Child, Employer &gt;-&lt; Employee). For some types of relationships, the labels may be the same in both directions (e.g. Spouse &gt;-&lt; Spouse).{/ts} {$docLink}</p>
    <p>{ts 1=$customURL}You can define as many additional relationships types as needed to cover the types of relationships you want to track. Once a relationship type is created, you may also define custom fields to extend relationship information for that type from <a href='%1'>Administer CiviCRM &raquo; Custom Data</a>.{/ts}{help id='id-relationship-types'} </p>
  </div>

<div class="crm-content-block crm-block">
{if $rows}
{if !($action eq 1 and $action eq 2)}
    <div class="action-link">
      {crmButton q="action=add&reset=1" class="newRelationshipType" icon="plus-circle"}{ts}Add Relationship Type{/ts}{/crmButton}
    </div>
{/if}

<div id="ltype">

    {strip}
  {* handle enable/disable actions*}
  {include file="CRM/common/enableDisableApi.tpl"}
    {include file="CRM/common/jsortable.tpl"}
        <table id="options" class="display">
        <thead>
        <tr>
          <th id="sortable">{ts}Relationship A to B{/ts}</th>
          <th>{ts}Relationship B to A{/ts}</th>
          <th>{ts}Contact Type A{/ts}</th>
          <th>{ts}Contact Type B{/ts}</th>
          <th>{ts}Enabled?{/ts}</th>
          <th></th>
        </tr>
        </thead>
        {foreach from=$rows item=row}
        <tr id="relationship_type-{$row.id}" class="crm-entity {cycle values="odd-row,even-row"}{if !empty($row.class)} {$row.class}{/if}{if NOT $row.is_active} disabled{/if} crm-relationship">
            <td class="crm-relationship-label_a_b crm-editable" data-field="label_a_b">{$row.label_a_b}</td>
            <td class="crm-relationship-label_b_a crm-editable" data-field="label_b_a">{$row.label_b_a}</td>
            <td class="crm-relationship-contact_type_a_display">
                {if $row.contact_type_a_display} {$row.contact_type_a_display}
                {if !empty($row.contact_sub_type_a)} - {$row.contact_sub_type_a} {/if}{else} {ts}All Contacts{/ts} {/if} </td>
            <td class="crm-relationship-contact_type_b_display">
                {if $row.contact_type_b_display} {$row.contact_type_b_display}
                {if !empty($row.contact_sub_type_b)} - {$row.contact_sub_type_b}{/if} {else} {ts}All Contacts{/ts} {/if} </td>
            <td class="crm-relationship-is_active" id="row_{$row.id}_status">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
            <td>{$row.action|smarty:nodefaults|replace:'xx':$row.id}</td>
        </tr>
        {/foreach}
        </table>
        {/strip}

</div>
{else}
    <div class="messages status no-popup">
      <img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}"/>
      {ts}None found.{/ts}
    </div>
{/if}
  <div class="action-link">
    {crmButton q="action=add&reset=1" class="newRelationshipType" icon="plus-circle"}{ts}Add Relationship Type{/ts}{/crmButton}
    {crmButton p="civicrm/admin" q="reset=1" class="cancel" icon="times"}{ts}Done{/ts}{/crmButton}
  </div>
{/if}
</div>
