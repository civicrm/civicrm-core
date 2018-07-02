{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
{* This file provides the template for inline editing of ims *}
{$form.oplock_ts.html}
<table class="crm-inline-edit-form">
    <tr>
      <td colspan="5">
        <div class="crm-submit-buttons">
          {include file="CRM/common/formButtons.tpl"}
        </div>
      </td>
    </tr>
    <tr>
      <td>{ts}Instant Messenger{/ts}&nbsp;
      {if $actualBlockCount lt 5 }
        <span id="add-more-im" title="{ts}click to add more{/ts}"><a class="crm-hover-button action-item add-more-inline" href="#">{ts}add{/ts}</a></span>
      {/if}
      </td>
      <td>{ts}IM Location{/ts}</td>
      <td>{ts}IM Type{/ts}</td>
      <td>{ts}Primary?{/ts}</td>
      <td>&nbsp;</td>
    </tr>
    {section name='i' start=1 loop=$totalBlocks}
    {assign var='blockId' value=$smarty.section.i.index}
    <tr id="IM_Block_{$blockId}" {if $blockId gt $actualBlockCount}class="hiddenElement"{/if}>
        <td>{$form.im.$blockId.name.html}&nbsp;</td>
        <td>{$form.im.$blockId.location_type_id.html}</td>
        <td>{$form.im.$blockId.provider_id.html}</td>
        <td align="center" class="crm-im-is_primary">{$form.im.$blockId.is_primary.1.html}</td>
        <td>
          {if $blockId gt 1}
            <a class="crm-delete-inline crm-hover-button" href="#" title="{ts}Delete IM{/ts}"><span class="icon delete-icon"></span></a>
          {/if}
        </td>
    </tr>
    {/section}
</table>

{literal}
<script type="text/javascript">
    CRM.$(function($) {
      // check first primary radio
      $('#IM_1_IsPrimary').prop('checked', true );
    });
</script>
{/literal}
