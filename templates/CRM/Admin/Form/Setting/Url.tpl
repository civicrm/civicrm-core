{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="help">
  <p>
    {ts}These settings define the URLs used to access CiviCRM resources (CSS files, Javascript files, images, etc.).{/ts}
  </p>
  <p>
    {ts}You may configure these settings using absolute URLs or URL variables.{/ts}
    {help id='id-url_vars'}
  </p>
</div>
<div class="crm-block crm-form-block crm-url-form-block">
<table class="form-layout">
    <tr class="crm-url-form-block-userFrameworkResourceURL">
        <td class="label">
            {$form.userFrameworkResourceURL.label} {help id='id-resource_url'}
        </td>
        <td>
            {$form.userFrameworkResourceURL.html|crmAddClass:'huge40'}
        </td>
    </tr>
    <tr class="crm-url-form-block-imageUploadURL">
        <td class="label">
            {$form.imageUploadURL.label} {help id='id-image_url'}
        </td>
        <td>
            {$form.imageUploadURL.html|crmAddClass:'huge40'}
        </td>
    </tr>
    <tr class="crm-url-form-block-customCSSURL">
        <td class="label">
            {$form.customCSSURL.label} {help id='id-css_url'}
        </td>
        <td>
            {$form.customCSSURL.html|crmAddClass:'huge40'}
        </td>
    </tr>
    <tr class="crm-url-form-block-disable_core_css">
        <td class="label">
            {$form.disable_core_css.label} {help id='id-css_url'}
        </td>
        <td>
            {$form.disable_core_css.html}<br />
            <p class="description">{ts}{$disable_core_css_description}{/ts}</p>
        </td>
    </tr>
    <tr class="crm-url-form-block-extensionsURL">
        <td class="label">
            {$form.extensionsURL.label} {help id='id-extensions_url'}
        </td>
        <td>
            {$form.extensionsURL.html|crmAddClass:'huge40'}
        </td>
    </tr>
    <tr class="crm-url-form-block-enableSSL">
        <td class="label">
            {$form.enableSSL.label} {help id='id-enable_ssl'}
        </td>
        <td>
            {$form.enableSSL.html}
            <p class="description font-red">{ts}{$settings_fields.enableSSL.description}{/ts}</p>
        </td>
    </tr>
    <tr class="crm-url-form-block-verifySSL">
        <td class="label">
            {$form.verifySSL.label} {help id='id-verify_ssl'}
        </td>
        <td>
            {$form.verifySSL.html}<br/>
            <p class="description font-red">{ts}{$settings_fields.verifySSL.description}{/ts}</p>
        </td>
    </tr>
    <tr class="crm-url-form-block-defaultExternUrl">
        <td class="label">
            {$form.defaultExternUrl.label} {help id='id-defaultExternUrl'}
        </td>
        <td>
            {$form.defaultExternUrl.html}<br/>
            <p class="description font-red">{ts}{$settings_fields.defaultExternUrl.description}{/ts}</p>
        </td>
    </tr>
</table>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
