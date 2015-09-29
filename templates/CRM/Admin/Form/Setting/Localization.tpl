{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
<div class="crm-block crm-form-block crm-localization-form-block">
  <div class="help">
    {ts}Configure CiviCRM for your country and language.{/ts}
    {docURL page="i18n Administrator's Guide: Using CiviCRM in your own language" resource="wiki"}
  </div>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"} </div>
    <h3>{ts}Language and Currency{/ts}</h3>
        <table class="form-layout-compressed">
            <tr class="crm-localization-form-block-lcMessages">
                <td class="label">{$form.lcMessages.label}</td>
                <td>{$form.lcMessages.html}</td>
            </tr>
           {if $form.languageLimit}
             <tr class="crm-localization-form-block-languageLimit">
                 <td class="label">{$form.languageLimit.label}</td>
                 <td>{$form.languageLimit.html}<br />
                 <span class="description">{ts}Languages available to users of this installation.{/ts}</span></td>
             </tr>
             <tr class="crm-localization-form-block-addLanguage">
                 <td class="label">{$form.addLanguage.label}</td>
                 <td>{$form.addLanguage.html}<br />
                 <span class="description">{ts}Add a new language to this installation.{/ts}</span></td>
             </tr>
          {/if}
            <tr class="crm-localization-form-block-inheritLocale">
                <td class="label">{$form.inheritLocale.label} {help id='inheritLocale' title=$form.inheritLocale.label}</td>
                <td>{$form.inheritLocale.html}</td>
            </tr>
          <tr class="crm-localization-form-contact_default_language">
            <td class="label">{$form.contact_default_language.label}</td>
            <td>{$form.contact_default_language.html}<br />
              <span class="description">{ts}Default language (if any) for contact records.{/ts}</span></td>
          </tr>
            <tr class="crm-localization-form-block-defaultCurrency">
                <td class="label">{$form.defaultCurrency.label} {help id='defaultCurrency' title=$form.defaultCurrency.label}</td>
                <td>{$form.defaultCurrency.html}</td>
            </tr>
            <tr class="crm-localization-form-block-monetaryThousandSeparator">
                <td class="label">{$form.monetaryThousandSeparator.label}</td>
                <td>{$form.monetaryThousandSeparator.html}</td>
            </tr>
            <tr class="crm-localization-form-block-monetaryDecimalPoint">
                <td class="label">{$form.monetaryDecimalPoint.label}</td>
                <td>{$form.monetaryDecimalPoint.html}</td>
            </tr>
            <tr class="crm-localization-form-block-currencyLimit">
                <td class="label">{$form.currencyLimit.label}</td>
                <td>{$form.currencyLimit.html}</td>
            </tr>
            <tr class="crm-localization-form-block-moneyformat">
                <td class="label">{$form.moneyformat.label} {help id='moneyformat' title=$form.moneyformat.label}</td>
                <td>{$form.moneyformat.html}</td>
            </tr>
            <tr class="crm-localization-form-block-moneyvalueformat">
                <td class="label">{$form.moneyvalueformat.label} {help id='moneyvalueformat' title=$form.moneyvalueformat.label}</td>
                <td>{$form.moneyvalueformat.html}</td>
            </tr>
            <tr class="crm-localization-form-block-customTranslateFunction">
                <td class="label">{$form.customTranslateFunction.label} {help id='customTranslateFunction' title=$form.customTranslateFunction.label}</td>
                <td>{$form.customTranslateFunction.html}</td>
            </tr>
            <tr class="crm-localization-form-block-legacyEncoding">
                <td class="label">{$form.legacyEncoding.label} {help id='legacyEncoding' title=$form.legacyEncoding.label}</td>
                <td>{$form.legacyEncoding.html}</td>
            </tr>
            <tr class="crm-localization-form-block-fieldSeparator">
                <td class="label">{$form.fieldSeparator.label} {help id='fieldSeparator' title=$form.fieldSeparator.label}</td>
                <td>{$form.fieldSeparator.html}</td>
            </tr>
        </table>
    <h3>{ts}Contact Address Fields - Selection Values{/ts}</h3>
        <table class="form-layout-compressed">
            <tr class="crm-localization-form-block-defaultContactCountry">
                <td class="label">{$form.defaultContactCountry.label} {help id='defaultContactCountry' title=$form.defaultContactCountry.label}</td>
                <td>{$form.defaultContactCountry.html}</td>
            </tr>
           <tr class="crm-localization-form-block-defaultContactStateProvince">
                <td class="label">{$form.defaultContactStateProvince.label} {help id='defaultContactCountry' title=$form.defaultContactStateProvince.label}</td>
                <td>{$form.defaultContactStateProvince.html}</td>
            </tr>
            <tr class="crm-localization-form-block-countryLimit">
                <td class="label">{$form.countryLimit.label} {help id='countryLimit' title=$form.countryLimit.label}</td>
                <td>{$form.countryLimit.html}</td>
            </tr>
            <tr class="crm-localization-form-block-provinceLimit">
                <td class="label">{$form.provinceLimit.label} {help id='provinceLimit' title=$form.provinceLimit.label}</td>
                <td>{$form.provinceLimit.html}</td>
            </tr>
        </table>
    <h3>{ts}Multiple Languages Support{/ts}</h3>
      <table class="form-layout-compressed">
        {if $form.languageLimit}
          <tr class="crm-localization-form-block-makeSinglelingual_description">
              <td></td>
              <td><span class="description">{ts 1="http://documentation.civicrm.org"}This is a multilingual installation. It contains certain schema differences compared to regular installations of CiviCRM. Please <a href="%1">refer to the documentation</a> for details.{/ts}</span></td>
          </tr>
          <tr class="crm-localization-form-block-makeSinglelingual">
              <td class="label">{$form.makeSinglelingual.label}</td>
              <td>{$form.makeSinglelingual.html}<br />
              <span class="description">{ts}Check this box and click 'Save' to switch this installation from multi- to single-language.{/ts}</span><br /><br />
              <span class="description" style="color:red">{$warning}</span></td>
          </tr>
        {elseif $form.makeMultilingual}
          <tr class="crm-localization-form-block-makeMultilingual">
              <td class="label">{$form.makeMultilingual.label}</td>
              <td>{$form.makeMultilingual.html}<br />
              <span class="description">{ts}Check this box and click 'Save' to switch this installation from single- to multi-language, then add further languages.{/ts}</span><br /><br />
              <span class="description" style="color:red">{$warning}</span></td>
        {else}
          <tr class="crm-localization-form-block-description">
              <td>
              <span class="description">{ts}In order to use this functionality, the installation's database user must have privileges to create triggers (in MySQL 5.0 – and in MySQL 5.1 if binary logging is enabled – this means the SUPER privilege). This install either does not seem to have the required privilege enabled.{/ts} {ts}(Multilingual support currently cannot be enabled on installations with enabled logging.){/ts}</span><br /><br />
              <span class="description" style="color:red">{$warning}</span></td>
          </tr>
        {/if}
      </table>
    <div class="crm-submit-buttons">
        {include file="CRM/common/formButtons.tpl" location="bottom"}
    </div>
<div class="spacer"></div>
</div>
