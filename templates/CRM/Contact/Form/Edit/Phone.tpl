{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
{* This file provides the plugin for the phone block *}
{* @var $form Contains the array for the form elements and other form associated information assigned to the template by the controller*}
{* @var blockId Contains the current block id, and assigned in the CRM/Contact/Form/Location.php file *}

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
  <td>{$form.phone.$blockId.phone.html}&nbsp;&nbsp;{ts}ext.{/ts}&nbsp;{$form.phone.$blockId.phone_ext.html|crmAddClass:four}&nbsp;</td>
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
  &nbsp;&nbsp;<a id='addPhone' href="#" title={ts}Add{/ts} onClick="buildAdditionalBlocks( 'Phone', '{$className}');return false;">{ts}Add another Phone number{/ts}</a>
  </td>
</tr>
{/if}

