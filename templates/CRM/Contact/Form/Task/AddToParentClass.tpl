{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{assign var="class" value="_qf_AddTo"|cat:$contactType}
{assign var="refresh" value="$class"|cat:"_refresh"}
{assign var="cancel" value="$class"|cat:"_cancel"}
<div class="crm-block crm-form-block crm-contact-task-addto{$contactType}-form-block">
  <div class="help">
    {ts 1=$contactType}Choose Relationship Type and Target %1{/ts}
  </div>
    <table class="form-layout-compressed">
        <tr><td></td><td>{include file="CRM/Contact/Form/Task.tpl"}</td></tr>
            {if $action EQ 2} {* action = update *}
                <tr><td><label>{$sort_name}</label></td></tr>
            {else} {* action = add *}
                <tr class="crm-contact-task-addto{$contactType}-form-block-relationship_type_id">
                    <td>{$form.relationship_type_id.label}</td>
                    <td>{$form.relationship_type_id.html}</td>
                </tr>
                <tr><td></td></tr>
                <tr class="crm-contact-task-addto{$contactType}-form-block-name">
                    <td>{$form.name.label}</td>
                    <td>{$form.name.html}</td>
                </tr>
                <tr><td></td><td>{$form.$refresh.html}&nbsp;&nbsp;{$form.$cancel.html}</td></tr>
     </table>
         {if $searchDone} {* Search button clicked *}
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

        {* Only show buttons if action=update, OR if we have $contacts (results)*}
        {if $searchRows OR $action EQ 2}
            <div class="form-item">

                    <div class="description">

                    </div>
               <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location=''}</div>
            </div>


            </div></fieldset>
  {/if}
