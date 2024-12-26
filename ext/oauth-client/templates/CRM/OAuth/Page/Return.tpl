{if $error}
    <details class="crm-accordion-bold" open>
        <summary>
            {ts}OAuth Error Details{/ts}
        </summary>
        <div class="crm-accordion-body">
            <ul>
                <li><strong>{ts}Error type:{/ts}</strong> {$error.error|escape:'html'}</li>
                <li><strong>{ts}Error description:{/ts}</strong>
                    <pre>{$error.error_description|escape:'html'}</pre>
                </li>
                <li><strong>{ts}Error URI:{/ts}</strong> <code>{$error.error_uri|escape:'html'}</code></li>
            </ul>
        </div>
    </details>
{else}
    <p>{ts}An OAuth token was created!{/ts}</p>
    <p>{ts}There is no clear "next step", so this may be a new integration. Please update the integration to define a next step via "hook_civicrm_oauthReturn" or "landingUrl".{/ts}</p>
{/if}

{if $stateJson}
    <details class="crm-accordion-bold">
        <summary>
            {ts}OAuth State{/ts}
        </summary>
        <div class="crm-accordion-body">
            <pre>{$stateJson}</pre>
        </div>
    </details>
{/if}

{if $tokenJson}
    <details class="crm-accordion-bold">
        <summary>
            {ts}OAuth Token{/ts}
        </summary>
        <div class="crm-accordion-body">
            <pre>{$tokenJson}</pre>
        </div>
    </details>
{/if}
