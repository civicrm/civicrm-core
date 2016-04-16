{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
<div class="crm-block crm-form-block crm-contact-task-addtoOrganization-form-block">
<div class="help">
    {ts}Choose Relationship Type and Target Organization{/ts}
</div>
<table class="form-layout-compressed">
    <tr><td></td><td>{include file="CRM/Contact/Form/Task.tpl"}</td></tr>
     {if $action EQ 2} {* action = update *}
        <tr><td></td><td><label>{$sort_name}</label></td></tr>
     {else} {* action = add *}
        <tr class="crm-contact-task-addtoOrganization-form-block-relationship_type_id">
          <td>{$form.relationship_type_id.label}</td>
          <td>{$form.relationship_type_id.html}</td>
        </tr>
        <tr class="crm-contact-task-addtoOrganization-form-block-name">
          <td>{$form.name.label}</td><td>{$form.name.html}</td>
        </tr>
        <tr><td></td><td>{$form._qf_AddToOrganization_refresh.html}&nbsp;&nbsp;{$form._qf_AddToOrganization_cancel.html}</td></tr>
</table>
{if $searchDone } {* Search button clicked *}
    {if $searchCount}
        {if $searchRows} {* we've got rows to display *}
            <fieldset><legend>{ts}Mark Target Contact(s) for this Relationship{/ts}</legend>
            <div class="description">
                {ts}Mark the target contact(s) for this relationship if it appears below. Otherwise you may modify the search name above and click Search again.{/ts}
             </div>
                        {strip}
                        <table>
                        <tr class="columnheader">
                        <td>&nbsp;</td>
                        <td>{ts}Name{/ts}</td>
                        <td>{ts}City{/ts}</td>
                        <td>{ts}State{/ts}</td>
                        <td>{ts}Email{/ts}</td>
                        <td>{ts}Phone{/ts}</td>
                        </tr>
                        {foreach from=$searchRows item=row}
                        <tr class="{cycle values="odd-row,even-row"}">
                            <td>{$form.contact_check[$row.id].html}</td>
                            <td>{$row.type} {$row.name}</td>
                            <td>{$row.city}</td>
                            <td>{$row.state}</td>
                            <td>{$row.email}</td>
                            <td>{$row.phone}</td>
                        </tr>
                        {/foreach}
                        </table>
                        {/strip}
                        </fieldset>
                    {else} {* too many results - we're only displaying 50 *}
                        </div></fieldset>
                        {capture assign=infoTitle}{ts}Too many matching results{/ts}{/capture}
                        {capture assign=infoMessage}{ts}Please narrow your search by entering a more complete target contact name.{/ts}{/capture}
                        {include file="CRM/common/info.tpl"}
                    {/if}
                {else} {* no valid matches for name + contact_type *}
                        </div></fieldset>
                        {capture assign=infoTitle}{ts}No matching results for{/ts}{/capture}
                        {capture assign=infoMessage}<ul><li>{ts 1=$form.name.value}Name like: %1{/ts}</li><li>{ts 1=$contact_type_display}Contact Type: %1{/ts}</li></ul>{ts}Check your spelling, or try fewer letters for the target contact name.{/ts}{/capture}
                        {include file="CRM/common/info.tpl"}
                {/if} {* end if searchCount *}
              {else}
                </div></fieldset>
              {/if} {* end if searchDone *}
        {/if} {* end action = add *}

        {* Only show start/end date and buttons if action=update, OR if we have $contacts (results)*}
        {if $searchRows OR $action EQ 2}
            <div class="form-item">

                    <div class="description">

                    </div>
                   <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
            </div>
  <div class="form-item">
  {$form.status.label} {$form.status.html}
  </div>


            </div></fieldset>
  {/if}



