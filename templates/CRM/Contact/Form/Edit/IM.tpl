{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* This file provides the plugin for the Instant Messenger block *}
{* @var $form Contains the array for the form elements and other form associated information assigned to the template by the controller*}
{* @var $blockId Contains the current block id, assigned in the CRM/Contact/Form/Location.php file *}

{if !$addBlock}
<tr>
    <td>{ts}Instant Messenger{/ts}</td>
    <td>{ts}IM Location{/ts}</td>
    <td colspan="2">{ts}IM Type{/ts}</td>
    <td id="IM-Primary" class="hiddenElement">{ts}Primary?{/ts}</td>
</tr>
{/if}

<tr id="IM_Block_{$blockId}">
    <td>{$form.im.$blockId.name.html|crmAddClass:twenty}&nbsp;</td>
    <td>{$form.im.$blockId.location_type_id.html}</td>
    <td colspan="2">{$form.im.$blockId.provider_id.html}</td>
    <td align="center" id="IM-Primary-html" {if $blockId eq 1}class="hiddenElement"{/if}>{$form.im.$blockId.is_primary.1.html}</td>
    {if $blockId gt 1}
        <td><a href="#" title="{ts}Delete IM Block{/ts}" onClick="removeBlock('IM','{$blockId}'); return false;">{ts}delete{/ts}</a></td>
    {/if}
</tr>
{if !$addBlock}
<tr>
<td colspan="4">
&nbsp;&nbsp;<a href="#" title={ts}Add{/ts} onClick="buildAdditionalBlocks( 'IM', '{$className}');return false;">{ts}Add another IM{/ts}</a>
</td>
</tr>
{/if}

