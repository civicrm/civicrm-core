{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* This file provides the template for inline editing of openids *}
<table class="crm-inline-edit-form">
  <tr>
    <td colspan="4">
      <div class="crm-submit-buttons">
        {include file="CRM/common/formButtons.tpl" location=''}
      </div>
    </td>
  </tr>

  <tr>
    <td>{ts}Open ID{/ts}&nbsp;
    {if $actualBlockCount lt 5}
      <span id="add-more-openid" title="{ts escape='htmlattribute'}click to add more{/ts}"><a class="crm-hover-button action-item add-more-inline" href="#">{ts}add{/ts}</a></span>
    {/if}
    </td>
    <td>{ts}Open ID Location{/ts}</td>
     <td id="OpenID-Primary">{ts}Primary?{/ts}</td>
    <td>&nbsp;</td>
  </tr>

  {section name='i' start=1 loop=$totalBlocks}
  {assign var='blockId' value=$smarty.section.i.index}
  <tr data-entity='openid' data-block-number={$blockId} id="OpenID_Block_{$blockId}" {if $blockId gt $actualBlockCount}class="hiddenElement"{/if}>
    <td>{$form.openid.$blockId.openid.html|crmAddClass:twenty}&nbsp;</td>
    <td>{$form.openid.$blockId.location_type_id.html}</td>
    <td align="center" id="OpenID-Primary-html" class="crm-openid-is_primary">{$form.openid.$blockId.is_primary.1.html}</td>
    <td>
      {if $blockId gt 1}
        <a class="crm-delete-inline crm-hover-button" href="#" title="{ts escape='htmlattribute'}Delete OpenID{/ts}"><span class="icon delete-icon"></span></a>
      {/if}
    </td>
    {include file="CRM/Contact/Form/Inline/BlockCustomData.tpl" entity=openid customFields=$custom_fields_openid blockId=$blockId actualBlockCount=$actualBlockCount}
  </tr>
  {/section}
</table>

{literal}
<script type="text/javascript">
    CRM.$(function($) {
      // check first primary radio
      $('#OpenID_1_IsPrimary').prop('checked', true );
    });
</script>
{/literal}
