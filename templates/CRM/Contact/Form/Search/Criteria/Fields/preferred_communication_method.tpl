{$form.preferred_communication_method.label}
<br/>
{$form.preferred_communication_method.html}
<br/>

{if $form.email_on_hold.type == 'select'}
  <br/>
  {$form.email_on_hold.label}
  <br/>
  {$form.email_on_hold.html}
  <br/>
{elseif $form.email_on_hold.type == 'checkbox'}
  <div class="spacer"></div>
  {$form.email_on_hold.html}
  {$form.email_on_hold.label}
{/if}
