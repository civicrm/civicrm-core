{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*}
<div class="crm-block crm-form-block crm-preferences-address-form-block">
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
    <h3>{ts}Mailing Labels{/ts}</h3>
        <table class="form-layout">
        <tr class="crm-preferences-address-form-block-mailing_format">
            <td class="label">{$form.mailing_format.label}<br />{help id='label-tokens'}</td>
            <td>
              <div class="helpIcon" id="helphtml">
                <input class="crm-token-selector big" data-field="mailing_format" />
                {help id="id-token-text" tplFile=$tplFile file="CRM/Contact/Form/Task/Email.hlp"}
              </div>
                {$form.mailing_format.html|crmAddClass:huge12}<br />
                <span class="description">{ts}Content and format for mailing labels.{/ts}</span>
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
              <td class="label">{$form.address_format.label}<br />{help id='address-tokens'}</td>
              <td>
              <div class="helpIcon" id="helphtml">
                <input class="crm-token-selector big" data-field="address_format" />
                {help id="id-token-text" tplFile=$tplFile file="CRM/Contact/Form/Task/Email.hlp"}
              </div>
                {$form.address_format.html|crmAddClass:huge12}<br />
                <span class="description">{ts}Format for displaying addresses in the Contact Summary and Event Information screens.{/ts}<br />{ts 1=&#123;contact.state_province&#125; 2=&#123;contact.state_province_name&#125;}Use %1 for state/province abbreviation or %2 for state province name.{/ts}</span>
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
                      {ts 1=https://www.usps.com/business/web-tools-apis/welcome.htm}CiviCRM includes an optional plugin for interfacing with the United States Postal Services (USPS) Address Standardization web service. You must register to use the USPS service at <a href='%1' target='_blank'>%1</a>. If you are approved, they will provide you with a User ID and the URL for the service.{/ts}
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
