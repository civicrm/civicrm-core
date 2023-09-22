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
   {include file="CRM/Mailing/Form/Component.tpl"}
{else}

<div id="ltype">
 <p></p>
    <div class="form-item">
       {strip}
       {* handle enable/disable actions*}
       {include file="CRM/common/enableDisableApi.tpl"}
       <table cellpadding="0" cellspacing="0" border="0">
        <thead class="sticky">
        <th>{ts}Name{/ts}</th>
        <th>{ts}Type{/ts}</th>
        <th>{ts}Subject{/ts}</th>
        <th>{ts}Body HTML{/ts}</th>
        <th>{ts}Body Text{/ts}</th>
        <th>{ts}Default?{/ts}</th>
        <th>{ts}Enabled?{/ts}</th>
        <th></th>
        </thead>
       {foreach from=$rows item=row}
         <tr id="mailing_component-{$row.id}" class="crm-entity {cycle values="odd-row,even-row"}{if !empty($row.class)} {$row.class}{/if}{if NOT $row.is_active} disabled{/if}">
           <td class="crm-editable" data-field="name">{$row.name}</td>
           <td>{$row.component_type}</td>
           <td>{$row.subject}</td>
           <td>{$row.body_html|escape}</td>
           <td>{$row.body_text|escape}</td>
           <td>{icon condition=$row.is_default}{ts}Default{/ts}{/icon}&nbsp;</td>
     <td id="row_{$row.id}_status">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
           <td>{$row.action|smarty:nodefaults|replace:'xx':$row.id}</td>
        </tr>
       {/foreach}
       </table>
       {/strip}

       {if $action ne 1 and $action ne 2}
  <br/>
       <div class="action-link">
       {crmButton q="action=add&reset=1" icon="plus-circle"}{ts}Add Mailing Component{/ts}{/crmButton}
       </div>
       {/if}
    </div>
</div>
{/if}
