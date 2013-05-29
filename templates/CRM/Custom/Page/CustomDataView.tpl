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
{* Custom Data view mode*}
{assign var="showEdit" value=1}
{foreach from=$viewCustomData item=customValues key=customGroupId}
  {foreach from=$customValues item=cd_edit key=cvID}
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
                  <a href="#"
                     onclick="showDelete( {$cvID}, '{$cd_edit.name}_{$index}', {$customGroupId}, {$contactId} ); return false;"
                     class="button delete-button" title="{ts 1=$cd_edit.title}Delete this %1 record{/ts}">
                    <span><div class="icon delete-icon"></div>{ts}Delete{/ts}</span>
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
                          <td class="html-adjust"><a href="#"
                                                     onclick="imagePopUp('{$element.field_value.imageURL}'); return false;"><img
                                src="{$element.field_value.displayURL}" height="100" width="100"></a></td>
                        {else}
                          <td class="html-adjust"><a
                              href="{$element.field_value.fileURL}">{$element.field_value.fileName}</a></td>
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
            </div>
            <!-- end of body -->
            <div class="clear"></div>
          </div>
          <!-- end of main accordian -->
        </td>
      </tr>
    </table>
  {/foreach}
{/foreach}
{literal}
  <script type="text/javascript">
    cj(function () {
      cj().crmAccordions();
    });
  </script>
{/literal}
{*currently delete is available only for tab custom data*}
{if $groupId}
  <script type="text/javascript">
    {literal}
    function hideStatus(valueID, groupID) {
      cj('#statusmessg_' + groupID + '_' + valueID).hide();
    }
    function showDelete(valueID, elementID, groupID, contactID) {
      var confirmMsg = '{/literal}{ts escape='js'}Are you sure you want to delete this record?{/ts}{literal} &nbsp; <a href="#" onclick="deleteCustomValue( ' + valueID + ',\'' + elementID + '\',' + groupID + ',' + contactID + ' ); return false;" style="text-decoration: underline;">{/literal}{ts escape='js'}Yes{/ts}{literal}</a>&nbsp;&nbsp;&nbsp;<a href="#" onclick="hideStatus( ' + valueID + ', ' + groupID + ' ); return false;" style="text-decoration: underline;">{/literal}{ts escape='js'}No{/ts}{literal}</a>';
      cj('tr#statusmessg_' + groupID + '_' + valueID).show().children().find('span').html(confirmMsg);
    }
    function deleteCustomValue(valueID, elementID, groupID, contactID) {
      var postUrl = {/literal}"{crmURL p='civicrm/ajax/customvalue' h=0 }"{literal};
      cj.ajax({
        type: "POST",
        data: "valueID=" + valueID + "&groupID=" + groupID + "&contactId=" + contactID + "&key={/literal}{crmKey name='civicrm/ajax/customvalue'}{literal}",
        url: postUrl,
        success: function (html) {
          cj('#' + elementID).hide();
          hideStatus(valueID, groupID);
          CRM.alert('', '{/literal}{ts escape="js"}Record Deleted{/ts}{literal}', 'success');
          var element = cj('.ui-tabs-nav #tab_custom_' + groupID + ' a');
          cj(element).html(cj(element).attr('title') + ' (' + html + ') ');
        }
      });
    }
    {/literal}
  </script>
{/if}

