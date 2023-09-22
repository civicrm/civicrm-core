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
    {ts}In addition to the settings on this screen, there are a number of settings you can add to your sites's settings file (civicrm.settings.php) to provide additional debugging information.{/ts} {docURL page="dev/tools/debugging/#changing-file-based-settings"}
</div>
<div class="crm-block crm-form-block crm-debugging-form-block">
    <table class="form-layout">
        {if !empty($form.userFrameworkLogging)}
            <tr class="crm-debugging-form-block-userFrameworkLogging">
                <td class="label">{$form.userFrameworkLogging.label}</td>
                <td>{$form.userFrameworkLogging.html}<br />
                <span class="description">{ts}Set this value to <strong>Yes</strong> if you want CiviCRM error/debugging messages to appear in the Drupal error logs{/ts} {help id='userFrameworkLogging'}</span></td>
            </tr>
        {/if}
            <tr class="crm-debugging-form-block-debug">
                <td class="label">{$form.debug_enabled.label}</td>
                <td>{$form.debug_enabled.html}<br />
                <span class="description">{ts}<strong>This feature should NOT be enabled for production sites.</strong><br />Set this value to <strong>Yes</strong> if you want to use one of CiviCRM's debugging tools.{/ts} {help id='debug'}</span></td>
            </tr>
            <tr class="crm-debugging-form-block-backtrace">
                <td class="label">{$form.backtrace.label}</td>
                <td>{$form.backtrace.html}<br />
                <span class="description">{ts}<strong>This feature should NOT be enabled for production sites.</strong><br />Set this value to <strong>Yes</strong> if you want to display a backtrace listing when a fatal error is encountered.{/ts}</span></td>
            </tr>
            <tr class="crm-debugging-form-block-environment">
                <td class="label">{$form.environment.label}</td>
                <td>{$form.environment.html}<br />
                <span class="description">{ts}Set this value to <strong>Staging/Development</strong> to prevent cron jobs & mailings from being executed.{/ts}</span></td>
            </tr>
            <tr class="crm-debugging-form-block-fatalErrorHandler">
                <td class="label">{$form.fatalErrorHandler.label}</td>
                <td>{$form.fatalErrorHandler.html}<br />
                <span class="description">{ts}Enter the path and class for a custom PHP error-handling function if you want to override built-in CiviCRM error handling for your site.{/ts}</span></td>
            </tr>
            <tr class="crm-debugging-form-block-assetCache">
                <td class="label">{$form.assetCache.label}</td>
                <td>{$form.assetCache.html}<br />
                <span class="description">{ts}Store computed JS/CSS content in cache files? (Note: In "Auto" mode, the "Debug" setting will determine whether to activate the cache.){/ts}</span></td>
            </tr>
            <tr class="crm-debugging-form-block-esm_loader">
                <td class="label">{$form.esm_loader.label}</td>
                <td>{$form.esm_loader.html}<br />
                <span class="description">{$settings_fields.esm_loader.description}</span></td>
            </tr>
    </table>
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
    <div class="spacer"></div>
</div>
