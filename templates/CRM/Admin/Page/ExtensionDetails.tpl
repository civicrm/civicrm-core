<table class="crm-info-panel">
    {if $extension.urls}
        {foreach from=$extension.urls key=label item=url}
            <tr><td class="label">{$label|escape}</td><td><a href="{$url|escape}">{$url|escape}</a></td></tr>
        {/foreach}
    {/if}
    <tr>
        <td class="label">{ts}Author{/ts}</td>
        <td>
          {foreach from=$extension.authors item=author}
            {capture assign=authorDetails}
              {if !empty($author.role)}{$author.role|escape};{/if}
              {if !empty($author.email)}<a href="mailto:{$author.email|escape}">{$author.email|escape}</a>;{/if}
              {if !empty($author.homepage)}<a href="{$author.homepage|escape}">{$author.homepage|escape}</a>;{/if}
            {/capture}
            {$author.name|escape} {if $authorDetails}({$authorDetails|trim:'; '}){/if}<br/>
          {/foreach}
        </td>
    </tr>
    {if $extension.comments}
    <tr>
      <td class="label">{ts}Comments{/ts}</td><td>{$extension.comments|escape}</td>
    </tr>
    {/if}
    <tr>
      <td class="label">{ts}Version{/ts}</td><td>{$extension.version|escape}</td>
    </tr>
    <tr>
      <td class="label">{ts}Released on{/ts}</td><td>{$extension.releaseDate|escape}</td>
    </tr>
    <tr>
      <td class="label">{ts}License{/ts}</td><td>{$extension.license|escape}</td>
    </tr>
    {if $extension.develStage}
    <tr>
      <td class="label">{ts}Development stage{/ts}</td><td>{$extension.develStage|escape}</td>
    </tr>
    {/if}
    <tr>
        <td class="label">{ts}Requires{/ts}</td>
        <td>
            {foreach from=$extension.requires item=ext}
                {if array_key_exists($ext, $localExtensionRows)}
                    {$localExtensionRows.$ext.label|escape} (already downloaded)
                {elseif array_key_exists($ext, $remoteExtensionRows)}
                    {$remoteExtensionRows.$ext.label|escape} (not downloaded)
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
            {if $extension.compatibility}
                {foreach from=$extension.compatibility.ver item=ver}
                    {$ver|escape} &nbsp;
                {/foreach}
            {/if}
        </td>
    </tr>
    <tr>
      <td class="label">{ts}Local path{/ts}</td><td>{if !empty($extension.path)}{$extension.path|escape}{/if}</td>
    </tr>
    {if $extension.downloadUrl}
    <tr>
      <td class="label">{ts}Download location{/ts}</td><td>{$extension.downloadUrl|escape}</td>
    </tr>
    {/if}
</table>
