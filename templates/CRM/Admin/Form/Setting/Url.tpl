{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
<div class="crm-block crm-form-block crm-url-form-block">
<div id="help">
    {ts}These settings define the URLs used to access CiviCRM resources (CSS files, Javascript files, images, etc.). Default values will be inserted the first time you access CiviCRM - based on the CIVICRM_UF_BASEURL specified in your installation's settings file (civicrm.settings.php).{/ts}
</div>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
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
        </td>
    </tr>
    <tr class="crm-url-form-block-verifySSL">
        <td class="label">
            {$form.verifySSL.label} {help id='id-verify_ssl'}
        </td>
        <td>
            {$form.verifySSL.html}<br/>
            <p class="description font-red">{ts}{$verifySSL_description}{/ts}</p>
        </td>
    </tr>
    <tr class="crm-miscellaneous-form-block-cvv-backoffice-required">
          <td class="label">{$form.cvv_backoffice_required.label}</td>
          <td>
            {$form.cvv_backoffice_required.html}<br />
            <p class="description">{ts}{$cvv_backoffice_required_description}{/ts}</p>
          </td>
        </tr>
</table>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
