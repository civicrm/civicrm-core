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
{if $action eq 1 or $action eq 2 or $action eq 8}
   {include file="CRM/Admin/Form/ContactType.tpl"}
{else}

  <div id="help">
    {ts}CiviCRM comes with 3 basic (built-in) contact types: Individual, Household, and Organization. You can create additional contact types based on these types to further differentiate contacts (for example you might create Student, Parent, Staff, and /or Volunteer types from the basic Individual type...).{/ts} {help id="id-contactSubtype-intro"}
  </div>
{if $rows}
<div>
    {strip}
    {* handle enable/disable actions*}
    {include file="CRM/common/enableDisableApi.tpl"}
    {include file="CRM/common/crmeditable.tpl"}
    {include file="CRM/common/jsortable.tpl"}
    <table id="options" class="display">
    <thead>
    <tr>
        <th>{ts}Contact Type{/ts}</th>
        <th>{ts}Based On{/ts}</th>
        <th id="nosort">{ts}Description{/ts}</th>
        <th></th>
    </tr>
    </thead>
    {foreach from=$rows item=row}
      <tr id="contact_type-{$row.id}" class="{cycle values="odd-row,even-row"} {$row.class} crm-contactType crm-entity {if NOT $row.is_active} disabled{/if}">
        <td class="crm-contactType-label crm-editable" data-field="label">{ts}{$row.label}{/ts}</td>
        <td class="crm-contactType-parent">{if $row.parent}{ts}{$row.parent_label}{/ts}{else}{ts}(built-in){/ts}{/if}</td>
        <td class="crm-contactType-description crm-editable" data-field="description" data-type="textarea">{$row.description}</td>
        <td>{$row.action|replace:'xx':$row.id}</td>
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
    <a href="{crmURL q="action=add&reset=1"}" class="button"><span><div class="icon add-icon"></div>{ts}Add Contact Type{/ts}</span></a>
    <a href="{crmURL p="civicrm/admin" q="reset=1"}" class="button cancel no-popup"><span><div class="icon ui-icon-close"></div> {ts}Done{/ts}</span></a>
  </div>
{/if}
