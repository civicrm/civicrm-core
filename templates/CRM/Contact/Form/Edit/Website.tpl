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
    <td colspan="3">{if $blockId > 1} <a href="#" title="{ts}Delete Website Block{/ts}" onClick="removeBlock('Website','{$blockId}'); return false;">{ts}delete{/ts}</a>{/if}</td>
</tr>
{if !$addBlock}
<tr>
<td colspan="4">
&nbsp;&nbsp;<a href="#" title={ts}Add{/ts} onClick="buildAdditionalBlocks( 'Website', '{$className}');return false;">{ts}Add another website{/ts}</a>
</td>
</tr>
{/if}

