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
{* This file provides the template for inline editing of phones *}
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
      <td>{ts}Phone{/ts}&nbsp;
      {if $actualBlockCount lt 5 }
        <span id="add-more-phone" title="{ts}click to add more{/ts}"><a class="crm-link-action add-more-inline">{ts}add{/ts}</a></span>
      {/if}
      </td>
      <td>{ts}Phone Location{/ts}</td>
      <td>{ts}Phone Type{/ts}</td>
      <td>{ts}Primary?{/ts}</td>
      <td>&nbsp;</td>
    </tr>
    {section name='i' start=1 loop=$totalBlocks}
    {assign var='blockId' value=$smarty.section.i.index}
    <tr id="Phone_Block_{$blockId}" {if $blockId gt $actualBlockCount}class="hiddenElement"{/if}>
        <td>{$form.phone.$blockId.phone.html}&nbsp;&nbsp;{ts}ext.{/ts}&nbsp;{$form.phone.$blockId.phone_ext.html|crmAddClass:four}&nbsp;</td>
        <td>{$form.phone.$blockId.location_type_id.html}</td>
        <td>{$form.phone.$blockId.phone_type_id.html}</td>
        <td align="center" class="crm-phone-is_primary">{$form.phone.$blockId.is_primary.1.html}</td>
        <td>
          {if $blockId gt 1}
            <a class="crm-delete-inline crm-link-action" title="{ts}delete phone block{/ts}">{ts}delete{/ts}</a>
          {/if}
        </td>
    </tr>
    {/section}
</table>

{literal}
<script type="text/javascript">
  cj(function($) {
    // check first primary radio
    $('#Phone_1_IsPrimary').prop('checked', true );
  });
</script>
{/literal}
