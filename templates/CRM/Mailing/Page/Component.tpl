{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
         <tr id="mailing_component-{$row.id}" class="crm-entity {cycle values="odd-row,even-row"} {$row.class}{if NOT $row.is_active} disabled{/if}">
           <td class="crm-editable" data-field="name">{$row.name}</td>
           <td>{$row.component_type}</td>
           <td>{$row.subject}</td>
           <td>{$row.body_html|escape}</td>
           <td>{$row.body_text|escape}</td>
           <td>{if $row.is_default eq 1}<img src="{$config->resourceBase}i/check.gif" alt="{ts}Default{/ts}" />{/if}&nbsp;</td>
     <td id="row_{$row.id}_status">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
           <td>{$row.action|replace:'xx':$row.id}</td>
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
