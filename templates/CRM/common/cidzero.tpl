{if $nocid}
  <div class="crm-other-contact-row messages status">
    <span>{ts}You are completing this form on behalf of someone else. Please enter their details.</span>{/ts}
  {if !empty($selectable)}
    <div class="crm-contact-select-row">
      <div class="crm-content">
        {$form.select_contact_id.html}
      </div>
    </div>
  {/if}
  </div>
{/if}
