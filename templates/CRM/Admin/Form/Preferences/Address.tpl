{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{capture assign='tokenTitle'}{ts}Tokens{/ts}{/capture}
<div class="crm-block crm-form-block crm-preferences-address-form-block">
    <h3>{ts}Mailing Labels{/ts}</h3>
        <table class="form-layout">
        <tr class="crm-preferences-address-form-block-mailing_format">
            <td class="label">{$form.mailing_format.label}<br />{help id='mailing_format'}</td>
            <td>
              <div class="helpIcon" id="helphtml">
                <input class="crm-token-selector big" data-field="mailing_format" />
                {help id="id-token-text" tplFile=$tplFile file="CRM/Contact/Form/Task/Email.hlp" title=$tokenTitle}
              </div>
                {$form.mailing_format.html|crmAddClass:huge12}<br />
                <span class="description">{ts}Content and format for mailing labels.{/ts}<br />
                  {capture assign=labelFormats}href="{crmURL p='civicrm/admin/labelFormats' q='reset=1'}"{/capture}
                  {ts 1=$labelFormats}You can change the size and layout of labels at <a %1>Label Page Formats</a>.{/ts}
                </span>
            </td>
        </tr>
         <tr class="crm-preferences-address-form-block-hideCountryMailingLabels">
           <td class="label">{$form.hideCountryMailingLabels.label}
           <td>{$form.hideCountryMailingLabels.html}
           </td>
        </tr>

      </table>

    <h3>{ts}Address Display{/ts}</h3>
        <table class="form-layout">
          <tr class="crm-preferences-address-form-block-address_format">
              <td class="label">{$form.address_format.label}<br />{help id='address_format'}</td>
              <td>
              <div class="helpIcon" id="helphtml">
                <input class="crm-token-selector big" data-field="address_format" />
                {help id="id-token-text" tplFile=$tplFile file="CRM/Contact/Form/Task/Email.hlp" title=$tokenTitle}
              </div>
                {$form.address_format.html|crmAddClass:huge12}<br />
                <span class="description">{ts}Format for displaying addresses in the Contact Summary and Event Information screens.{/ts}<br />{ts 1="&#123;contact.state_province&#125;" 2="&#123;contact.state_province_name&#125;"}Use %1 for state/province abbreviation or %2 for state province name.{/ts}</span>
              </td>
          </tr>
      </table>

    <h3>{ts}Address Editing{/ts}</h3>
        <table class="form-layout">
             <tr class="crm-preferences-address-form-block-address_options">
                <td class="label">{$form.address_options.label}
                <td>{$form.address_options.html}<br />
              <span class="description">{ts}Select the fields to be included when editing a contact or event address.{/ts}</span>
                </td>
             </tr>
        </table>

    <h3>{ts}Address Standardization{/ts}</h3>
        <table class="form-layout">
             <tr class="crm-preferences-address-form-block-description">
                <td colspan="2">
                  <span class="description">
                      {ts 1="https://www.usps.com/business/web-tools-apis/welcome.htm"}CiviCRM includes an optional plugin for interfacing with the United States Postal Services (USPS) Address Standardization web service. You must register to use the USPS service at <a href='%1' target='_blank'>%1</a>. If you are approved, they will provide you with a User ID and the URL for the service.{/ts}
                      {ts}Plugins for other address standardization services may be available from 3rd party developers. If installed, they will be included in the drop-down below.{/ts}
                  </span>
              </td>
            </tr>
            <tr class="crm-preferences-address-form-block-address_standardization_provider">
              <td class="label">{$form.address_standardization_provider.label}</td>
              <td>{$form.address_standardization_provider.html}<br />
              <span class="description">{ts}Address Standardization Provider.{/ts}</span>
                </td>
            </tr>
            <tr class="crm-preferences-address-form-block-address_standardization_userid">
              <td class="label">{$form.address_standardization_userid.label}
              <td>{$form.address_standardization_userid.html}<br />
              <span class="description">{ts}Web service user ID.{/ts}</span>
                </td>
            </tr>
            <tr class="crm-preferences-address-form-block-address_standardization_url">
              <td class="label">{$form.address_standardization_url.label}
              <td>{$form.address_standardization_url.html}<br />
              <span class="description">{ts}Web Service URL{/ts}</span>
              </td>
            </tr>
        </table>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
{include file="CRM/Mailing/Form/InsertTokens.tpl"}
