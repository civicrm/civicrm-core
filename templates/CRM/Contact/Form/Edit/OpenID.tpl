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
    <td><a href="#" title="{ts}Delete OpenID Block{/ts}" onClick="removeBlock('OpenID','{$blockId}'); return false;">{ts}delete{/ts}</a></td>
    {/if}
</tr>
{if !$addBlock}
<tr>
<td colspan="4">
&nbsp;&nbsp;<a href="#" title={ts}Add{/ts} onClick="buildAdditionalBlocks( 'OpenID', '{$className}');return false;">{ts}Add another Open Id{/ts}</a>
</td>
</tr>
{/if}

