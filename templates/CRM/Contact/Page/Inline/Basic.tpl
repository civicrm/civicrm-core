<div class="crm-clear crm-inline-block-content">
  <div class="crm-summary-row">
    <div class="crm-label" id="tagLink">
      <a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=$contactId&selectedChild=tag"}"
         title="{ts}Edit Tags{/ts}">{ts}Tags{/ts}</a>
    </div>
    <div class="crm-content" id="tags">
      {foreach from=$contactTag item=tagName key=tagId}
        <span class="crm-tag-item" {if !empty($allTags.$tagId.color)}style="background-color: {$allTags.$tagId.color}; color: {$allTags.$tagId.color|colorContrast};"{/if} title="{$allTags.$tagId.description|escape}">
          {$tagName}
        </span>
      {/foreach}
    </div>
  </div>
  <div class="crm-summary-row">
    <div class="crm-label">{ts}Contact Type{/ts}</div>
    <div class="crm-content crm-contact_type_label">
      {$contact_type_label}
    </div>
  </div>
  <div class="crm-summary-row">
    <div class="crm-label">
      {ts}Contact ID{/ts}{if $userRecordUrl} / {ts}User ID{/ts}{/if}
    </div>
    <div class="crm-content">
      <span class="crm-contact-contact_id">{$contactId}</span>
      {if $userRecordUrl}
        <span class="crm-contact-user_record_id">
          &nbsp;/&nbsp;<a title="{ts}View user record{/ts}" class="user-record-link"
                          href="{$userRecordUrl}">{$userRecordId}</a>
        </span>
      {/if}
    </div>
  </div>
  <div class="crm-summary-row">
    <div class="crm-label">{ts}External ID{/ts}</div>
    <div class="crm-content crm-contact_external_identifier_label">
      {$external_identifier}
    </div>
  </div>
</div>
