{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
<div class="crm-block crm-form-block crm-path-form-block">
<div id="help">
    {ts}Default values will be supplied for these upload directories the first time you access CiviCRM - based on the CIVICRM_TEMPLATE_COMPILEDIR specified in civicrm.settings.php. If you need to modify the defaults, make sure that your web server has write access to the directories.{/ts}
</div>
 <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
           <table class="form-layout">
            <tr class="crm-path-form-block-uploadDir">
                <td class="label">{$form.uploadDir.label}</td>
                <td>{$form.uploadDir.html|crmAddClass:'huge40'}<br />
                    <span class="description">{ts}File system path where temporary CiviCRM files - such as import data files - are uploaded.{/ts}</span>
                </td>
            </tr>
            <tr class="crm-path-form-block-imageUploadDir">
                <td class="label">{$form.imageUploadDir.label}</td>
                <td>{$form.imageUploadDir.html|crmAddClass:'huge40'}<br />
                    <span class="description">{ts}File system path where image files are uploaded. Currently, this path is used for images associated with premiums (CiviContribute thank-you gifts).{/ts}</span>
                </td>    
            </tr>
            <tr class="crm-path-form-block-customFileUploadDir">  
                <td class="label">{$form.customFileUploadDir.label}</td>
                <td>{$form.customFileUploadDir.html|crmAddClass:'huge40'}<br />
                    <span class="description">{ts}Path where documents and images which are attachments to contact records are stored (e.g. contact photos, resumes, contracts, etc.). These attachments are defined using 'file' type custom fields.{/ts}</span>
                </td>
            </tr>
            <tr class="crm-path-form-block-customTemplateDir">  
                <td class="label">{$form.customTemplateDir.label}</td>
                <td>{$form.customTemplateDir.html|crmAddClass:'huge40'}<br />
                    <span class="description">{ts}Path where site specific templates are stored if any. This directory is searched first if set. Custom JavaScript code can be added to templates by creating files named <em>templateFile.extra.tpl</em>.{/ts} {docURL page="developer/techniques/templates"}</span><br />
                    <span class="description">{ts}CiviCase configuration files can also be stored in this custom path.{/ts} {docURL page="user/case-management/setup"}</span>
                </td>
            </tr>
            <tr class="crm-path-form-block-customPHPPathDir">  
                <td class="label">{$form.customPHPPathDir.label}</td>
                <td>{$form.customPHPPathDir.html|crmAddClass:'huge40'}<br />
                    <span class="description">{ts}Path where site specific PHP code files are stored if any. This directory is searched first if set.{/ts}</span>
                </td>    
            </tr>
            <tr class="crm-path-form-block-extensionsDir">  
                <td class="label">{$form.extensionsDir.label}</td>
                <td>{$form.extensionsDir.html|crmAddClass:'huge40'}<br />
                    <span class="description">{ts}Path where CiviCRM extensions are stored.{/ts}</span>
                </td>    
            </tr>
        </table>
   <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
