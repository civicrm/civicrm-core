{if $error}
    <div class="crm-accordion-wrapper">
        <div class="crm-accordion-header">
            {ts}OAuth Error Details{/ts}
        </div>
        <div class="crm-accordion-body">
            <ul>
                <li><strong>{ts}Error type:{/ts}</strong> {$error.error|escape:'html'}</li>
                <li><strong>{ts}Error description:{/ts}</strong>
                    <pre>{$error.error_description|escape:'html'}</pre>
                </li>
                <li><strong>{ts}Error URI:{/ts}</strong> <code>{$error.error_uri|escape:'html'}</code></li>
            </ul>
        </div>
    </div>
{else}
    <p>{ts}An OAuth token was created!{/ts}</p>
    <p>{ts}There is no clear "next step", so this may be a new integration. Please update the integration to define a next step via "hook_civicrm_oauthReturn" or "landingUrl".{/ts}</p>
{/if}

{if $stateJson}
    <div class="crm-accordion-wrapper collapsed">
        <div class="crm-accordion-header">
            {ts}OAuth State{/ts}
        </div>
        <div class="crm-accordion-body">
            <pre>{$stateJson}</pre>
        </div>
    </div>
{/if}

{if $tokenJson}
    <div class="crm-accordion-wrapper collapsed">
        <div class="crm-accordion-header">
            {ts}OAuth Token{/ts}
        </div>
        <div class="crm-accordion-body">
            <pre>{$tokenJson}</pre>
        </div>
    </div>
{/if}
