{$form.preferred_communication_method.label nofilter}
<br/>
{$form.preferred_communication_method.html nofilter}
<br/>

{if $form.email_on_hold.type == 'select'}
  <br/>
  {$form.email_on_hold.label nofilter}
  <br/>
  {$form.email_on_hold.html nofilter}
  <br/>
{elseif $form.email_on_hold.type == 'checkbox'}
  <div class="spacer"></div>
  {$form.email_on_hold.html nofilter}
  {$form.email_on_hold.label nofilter}
{/if}
