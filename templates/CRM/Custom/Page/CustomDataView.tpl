{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Custom Data view mode*}
{assign var="showEdit" value=1}
{assign var="rowCount" value=1}
{foreach from=$viewCustomData item=customValues key=customGroupId}
  {foreach from=$customValues item=cd_edit key=cvID}
    {crmRegion name="custom-data-view-`$cd_edit.name`"}
    {if $cd_edit.help_pre}
      <div class="messages help">{$cd_edit.help_pre|purify}</div>
    {/if}
    <table class="no-border">
      {assign var='index' value=$groupId|cat:"_$cvID"}
      {if $showEdit && $cd_edit.editable && $groupId && $editPermission}
        <tr>
          <td>
            {if $cd_edit.is_multiple}
              <a
                href="{crmURL p="civicrm/af/custom/`$cd_edit.name`/create" f="?entity_id=`$contactId`"}"
                class="button crm-popup" style="margin-left: 6px;"><span><i class="crm-i fa-plus" role="img" aria-hidden="true"></i> {ts 1=$cd_edit.title}Add %1{/ts}</span></a>
            {else}
              <a
                href="{crmURL p="civicrm/af/custom/`$cd_edit.name`/update" f="?`$cd_edit.extends`1=`$contactId`"}"
                class="button crm-popup" style="margin-left: 6px;"><span><i class="crm-i fa-pencil" role="img" aria-hidden="true"></i> {ts 1=$cd_edit.title}Edit %1{/ts}</span></a>
            {/if}
          </td>
        </tr>
      {/if}
      {assign var="showEdit" value=0}
      <tr>
        <td id="{$cd_edit.name}_{$index}" class="section-shown form-item">
          <details class="crm-accordion-bold" {if !empty($cd_edit.collapse_display) && empty($skipTitle)}{else}open{/if}>
            {if !$skipTitle}
              <summary>
                {$cd_edit.title|escape}
              </summary>
            {/if}
            <div class="crm-accordion-body">
              {if $groupId and $cvID and $editPermission and $cd_edit.editable}
                <div class="crm-submit-buttons">
                  {if $cd_edit.is_multiple}
                    <a href="{crmURL p="civicrm/af/custom/`$cd_edit.name`/update" f="?Record=`$cvID`"}"
                       class="crm-hover-button crm-popup"
                       title="{ts escape='htmlattribute' 1=$cd_edit.title|cat:" `$rowCount`"}Edit %1{/ts}">
                      <i class="crm-i fa-pencil" role="img" aria-hidden="true"></i> {ts}Edit{/ts}
                    </a>
                  {/if}
                  <a href="#" class="crm-hover-button crm-custom-value-del"
                     data-post='{ldelim}"valueID": "{$cvID}", "groupID": "{$customGroupId}", "contactId": "{$contactId}", "key": "{crmKey name='civicrm/ajax/customvalue'}"{rdelim}'
                     title="{ts escape='htmlattribute' 1=$cd_edit.title|cat:" `$rowCount`"}Delete %1{/ts}">
                    <i class="crm-i fa-trash" role="img" aria-hidden="true"></i> {ts}Delete{/ts}
                  </a>
                </div>
              {/if}
              {if !empty($cd_edit.fields)}
                <table class="crm-info-panel">
                  {foreach from=$cd_edit.fields item=element key=field_id}
                    <tr>
                      <td class="label">{$element.field_title}</td>
                      <td class="html-adjust crm-cf-datatype-{$element.field_data_type|lower} crm-cf-{$element.field_type|lower}">
                        {if $element.options_per_line != 0}
                          {* sort by fails for option per line. Added a variable to iterate through the element array*}
                          {foreach from=$element.field_value item=val}
                            {$val}
                            <br/>
                          {/foreach}
                        {else}
                          {if $element.field_data_type == 'Money' && $element.field_type == 'Text'}
                            {* todo: roll into CRM_Core_BAO_CustomField::displayValue *}
                            {$element.data|crmMoney}
                          {elseif $element.field_data_type EQ 'ContactReference' && $element.contact_ref_links}
                            {* todo: roll into CRM_Core_BAO_CustomField::displayValue *}
                            {$element.contact_ref_links|join:', '}
                          {else}
                            {* should be escaped / purified by CRM_Core_BAO_CustomField::displayValue *}
                            {$element.field_value}
                          {/if}
                        {/if}
                      </td>
                    </tr>
                  {/foreach}
                </table>
              {/if}
              {assign var="rowCount" value=$rowCount+1}
            </div>
            <!-- end of body -->
            <div class="clear"></div>
          </details>
          <!-- end of main accordion -->
        </td>
      </tr>
    </table>
      {if $cd_edit.help_post}
        <div class="messages help">{$cd_edit.help_post|purify}</div>
      {/if}
    {/crmRegion}
  {/foreach}
{/foreach}
{*currently delete is available only for tab custom data*}
{if $groupId}
  <script type="text/javascript">
    {literal}
    CRM.$(function($) {
      // Handle delete of multi-record custom data
      $('#crm-container')
        .off('.customValueDel')
        .on('click.customValueDel', '.crm-custom-value-del', function(e) {
          e.preventDefault();
          var $el = $(this),
            msg = '{/literal}{ts escape="js"}The record will be deleted immediately. This action cannot be undone.{/ts}{literal}';
          CRM.confirm({title: $el.attr('title'), message: msg})
            .on('crmConfirm:yes', function() {
              var url = CRM.url('civicrm/ajax/customvalue');
              var request = $.post(url, $el.data('post'))
                .done(CRM.refreshParent($el));
              CRM.status({success: '{/literal}{ts escape="js"}Record Deleted{/ts}{literal}'}, request);
            });
        });
      });
    {/literal}
  </script>
{/if}
