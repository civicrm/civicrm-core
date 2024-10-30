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
      {ts}You may configure these upload directories using absolute paths or path variables.{/ts}
      {help id='id-path_vars'}
    </p>
    <p>
      {ts}If you modify the defaults, make sure that your web server has write access to the directories.{/ts}
    </p>
</div>
<div class="crm-block crm-form-block crm-path-form-block">
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
                    <span class="description">{ts}Path where site specific templates are stored if any. This directory is searched first if set. Custom JavaScript code can be added to templates by creating files named <em>templateFile.extra.tpl</em>.{/ts} {docURL page="sysadmin/setup/directories"}</span><br />
                    <span class="description">{ts}CiviCase configuration files can also be stored in this custom path.{/ts} {docURL page="user/case-management/set-up"}</span>
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
             <tr class="crm-path-form-block-ext_max_depth">
               <td class="label">{$form.ext_max_depth.label}</td>
               <td>{$form.ext_max_depth.html}<br />
                 <span class="description">{ts}When searching for extensions, limit the number of subdirectories.{/ts}</span>
               </td>
             </tr>
        </table>
   <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
