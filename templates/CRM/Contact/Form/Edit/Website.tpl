{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* This file provides the plugin for the Website block *}
{* @var $form Contains the array for the form elements and other form associated information assigned to the template by the controller*}
{* @var $blockId Contains the current block id, assigned in the CRM/Contact/Form/Location.php file *}

{if !$addBlock}
<tr>
    <td>{ts}Website{/ts}
        &nbsp;&nbsp;{help id="id-website" file="CRM/Contact/Form/Contact.hlp"}
    </td>
    <td>{ts}Website Type{/ts}</td>
    <td colspan="2"></td>
    <td id="Website-Primary" class="hiddenElement"></td>
</tr>
{/if}

<tr id="Website_Block_{$blockId}">
    <td>{$form.website.$blockId.url.html|crmAddClass:url}&nbsp;</td>
    <td>{$form.website.$blockId.website_type_id.html}</td>
    {if $blockId gt 1}
      <td colspan="3"><a href="#" title="{ts escape='htmlattribute'}Delete Website Block{/ts}" onClick="removeBlock('Website','{$blockId}'); return false;">{ts}delete{/ts}</a></td>
    {/if}
</tr>
{if !$addBlock}
<tr>
<td colspan="4">
&nbsp;&nbsp;<a href="#" title="{ts escape='htmlattribute'}Add{/ts}" onClick="buildAdditionalBlocks( 'Website', '{$className}');return false;">{ts}Add another website{/ts}</a>
</td>
</tr>
{/if}
