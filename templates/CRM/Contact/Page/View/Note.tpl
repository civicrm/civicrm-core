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
<div class="view-content">
{if $action eq 4}{* when action is view  *}
    {if $notes}
        <h3>{ts}View Note{/ts}</h3>
        <div class="crm-block crm-content-block crm-note-view-block">
          <table class="crm-info-panel">
            <tr><td class="label">{ts}Subject{/ts}</td><td>{$note.subject}</td></tr>
            <tr><td class="label">{ts}Date:{/ts}</td><td>{$note.modified_date|crmDate}</td></tr>
            <tr><td class="label">{ts}Privacy:{/ts}</td><td>{$note.privacy}</td></tr>
            <tr><td class="label"></td><td>{$note.note|nl2br}</td></tr>

            {if $currentAttachmentInfo}
               {include file="CRM/Form/attachment.tpl"}
            {/if}
          </table>
          <div class="crm-submit-buttons"><input type="button" name='cancel' value="{ts}Done{/ts}" onclick="location.href='{crmURL p='civicrm/contact/view' q='action=browse&selectedChild=note'}';"/></div>

        {if $comments}
        <fieldset>
        <legend>{ts}Comments{/ts}</legend>
            <table class="display">
                <thead>
                    <tr><th>{ts}Comment{/ts}</th><th>{ts}Created By{/ts}</th><th>{ts}Date{/ts}</th></tr>
                </thead>
                {foreach from=$comments item=comment}
                    <tr class="{cycle values='odd-row,even-row'}"><td>{$comment.note}</td><td>{$comment.createdBy}</td><td>{$comment.modified_date}</td></tr>
                {/foreach}
            </table>
        </fieldset>
        {/if}

        </div>
        {/if}
{elseif $action eq 1 or $action eq 2} {* action is add or update *}
    <h3>
        {if $parentId}
            {if $action eq 1}{ts}New Comment{/ts}{else}{ts}Edit Comment{/ts}{/if}
        {else}
            {if $action eq 1}{ts}New Note{/ts}{else}{ts}Edit Note{/ts}{/if}
        {/if}
    </h3>
  <div class="crm-block crm-form-block crm-note-form-block">
    <div class="content crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
        <table class="form-layout">
            <tr>
                <td class="label">{$form.subject.label}</td>
                <td>
                    {$form.subject.html}
                </td>
            </tr>
            <tr>
                <td class="label">{$form.privacy.label}</td>
                <td>
                    {$form.privacy.html}
                </td>
            </tr>
            <tr>
                <td class="label">{$form.note.label}</td>
                <td>
                    {$form.note.html}
                </td>
            </tr>
            <tr class="crm-activity-form-block-attachment">
                <td colspan="2">
                    {include file="CRM/Form/attachment.tpl"}
                </td>
            </tr>
        </table>

  <div class="crm-section note-buttons-section no-label">
   <div class="content crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
   <div class="clear"></div>
  </div>
    </div>
    {* include jscript to warn if unsaved form field changes *}
    {include file="CRM/common/formNavigate.tpl"}
{/if}
{if ($action eq 8)}
<fieldset><legend>{ts}Delete Note{/ts}</legend>
<div class=status>{ts 1=$notes.$id.note}Are you sure you want to delete the note '%1'?{/ts}</div>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl"}</div>
</fieldset>

{/if}

{if $permission EQ 'edit' AND ($action eq 16 or $action eq 4 or $action eq 8)}
   <div class="action-link">
   <a accesskey="N" href="{crmURL p='civicrm/contact/view/note' q="cid=`$contactId`&action=add"}" class="button"><span><div class="icon add-icon"></div>{ts}Add Note{/ts}</span></a>
   </div>
   <div class="clear"></div>
{/if}
<div class="crm-content-block">

{if $notes}

<script type="text/javascript">
    var commentAction = '{$commentAction|escape:quotes}'

    {literal}
    var commentRows = {};

    function showHideComments( noteId ) {

        elRow = cj('tr#cnote_'+ noteId)

        if (elRow.hasClass('view-comments')) {
            cj('tr.note-comment_'+ noteId).remove()
            commentRows['cnote_'+ noteId] = {};
            cj('tr#cnote_'+ noteId +' span.icon_comments_show').show();
            cj('tr#cnote_'+ noteId +' span.icon_comments_hide').hide();
            elRow.removeClass('view-comments');
        } else {
            var getUrl = {/literal}"{crmURL p='civicrm/ajax/rest' h=0}"{literal};
            cj.post(getUrl, { fnName: 'civicrm/note/tree_get', json: 1, id: noteId, sequential: 1 }, showComments, 'json' );
        }

    }

    function showComments (response) {
        var urlTemplate = '{/literal}{crmURL p='civicrm/contact/view' q="reset=1&cid=" h=0 }{literal}'
        if (response['values'][0] && response['values'][0].entity_id) {
            var noteId = response['values'][0].entity_id
            var row = cj('tr#cnote_'+ noteId);

            row.addClass('view-comments');

            if (row.hasClass('odd') ) {
                var rowClassOddEven = 'odd'
            } else {
                var rowClassOddEven = 'even'
            }

            if ( commentRows['cnote_'+ noteId] ) {
                for ( var i in commentRows['cnote_'+ noteId] ) {
                    return false;
                }
            } else {
                commentRows['cnote_'+ noteId] = {};
            }
            for (i in response['values']) {
                if ( response['values'][i].id ) {
                    if ( commentRows['cnote_'+ noteId] &&
                        commentRows['cnote_'+ noteId][response['values'][i].id] ) {
                        continue;
                    }
                    str = '<tr id="cnote_'+ response['values'][i].id +'" class="'+ rowClassOddEven +' note-comment_'+ noteId +'">'
                        + '<td></td>'
                        + '<td style="padding-left: 2em">'
                        + response['values'][i].note
                        + '</td><td>'
                        + response['values'][i].subject
                        + '</td><td>'
                        + response['values'][i].modified_date
                        + '</td><td>'
                        + '<a href="'+ urlTemplate + response['values'][i].createdById +'">'+ response['values'][i].createdBy +'</a>'
                        + '</td><td>'+ commentAction.replace(/{cid}/g, response['values'][i].createdById).replace(/{id}/g, response['values'][i].id) +'</td></tr>'

                    commentRows['cnote_'+ noteId][response['values'][i].id] = str;
                }
            }
            drawCommentRows('cnote_'+ noteId);

            cj('tr#cnote_'+ noteId +' span.icon_comments_show').hide();
            cj('tr#cnote_'+ noteId +' span.icon_comments_hide').show();
        } else {
            CRM.alert('{/literal}{ts escape="js"}There are no comments for this note{/ts}{literal}', '{/literal}{ts escape="js"}None Found{/ts}{literal}', 'alert');
        }

    }

    function drawCommentRows(rowId) {
        row = cj('tr#'+ rowId)
        for (i in commentRows[rowId]) {
            row.after(commentRows[rowId][i]);
            row = cj('tr#cnote_'+ i);
        }
    }

    {/literal}
</script>

<div class="crm-results-block">
{* show browse table for any action *}
<div id="notes">
    {strip}
    {include file="CRM/common/jsortable.tpl"}

    <script type="text/javascript">
    {literal}
        cj(document).ready( function() {
            var tabId = cj.fn.dataTableSettings[0].sInstance;

            cj('table#'+ tabId).dataTable().fnSettings().aoDrawCallback.push( {
                    "fn": function () {
                        cj('#'+ tabId +' tr').each( function() {
                            drawCommentRows(this.id)
                        });
                    },
                    "sName": "user"
            } );
        });

    {/literal}
    </script>

        <table id="options" class="display">
        <thead>
        <tr>
          <th></th>
          <th>{ts}Note{/ts}</th>
          <th>{ts}Subject{/ts}</th>
          <th>{ts}Date{/ts}</th>
          <th>{ts}Created By{/ts}</th>
          <th></th>
        </tr>
        </thead>

        {foreach from=$notes item=note}
        <tr id="cnote_{$note.id}" class="{cycle values="odd-row,even-row"} crm-note">
            <td class="crm-note-comment">
                {if $note.comment_count}
                    <span id="{$note.id}_show" style="display:block" class="icon_comments_show">
                        <a href="#" onclick="showHideComments({$note.id}); return false;" title="{ts}Show comments for this note.{/ts}"><span class="ui-icon dark-icon ui-icon-triangle-1-e"></span></a>
                    </span>
                    <span id="{$note.id}_hide" style="display:none" class="icon_comments_hide">
                        <a href="#" onclick="showHideComments({$note.id}); return false;" title="{ts}Hide comments for this note.{/ts}"><span class="ui-icon dark-icon ui-icon-triangle-1-s"></span></a>
                    </span>
                {else}
                    <span class="ui-icon ui-icon-triangle-1-e" id="{$note.id}_hide" style="display:none"></span>
                {/if}
            </td>
            <td class="crm-note-note">
                {$note.note|mb_truncate:80:"...":false|nl2br}
                {* Include '(more)' link to view entire note if it has been truncated *}
                {assign var="noteSize" value=$note.note|count_characters:true}
                {if $noteSize GT 80}
            <a href="{crmURL p='civicrm/contact/view/note' q="action=view&selectedChild=note&reset=1&cid=`$contactId`&id=`$note.id`"}">{ts}(more){/ts}</a>
                {/if}
            </td>
            <td class="crm-note-subject">{$note.subject}</td>
            <td class="crm-note-modified_date">{$note.modified_date|crmDate}</td>
            <td class="crm-note-createdBy">
                <a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$note.contact_id`"}">{$note.createdBy}</a>
            </td>
            <td class="nowrap">{$note.action|replace:'xx':$note.id}</td>
        </tr>
        {/foreach}
        </table>
    {/strip}
 </div>
</div>
{elseif ! ($action eq 1)}
   <div class="messages status no-popup">
        <div class="icon inform-icon"></div>
        {capture assign=crmURL}{crmURL p='civicrm/contact/view/note' q="cid=`$contactId`&action=add"}{/capture}
        {ts 1=$crmURL}There are no Notes for this contact. You can <a accesskey="N" href='%1'>add one</a>.{/ts}
   </div>
{/if}
</div>
</div>
