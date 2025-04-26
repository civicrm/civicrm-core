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
      <div class="messages help">{$cd_edit.help_pre}</div>
    {/if}
    {if $multiRecordDisplay neq 'single'}
    <table class="no-border">
      {assign var='index' value=$groupId|cat:"_$cvID"}
      {if $showEdit && $cd_edit.editable && $groupId && $editPermission}
        <tr>
          <td>
            <a
              href="{crmURL p="civicrm/contact/view/cd/edit" q="tableId=`$contactId`&cid=`$contactId`&groupID=`$groupId`&action=update&reset=1"}"
              class="button" style="margin-left: 6px;"><span><i class="crm-i fa-pencil" aria-hidden="true"></i> {ts 1=$cd_edit.title}Edit %1{/ts}</span></a><br/><br/>
          </td>
        </tr>
      {/if}
      {assign var="showEdit" value=0}
      <tr>
        <td id="{$cd_edit.name}_{$index}" class="section-shown form-item">
          <details class="crm-accordion-bold" {if !empty($cd_edit.collapse_display) && empty($skipTitle)}{else}open{/if}>
            {if !$skipTitle}
              <summary>
                {$cd_edit.title}
              </summary>
            {/if}
            <div class="crm-accordion-body">
              {if $groupId and $cvID and $editPermission and $cd_edit.editable}
                <div class="crm-submit-buttons">
                  <a href="#" class="crm-hover-button crm-custom-value-del"
                     data-post='{ldelim}"valueID": "{$cvID}", "groupID": "{$customGroupId}", "contactId": "{$contactId}", "key": "{crmKey name='civicrm/ajax/customvalue'}"{rdelim}'
                     title="{ts escape='htmlattribute' 1=$cd_edit.title|cat:" `$rowCount`"}Delete %1{/ts}">
                    <i class="crm-i fa-trash" aria-hidden="true"></i> {ts}Delete{/ts}
                  </a>
                </div>
              {/if}
              {if !empty($cd_edit.fields)}
                <table class="crm-info-panel">
                  {foreach from=$cd_edit.fields item=element key=field_id}
                    <tr>
                      <td class="label">{$element.field_title}</td>
                      <td class="html-adjust">
                        {if $element.options_per_line != 0}
                          {* sort by fails for option per line. Added a variable to iterate through the element array*}
                          {foreach from=$element.field_value item=val}
                            {$val}
                            <br/>
                          {/foreach}
                        {else}
                          {if $element.field_data_type == 'Money'}
                            {if $element.field_type == 'Text'}
                              {$element.data|crmMoney}
                            {else}
                              {$element.field_value}
                            {/if}
                          {else}
                            {if $element.field_data_type EQ 'ContactReference' && $element.contact_ref_links}
                              {$element.contact_ref_links|join:', '}
                            {else}
                              {$element.field_value}
                            {/if}
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
    {else}
      {foreach from=$cd_edit.fields item=element key=field_id}
        <div class="crm-section">
          {if $element.options_per_line != 0}
              <div class="label">{$element.field_title}</div>
              <div class="content">
              {* sort by fails for option per line. Added a variable to iterate through the element array*}
              {foreach from=$element.field_value item=val}
                {$val}
                <br/>
              {/foreach}
              </div>
          {else}
              <div class="label">{$element.field_title}</div>
              {if $element.field_type == 'File'}
                <div class="content">
                {if $element.field_value}
                  {$element.field_value}
                {else}
                  <br/>
                {/if}
                </div>
              {else}
                {if $element.field_data_type == 'Money'}
                  {if $element.field_type == 'Text'}
                    <div class="content">{if $element.field_value}{$element.field_value|crmMoney}{else}<br/>{/if}</div>
                  {else}
                    <div class="content">{if $element.field_value}{$element.field_value}{else}<br/>{/if}</div>
                  {/if}
                {else}
                  <div class="content">
                    {if $element.field_data_type EQ 'ContactReference' && $element.contact_ref_links}
                      {$element.contact_ref_links|join:', '}
                    {else}
                      {if $element.field_value}{$element.field_value} {else}<br/>{/if}
                    {/if}
                  </div>
                {/if}
              {/if}
            {/if}
          </div>
        {/foreach}
      {/if}
      {if $cd_edit.help_post}
        <div class="messages help">{$cd_edit.help_post}</div>
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
