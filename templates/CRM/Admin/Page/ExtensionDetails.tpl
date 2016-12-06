<table class="crm-info-panel">
    {if $extension.name}
    <tr>
        <td class="label">{ts}Name (key){/ts}</td><td>{$extension.name} ({$extension.key})</td>
    </tr>
    {/if}
    <tr>
        <td class="label">{ts}Description{/ts}</td><td>{$extension.description}</td>
    </tr>
    <tr>
        <td class="label">{ts}Download location{/ts}</td><td>{$extension.downloadUrl}</td>
    </tr>
    <tr>
        <td class="label">{ts}Local path{/ts}</td><td>{$extension.path}</td>
    </tr>
        {foreach from=$extension.urls key=label item=url}
            <tr><td class="label">{$label}</td><td><a href="{$url}">{$url}</a></td></tr>
        {/foreach}
    <tr>
        <td class="label">{ts}Author{/ts}</td><td>{$extension.maintainer.author} (<a href="mailto:{$extension.maintainer.email}">{$extension.maintainer.email}</a>)</td>
    </tr>
    <tr>
        <td class="label">{ts}Version{/ts}</td><td>{$extension.version}</td>
    </tr>
    <tr>
        <td class="label">{ts}Released on{/ts}</td><td>{$extension.releaseDate}</td>
    </tr>
    <tr>
        <td class="label">{ts}License{/ts}</td><td>{$extension.license}</td>
    </tr>
    <tr>
        <td class="label">{ts}Development stage{/ts}</td><td>{$extension.develStage}</td>
    </tr>
    <tr>
        <td class="label">{ts}Requires{/ts}</td>
        <td>
            {foreach from=$extension.requires item=ext}
                {if array_key_exists($ext, $localExtensionRows)}
                    {$localExtensionRows.$ext.name} (already downloaded - {$ext})
                {elseif array_key_exists($ext, $remoteExtensionRows)}
                    {$remoteExtensionRows.$ext.name} (not downloaded - {$ext})
                {else}
                    {$ext} {ts}(not available){/ts}
                {/if}
                <br/>
            {/foreach}
        </td>
    </tr>
    <tr>
        <td class="label">{ts}Compatible with{/ts}</td>
        <td>
            {foreach from=$extension.compatibility.ver item=ver}
                {$ver} &nbsp;
            {/foreach}
        </td>
    </tr>
    <tr>
        <td class="label">{ts}Comments{/ts}</td><td>{$extension.comments}</td>
    </tr>
</table>