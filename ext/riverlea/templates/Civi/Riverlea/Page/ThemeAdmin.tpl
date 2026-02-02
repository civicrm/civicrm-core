<h2>{ts}Themes{/ts}</h2>

<civi-riverlea-stream-list></civi-riverlea-stream-list>

<h2>{ts}Additional settings{/ts}</h2>

<dl>
  {foreach $settings as $setting}
    <div>
      <dt>{$setting.title}</dt>
      <dd>{$setting.value_label}</dd>
    </div>
  {/foreach}
</dl>

<a class="btn btn-primary" href="{$settingsFormUrl}" target="crm-popup">
  <i class="crm-i fa-pencil" role="img" aria-hidden="true"></i>
  {ts}Edit{/ts}
</a>
