{if !empty($nocid)}
  <div class="crm-other-contact-row messages status">
    <span>{ts}You are completing this form on behalf of someone else. Please enter their details.</span>{/ts}
  {if !empty($selectable)}
    {ts} <p> Click <a href=# id='crm-contact-toggle-{$selectable}'>here</a>
     <span id='crm-contact-toggle-text-{$selectable}'> to select someone already in our database.</span></p>{/ts}
    <span id='crm-contact-toggle-hidden-text-{$selectable}'>
    </span>
    <div class="crm-contact-select-row">
      <div class="crm-content">
        {$form.select_contact.html}
      </div>
    </div>
  {/if}
  </div>
{/if}
