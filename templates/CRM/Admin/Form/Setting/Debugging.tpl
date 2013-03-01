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
<div class="crm-block crm-form-block crm-debugging-form-block">
<div id="help">
    {ts}In addition to the settings on this screen, there are a number of settings you can add to your sites's settings file (civicrm.settings.php) to provide additional debugging information.{/ts} {docURL page="developer/development-environment/debugging"}
</div>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
         <table class="form-layout">
            <tr class="crm-debugging-form-block-debug">
                <td class="label">{$form.debug.label}</td>
                <td>{$form.debug.html}<br />
                <span class="description">{ts}Set this value to <strong>Yes</strong> if you want to use one of CiviCRM's debugging tools. <strong>This feature should NOT be enabled for production sites</strong>{/ts} {help id='debug'}</span></td>
            </tr>
            {if $form.userFrameworkLogging}
            <tr class="crm-debugging-form-block-userFrameworkLogging">
                <td class="label">{$form.userFrameworkLogging.label}</td>
                <td>{$form.userFrameworkLogging.html}<br />
                <span class="description">{ts}Set this value to <strong>Yes</strong> if you want CiviCRM error/debugging messages to also appear in Drupal error logs{/ts} {help id='userFrameworkLogging'}</span></td>
            </tr>
            {/if}
            <tr class="crm-debugging-form-block-backtrace">
                <td class="label">{$form.backtrace.label}</td>
                <td>{$form.backtrace.html}<br />
                <span class="description">{ts}Set this value to <strong>Yes</strong> if you want to display a backtrace listing when a fatal error is encountered. <strong>This feature should NOT be enabled for production sites</strong>{/ts}</span></td>
            </tr>
            <tr class="crm-debugging-form-block-fatalErrorTemplate">
                <td class="label">{$form.fatalErrorTemplate.label}</td>
                <td>{$form.fatalErrorTemplate.html}<br />
                <span class="description">{ts}Enter the path and filename for a custom Smarty template if you want to define your own screen for displaying fatal errors.{/ts}</span></td>
            </tr>
            <tr class="crm-debugging-form-block-fatalErrorHandler">
                <td class="label">{$form.fatalErrorHandler.label}</td>
                <td>{$form.fatalErrorHandler.html}<br />
                <span class="description">{ts}Enter the path and class for a custom PHP error-handling function if you want to override built-in CiviCRM error handling for your site.{/ts}</span></td>
            </tr>
        </table>
        <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
<div class="spacer"></div>
</div>
