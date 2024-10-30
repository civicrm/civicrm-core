{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* View action *}
{if ($action eq 4)}
  <div class="crm-block crm-content-block crm-note-view-block">
    <table class="crm-info-panel">
      <tr><td class="label">{ts}Subject{/ts}</td><td>{$note.subject}</td></tr>
      <tr><td class="label">{ts}Date:{/ts}</td><td>{$note.note_date|crmDate}</td></tr>
      <tr><td class="label">{ts}Modified Date:{/ts}</td><td>{$note.modified_date|crmDate}</td></tr>
      <tr><td class="label">{ts}Privacy:{/ts}</td><td>{$note.privacy}</td></tr>
      <tr><td class="label">{ts}Note:{/ts}</td><td>{$note.note|nl2br}</td></tr>

        {if $currentAttachmentInfo}
            {include file="CRM/Form/attachment.tpl"}
        {/if}
    </table>
    <div class="crm-submit-buttons">
        {crmButton class="cancel" icon="times" p='civicrm/contact/view' q="selectedChild=note&reset=1&cid=`$note.entity_id`"}{ts}Done{/ts}{/crmButton}
    </div>

      {if $comments}
        <fieldset>
          <legend>{ts}Comments{/ts}</legend>
          <table class="display">
            <thead>
            <tr><th>{ts}Comment{/ts}</th><th>{ts}Created By{/ts}</th><th>{ts}Date{/ts}</th><th>{ts}Modified Date{/ts}</th></tr>
            </thead>
              {foreach from=$comments item=comment}
                <tr class="{cycle values='odd-row,even-row'}"><td>{$comment.note}</td><td>{$comment.createdBy}</td><td>{$comment.note_date}</td><td>{$comment.modified_date}</td></tr>
              {/foreach}
          </table>
        </fieldset>
      {/if}

  </div>
{* Delete action *}
{elseif ($action eq 8)}
  <div class=status>{ts}Are you sure you want to delete this note?{/ts}</div>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location=''}</div>
{* Create/Update actions *}
{else}
  <div class="crm-block crm-form-block crm-note-form-block">
    <table class="form-layout">
      <tr>
        <td class="label">{$form.subject.label}</td>
        <td>
            {$form.subject.html}
        </td>
      </tr>
      <tr>
        <td class="label">{$form.note_date.label}</td>
        <td>{$form.note_date.html}</td>
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
{/if}
