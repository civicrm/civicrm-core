<tr class="crm-preferences-address-form-block-mailing_format">
  <td class="label">{$form.mailing_format.label}<br />{help id='mailing_format'}</td>
  <td>
    <div class="helpIcon" id="helphtml">
      <input class="crm-token-selector big" data-field="mailing_format" />
      {capture assign='tokenTitle'}{ts}Tokens{/ts}{/capture}
      {help id="id-token-text" tplFile=$tplFile file="CRM/Contact/Form/Task/Email.hlp" title=$tokenTitle}
    </div>
    {$form.mailing_format.html|crmAddClass:huge12}<br />
    <span class="description">{ts}Content and format for mailing labels.{/ts}<br />
                  {capture assign=labelFormats}href="{crmURL p='civicrm/admin/labelFormats' q='reset=1'}"{/capture}
      {ts 1=$labelFormats}You can change the size and layout of labels at <a %1>Label Page Formats</a>.{/ts}
                </span>
  </td>
