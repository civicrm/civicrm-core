<tr class="crm-preferences-address-form-block-address_format">
  <td class="label">{$form.address_format.label}<br />{help id='address_format'}</td>
  <td>
    <div class="helpIcon" id="helphtml">
      <input class="crm-token-selector big" data-field="address_format" />
      {capture assign='tokenTitle'}{ts}Tokens{/ts}{/capture}
      {help id="id-token-text" tplFile=$tplFile file="CRM/Contact/Form/Task/Email.hlp" title=$tokenTitle}
    </div>
    {$form.address_format.html|crmAddClass:huge12}<br />
    <span class="description">{ts}Format for displaying addresses in the Contact Summary and Event Information screens.{/ts}<br />{ts 1="&#123;contact.state_province&#125;" 2="&#123;contact.state_province_name&#125;"}Use %1 for state/province abbreviation or %2 for state province name.{/ts}</span>
  </td>
</tr>
