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

{capture assign=docLink}{docURL page="user/organising-your-data/groups-and-tags"}{/capture}

{if $action eq 1 or $action eq 2 or $action eq 8}
    {include file="CRM/Admin/Form/Tag.tpl"}
{else}
<div class="crm-content-block">
    <div class="help">
        {ts 1=$docLink}Tags can be assigned to any contact record, and are a convenient way to find contacts. You can create as many tags as needed to organize and segment your records.{/ts} {$docLink}
    </div>

    {if $rows}
        {if !($action eq 1 and $action eq 2)}
            <div class="crm-submit-buttons">
              <div class="action-link">
                    {crmButton q="action=add&reset=1" id="newTag"  icon="plus-circle"}{ts}Add Tag{/ts}{/crmButton}
                    {if $adminTagSet}
                        {crmButton q="action=add&reset=1&tagset=1" id="newTagSet"  icon="plus-circle"}{ts}Add Tag Set{/ts}{/crmButton}
                    {/if}
                </div>
            </div>
        {/if}

        {include file="CRM/common/jsortable.tpl"}
        <div id="merge_tag_status"></div>
        <div id="cat">
            {strip}
            <table id="options" class="display">
              <thead>
                    <tr>
                      <th>{ts}Tag{/ts}</th>
                      <th>{ts}ID{/ts}</th>
                      <th id="nosort">{ts}Description{/ts}</th>
                      <th>{ts}Parent (ID){/ts}</th>
                      <th>{ts}Used For{/ts}</th>
                      <th>{ts}Tag set?{/ts}</th>
                      <th>{ts}Reserved?{/ts}</th>
                      <th></th>
                    </tr>
                </thead>
                {foreach from=$rows item=row key=id }
                <tr class="{cycle values="odd-row,even-row"} {$row.class} crm-tag crm-entity" id="tag-{$row.id}">
                    <td class="crm-tag-name crm-editable crmf-name">{$row.name}</td>
                    <td class="crm-tag-id">{$row.id}</td>
                    <td class="crm-tag-description crm-editable crmf-description">{$row.description} </td>
                    <td class="crm-tag-parent">{$row.parent}{if $row.parent_id} ({$row.parent_id}){/if}</td>
              <td class="crm-tag-used_for">{$row.used_for}</td>
                    <td class="crm-tag-is_tagset">{if $row.is_tagset}<img src="{$config->resourceBase}i/check.gif" alt="{ts}Tag Set{/ts}" />{/if}</td>
                    <td class="crm-tag-is_reserved">{if $row.is_reserved}<img src="{$config->resourceBase}i/check.gif" alt="{ts}Reserved{/ts}" />{/if}</td>
                    <td>{$row.action|replace:'xx':$row.id}</td>
                </tr>
                {/foreach}
            </table>
            {/strip}
        </div>
        {if !($action eq 1 and $action eq 2)}
            <div class="crm-submit-buttons">
                <div class="action-link">
                    {crmButton q="action=add&reset=1" id="newTag"  icon="plus-circle"}{ts}Add Tag{/ts}{/crmButton}
                    {if $adminTagSet}
                        {crmButton q="action=add&reset=1&tagset=1" id="newTagSet"  icon="plus-circle"}{ts}Add Tag Set{/ts}{/crmButton}
                    {/if}
                </div>
            </div>
        {/if}
    {else}
        <div class="messages status no-popup">
        <div class="icon inform-icon"></div>&nbsp;
            {capture assign=crmURL}{crmURL p='civicrm/admin/tag' q="action=add&reset=1"}{/capture}
            {ts 1=$crmURL}There are no Tags present. You can <a href='%1'>add one</a>.{/ts}
        </div>
    {/if}

</div>

{literal}
<script type="text/javascript">
CRM.$(function($) {
  var tag;
  $('.merge_tag').click(function(e) {
    tag = $(this).crmEditableEntity();
    mergeTagDialog();
    e.preventDefault();
  });

  function mergeTagDialog() {
    var tagUrl = {/literal}"{crmURL p='civicrm/ajax/mergeTagList' h=0}"{literal};
    var title = {/literal}'{ts escape="js" 1="%1"}Merge tag %1 into:{/ts}'{literal};
    CRM.confirm({
      title: ts(title, {1: tag.name}),
      message: '<input name="select_merge_tag" class="big" />',
      open: function() {
        var dialog = this;
        $('input[name=select_merge_tag]', dialog)
          .crmSelect2({
            placeholder: {/literal}'{ts escape="js"}- select tag -{/ts}'{literal},
            minimumInputLength: 1,
            ajax: {
              url: tagUrl,
              data: function(term) {
                return {term: term, fromId: tag.id};
              },
              results: function(response) {
                return {results: response};
              }
            }
          })
          .change(function() {
            $('.messages', dialog).remove();
            if ($(this).val() && $(this).select2('data').warning) {
              $(dialog).append('<div class="messages status">{/literal}{ts escape='js'}Note: the selected tag is used by additional entities.{/ts}{literal}</div>');
            }
          });
      }
    })
      .on('dialogclose', function() {
        $('input[name=select_merge_tag]', this).select2('destroy');
      })
      .on('crmConfirm:yes', function() {
        var toId = $("input[name=select_merge_tag]", this).val();
        if (!toId) {
          $("input[name=select_merge_tag]", this).crmError('{/literal}{ts escape='js'}Select a tag{/ts}{literal}');
          return false;
        }
        var postUrl = {/literal}"{crmURL p='civicrm/ajax/mergeTags' h=0 }"{literal};
        var data = {fromId: tag.id, toId: toId, key:{/literal}"{crmKey name='civicrm/ajax/mergeTags'}"{literal}};
        $.ajax({
          type: "POST",
          url: postUrl,
          data: data,
          dataType: "json",
          success: function(values) {
            if ( values.status == true ) {
              $('#tag-' + toId).children('td.crm-tag-used_for').text(values.tagB_used_for);
              $('#tag-' + tag.id).html('<td colspan="8"><div class="status message"><div class="icon inform-icon"></div>' + values.message + '</div></td>');
            }
          }
        });
      });
  }
});
</script>
{/literal}

{/if}
