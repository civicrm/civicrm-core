{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Delete action *}
{if ($action eq 8)}
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
