{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
{* Custom Data view mode*}
{assign var="showEdit" value=1}
{assign var="rowCount" value=1}
{foreach from=$viewCustomData item=customValues key=customGroupId}
  {foreach from=$customValues item=cd_edit key=cvID}
{if $multiRecordDisplay neq 'single'}
    <table class="no-border">
      {assign var='index' value=$groupId|cat:"_$cvID"}
      {if ($editOwnCustomData and $showEdit) or ($showEdit and $editCustomData and $groupId)}
        <tr>
          <td>
            <a
              href="{crmURL p="civicrm/contact/view/cd/edit" q="tableId=`$contactId`&cid=`$contactId`&groupID=`$groupId`&action=update&reset=1"}"
              class="button" style="margin-left: 6px;"><span><div
                  class="icon edit-icon"></div>{ts 1=$cd_edit.title}Edit %1{/ts}</span></a><br/><br/>
          </td>
        </tr>
      {/if}
      {assign var="showEdit" value=0}
        <tr id="statusmessg_{$index}" class="hiddenElement">
          <td><span class="success-status"></span></td>
        </tr>
      <tr>
        <td id="{$cd_edit.name}_{$index}" class="section-shown form-item">
          <div class="crm-accordion-wrapper {if $cd_edit.collapse_display eq 0 or $skipTitle} {else}collapsed{/if}">
            {if !$skipTitle}
              <div class="crm-accordion-header">
                {$cd_edit.title}
              </div>
            {/if}
            <div class="crm-accordion-body">
              {if $groupId and $cvID and $editCustomData}
                <div class="crm-submit-buttons">
                  <a href="#" class="crm-hover-button crm-custom-value-del"
                     data-post='{ldelim}"valueID": "{$cvID}", "groupID": "{$customGroupId}", "contactId": "{$contactId}", "key": "{crmKey name='civicrm/ajax/customvalue'}"{rdelim}'
                     title="{ts 1=$cd_edit.title|cat:" `$rowCount`"}Delete %1{/ts}">
                    <span class="icon delete-icon"></span> {ts}Delete{/ts}
                  </a>
                </div>
              {/if}
              {foreach from=$cd_edit.fields item=element key=field_id}
                <table class="crm-info-panel">
                  <tr>
                    {if $element.options_per_line != 0}
                      <td class="label">{$element.field_title}</td>
                      <td class="html-adjust">
                        {* sort by fails for option per line. Added a variable to iterate through the element array*}
                        {foreach from=$element.field_value item=val}
                          {$val}
                          <br/>
                        {/foreach}
                      </td>
                    {else}
                      <td class="label">{$element.field_title}</td>
                      {if $element.field_type == 'File'}
                        {if $element.field_value.displayURL}
                          <td class="html-adjust">
                            <a href="{$element.field_value.displayURL}" class='crm-image-popup'>
                              <img src="{$element.field_value.displayURL}" height="100" width="100">
                            </a>
                          </td>
                        {else}
                          <td class="html-adjust">
                            <a href="{$element.field_value.fileURL}">{$element.field_value.fileName}</a>
                          </td>
                        {/if}
                      {else}
                        {if $element.field_data_type == 'Money'}
                          {if $element.field_type == 'Text'}
                            <td class="html-adjust">{$element.field_value|crmMoney}</td>
                          {else}
                            <td class="html-adjust">{$element.field_value}</td>
                          {/if}
                        {else}
                          <td class="html-adjust">
                            {if $element.contact_ref_id}
                            <a href='{crmURL p="civicrm/contact/view" q="reset=1&cid=`$element.contact_ref_id`"}'>
                              {/if}
                              {if $element.field_data_type == 'Memo'}
                                {$element.field_value|nl2br}
                              {else}
                                {$element.field_value}
                              {/if}
                              {if $element.contact_ref_id}
                            </a>
                            {/if}
                          </td>
                        {/if}
                      {/if}
                    {/if}
                  </tr>
                </table>
              {/foreach}
              {assign var="rowCount" value=$rowCount+1}
            </div>
            <!-- end of body -->
            <div class="clear"></div>
          </div>
          <!-- end of main accordian -->
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
          {if $element.field_value.displayURL}
            <div class="content">
              <a href="{$element.field_value.displayURL}" class='crm-image-popup'>
               <img src="{$element.field_value.displayURL}" height="100" width="100">
              </a>
            </div>
          {else}
            <div class="content">
             {if $element.field_value}
              <a href="{$element.field_value.fileURL}">{$element.field_value.fileName}</a>
             {else}
              <br/>
             {/if}
            </div>
          {/if}
          {else}
            {if $element.field_data_type == 'Money'}
              {if $element.field_type == 'Text'}
                 <div class="content">{if $element.field_value}{$element.field_value|crmMoney}{else}<br/>{/if}</div>
              {else}
                 <div class="content">{if $element.field_value}{$element.field_value}{else}<br/>{/if}</div>
              {/if}
            {else}
              <div class="content">
                {if $element.contact_ref_id}
                  <a href='{crmURL p="civicrm/contact/view" q="reset=1&cid=`$element.contact_ref_id`"}'>
                {/if}
                {if $element.field_data_type == 'Memo'}
                  {$element.field_value|nl2br}
                {else}
                  {if $element.field_value}{$element.field_value} {else}<br/>{/if}
                {/if}
                {if $element.contact_ref_id}
                  </a>
                {/if}
              </div>
            {/if}
          {/if}
       {/if}
     </div>
   {/foreach}
{/if}
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

