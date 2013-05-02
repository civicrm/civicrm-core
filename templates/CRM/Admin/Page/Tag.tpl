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

{capture assign=docLink}{docURL page="user/organising-your-data/groups-and-tags"}{/capture}

{if $action eq 1 or $action eq 2 or $action eq 8}
    {include file="CRM/Admin/Form/Tag.tpl"}
{else}
<div class="crm-content-block">
    <div id="help">
        {ts 1=$docLink}Tags can be assigned to any contact record, and are a convenient way to find contacts. You can create as many tags as needed to organize and segment your records.{/ts} {$docLink}
    </div>

    {if $rows}
        {if !($action eq 1 and $action eq 2)}
            <div class="crm-submit-buttons">
              <div class="action-link">
                    <a href="{crmURL q="action=add&reset=1"}" id="newTag" class="button"><span><div class="icon add-icon"></div>{ts}Add Tag{/ts}</span></a>
                    {if $adminTagSet}
                        <a href="{crmURL q="action=add&reset=1&tagset=1"}" id="newTagSet" class="button"><span><div class="icon add-icon"></div>{ts}Add Tag Set{/ts}</span></a>
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
                    <a href="{crmURL q="action=add&reset=1"}" id="newTag" class="button"><span><div class="icon add-icon"></div>{ts}Add Tag{/ts}</span></a>
                    {if $adminTagSet}
                        <a href="{crmURL q="action=add&reset=1&tagset=1"}" id="newTagSet" class="button"><span><div class="icon add-icon"></div>{ts}Add Tag Set{/ts}</span></a>
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

<div id="mergeTagDialog">
    {ts}Begin typing name of tag to merge into.{/ts}<br/>
    <input type="text" id="tag_name"/>
    <input type="hidden" id="tag_name_id" value="">
    <div id="used_for_warning" class="status message"></div>
</div>

</div>

{literal}
<script type="text/javascript">
cj("#mergeTagDialog").hide( );
cj( function() {
    cj('.merge_tag').click(function(){
        var row_id = cj(this).closest('tr').attr('id');
        var tagId = row_id.split('-');
        mergeTag(tagId[1]);
    });
});

function mergeTag( fromId ) {
    var fromTag = cj('#tag-' + fromId).children('td.crm-tag-name').text();
    cj('#used_for_warning').html('');

    cj("#mergeTagDialog").show( );
  cj("#mergeTagDialog").dialog({
    title: "Merge tag '" + fromTag + "' into:",
    modal: true,
    bgiframe: true,
    close: function(event, ui) { cj("#tag_name").unautocomplete( ); },
    overlay: {
      opacity: 0.5,
      background: "black"
    },

    open:function() {
      cj("#tag_name").val( "" );
      cj("#tag_name_id").val( null );

      var tagUrl = {/literal}"{crmURL p='civicrm/ajax/mergeTagList' h=0 q='fromId='}"{literal} + fromId;

      cj("#tag_name").autocomplete( tagUrl, {
        width: 260,
        selectFirst: false,
        matchContains: true
      });

      cj("#tag_name").focus();
      cj("#tag_name").result(function(event, data, formatted) {
        cj("input[id=tag_name_id]").val(data[1]);
                                if ( data[2] == 1 ) {
                                    cj('#used_for_warning').html("Warning: '" + fromTag + "' has different used-for options than the selected tag, which would be merged into the selected tag. Click Ok to proceed.");
                                } else {
                                    cj('#used_for_warning').html('');
                                }
      });
    },

    buttons: {
      "Ok": function() {
        if ( ! cj("#tag_name").val( ) ) {
          alert('{/literal}{ts escape="js"}Select valid tag from the list{/ts}{literal}.');
          return false;
        }
        var toId = cj("#tag_name_id").val( );
        if ( ! toId ) {
          alert('{/literal}{ts escape="js"}Select valid tag from the list{/ts}{literal}.');
          return false;
        }

                /* send synchronous request so that disabling any actions for slow servers*/
        var postUrl = {/literal}"{crmURL p='civicrm/ajax/mergeTags' h=0 }"{literal};
        var data    = {fromId: fromId, toId: toId, key:{/literal}"{crmKey name='civicrm/ajax/mergeTags'}"{literal}};
                cj.ajax({ type     : "POST",
            url      : postUrl,
            data     : data,
            dataType : "json",
            success  : function( values ) {
                        if ( values.status == true ) {
                            cj('#tag-' + toId).children('td.crm-tag-used_for').text(values.tagB_used_for);
                            var msg = "'" + values.tagA + "' has been merged with '" + values.tagB + "'. All records previously tagged with '" + values.tagA + "' are now tagged with '" + values.tagB + "'.";
                            cj('#tag-' + fromId).html('<td colspan="8"><div class="status message"><div class="icon inform-icon"></div>' + msg + '</div></td>');
                        }
                      }
                });

                cj(this).dialog("close");
        cj(this).dialog("destroy");
       },

      "Cancel": function() {
        cj(this).dialog("close");
        cj(this).dialog("destroy");
      }
          }
  });
}
</script>
{/literal}

{/if}
{include file="CRM/common/crmeditable.tpl"}
