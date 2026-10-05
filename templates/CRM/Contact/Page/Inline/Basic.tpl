<div class="crm-clear crm-inline-block-content">
  <div class="crm-summary-row">
    {include file="CRM/Contact/Page/Inline/Tags.tpl" tagsLabel="{ts}Tags{/ts}"}
  </div>
  <div class="crm-summary-row">
    <div class="crm-label">{ts}Contact Type{/ts}</div>
    <div class="crm-content crm-contact_type_label">
      {$contact_type_label}
    </div>
  </div>
  <div class="crm-summary-row">
    <div class="crm-label">
      {ts}Contact ID{/ts}{if $userRecordId} / {ts}User ID{/ts}{/if}
    </div>
    <div class="crm-content">
      <span class="crm-contact-contact_id">{$contactId}</span>
      {if $userRecordId}
        <span class="crm-contact-user_record_id">
          &nbsp;/&nbsp;
          {if $userRecordUrl}
          <a title="{ts escape='htmlattribute'}View user record{/ts}" class="user-record-link"
                          href="{$userRecordUrl}">{$userRecordId}</a>
          {else}
            {$userRecordId}
          {/if}
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
