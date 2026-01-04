{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* This file provides the plugin for the openid block *}
{* @var $form Contains the array for the form elements and other form associated information assigned to the template by the controller*}
{* @var $blockId Contains the current block Id, and assigned in the CRM/Contact/Form/Location.php file *}

{if !$addBlock}
<tr>
  <td>{ts}Open ID{/ts}</td>
  <td>{ts}Open ID Location{/ts}</td>
  <td id="OpenID-Primary" class="hiddenElement">{ts}Primary?{/ts}</td>
</tr>
{/if}

<tr id="OpenID_Block_{$blockId}">
    <td>{$form.openid.$blockId.openid.html|crmAddClass:twenty}&nbsp;</td>
    <td>{$form.openid.$blockId.location_type_id.html}</td>
    <td align="center" id="OpenID-Primary-html" {if $blockId eq 1}class="hiddenElement"{/if}>{$form.openid.$blockId.is_primary.1.html}</td>
    {if $blockId gt 1}
    <td><a href="#" title="{ts escape='htmlattribute'}Delete OpenID Block{/ts}" onClick="removeBlock('OpenID','{$blockId}'); return false;">{ts}delete{/ts}</a></td>
    {/if}
</tr>
{if !$addBlock}
<tr>
<td colspan="4">
&nbsp;&nbsp;<a href="#" title="{ts escape='htmlattribute'}Add{/ts}" onClick="buildAdditionalBlocks( 'OpenID', '{$className}');return false;">{ts}Add another Open Id{/ts}</a>
</td>
</tr>
{/if}

