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
{* this template is used for adding/editing relationship types  *}
<h3>{if $action eq 1}{ts}New Relationship Type{/ts}{elseif $action eq 2}{ts}Edit Relationship Type{/ts}{elseif $action eq 8}{ts}Delete Relationship Type{/ts}{else}{ts}View Relationship Type{/ts}{/if}</h3>
<div class="crm-block crm-form-block crm-relationship-type-form-block">
    {if $action neq 4} {* action is not view *}
        <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
    {else}
        <div class="crm-submit-buttons">{$form.done.html}</div>
    {/if}
  {if $action eq 8}
      <div class="messages status no-popup">
          <div class="icon inform-icon"></div>
          {ts}WARNING: Deleting this option will result in the loss of all Relationship records of this type.{/ts} {ts}This may mean the loss of a substantial amount of data, and the action cannot be undone.{/ts} {ts}Do you want to continue?{/ts}


      </div>
     {else}
      <table class="form-layout-compressed">
            <tr class="crm-relationship-type-form-block-label_a_b">
                <td class="label">{$form.label_a_b.label}</td>
                <td>{$form.label_a_b.html}<br />
                <span class="description">{ts}Label for the relationship from Contact A to Contact B. EXAMPLE: Contact A is 'Parent of' Contact B.{/ts}</span></td>
            </tr>
            <tr class="crm-relationship-type-form-block-label_b_a">
                <td class="label">{$form.label_b_a.label}</td>
                <td>{$form.label_b_a.html}<br />
                <span class="description">{ts}Label for the relationship from Contact B to Contact A. EXAMPLE: Contact B is 'Child of' Contact A. You may leave this blank for relationships where the name is the same in both directions (e.g. Spouse).{/ts}</span></td>
            </tr>
            <tr class="crm-relationship-type-form-block-contact_types_a">
                <td class="label">{$form.contact_types_a.label}</td>
                <td>{$form.contact_types_a.html}</td>
            </tr>
            <tr class="crm-relationship-type-form-block-contact_types_b">
                <td class="label">{$form.contact_types_b.label}</td>
                <td>{$form.contact_types_b.html}</td>
            </tr>
            <tr class="crm-relationship-type-form-block-description">
                <td class="label">{$form.description.label}</td>
                <td>{$form.description.html}</td>
            </tr>
            <tr class="crm-relationship-type-form-block-is_active">
                <td class="label">{$form.is_active.label}</td>
                <td>{$form.is_active.html}</td>
            </tr>
        </table>
    {/if}
  {if $action neq 4} {* action is not view *}
            <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
        {else}
            <div class="crm-submit-buttons">{$form.done.html}</div>
        {/if}

</fieldset>
</div>
