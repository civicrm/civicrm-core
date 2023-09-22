<table>
  <tbody>
    <tr class="columnheader">
      <th>{ts}Participant{/ts}</th>
      <th>{ts}Role{/ts}</th>
      <th>{ts}Fee{/ts}</th>
    </tr>
    {foreach from=$associatedParticipants item="participant"}
      <tr>
        <td><a href='{$participant.participantLink}'>{$participant.participantName|escape}</a></td></td>
        <td>{$participant.role|escape}</td>
        <td>{$participant.fee|escape}</td>
      </tr>
    {/foreach}
  </tbody>
</table>
