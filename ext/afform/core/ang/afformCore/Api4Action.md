# aff-api4-action

This directive is designed for invoking an action via APIv4. Much like
`ng-click`, one would use `api4-action` to add behavior to a button or link.

```html
<button
  aff-api4-action="['Contact','delete',{where:['id','=','100]}}]"
  >Delete</button>
```

### Options

Additional options may be used to manipulate the status messages and to
trigger further actions on failure or success.

```html
<button
  aff-api4-action="['Contact','delete',{where:['id','=','100]}}]"
  msg-start="ts('Deleting...')"
  msg-success="ts('Deleted')"
  on-success="crmUiAlert({text:'Received ' + response.length + ' items'})"
  on-error="crmUiAlert({text:'Failure: ' + error})"
>Delete</button>
<!-- Automated flag with aff-api4-action-{running -->
```

### Styling

The `aff-api4-action` element will have the follow classes
toggled automatically:

* `aff-api4-action-running`: User has clicked to fire the action, and action is still running.
* `aff-api4-action-idle`: The action is not running.
