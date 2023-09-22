{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* This file provides the plugin for the phone block *}
{* @var $form Contains the array for the form elements and other form associated information assigned to the template by the controller*}
{* @var blockId Contains the current block id, and assigned in the CRM/Contact/Form/Location.php file *}

{* note this is only called from CRM_Contact_Form_Contact in core so the className if clauses are not needed & should be phased out *}
{if !$addBlock}
  <tr>
    <td>{ts}Phone{/ts}</td>
    {if $className eq 'CRM_Contact_Form_Contact'}
    <td>{ts}Phone Location{/ts}</td>
    {/if}
    <td colspan="2">{ts}Phone Type{/ts}</td>
    {if $className eq 'CRM_Contact_Form_Contact'}
      <td id="Phone-Primary" class="hiddenElement">{ts}Primary?{/ts}</td>
    {/if}
  </tr>
{/if}
<tr id="Phone_Block_{$blockId}">
  <td>{$form.phone.$blockId.phone.html}<span class="crm-phone-ext">{ts context="phone_ext"}ext.{/ts}&nbsp;{$form.phone.$blockId.phone_ext.html|crmAddClass:four}&nbsp;</span></td>
  {if $className eq 'CRM_Contact_Form_Contact'}
  <td>{$form.phone.$blockId.location_type_id.html}</td>
  {/if}
  <td colspan="2">{$form.phone.$blockId.phone_type_id.html}</td>
  {if $className eq 'CRM_Contact_Form_Contact'}
    <td align="center" id="Phone-Primary-html" {if $blockId eq 1}class="hiddenElement"{/if}>{$form.phone.$blockId.is_primary.1.html}</td>
  {/if}
  {if $blockId gt 1}
    <td><a href="#" title="{ts}Delete Phone Block{/ts}" onClick="removeBlock('Phone','{$blockId}'); return false;">{ts}delete{/ts}</a></td>
  {/if}
</tr>

{if !$addBlock}
<tr>
  <td colspan="4">
  &nbsp;&nbsp;<a id='addPhone' href="#" title={ts}Add{/ts} onClick="buildAdditionalBlocks( 'Phone', '{$className}');return false;">{ts}Add another phone number{/ts}</a>
  </td>
</tr>
{/if}
