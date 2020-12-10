# af-api4-action

This directive is designed for invoking an action via APIv4. Much like
`ng-click`, one would use `api4-action` to add behavior to a button or link.

```html
<button
  af-api4-action="['Contact','delete',{where:['id','=','100]}}]"
  >Delete</button>
```

### Options

Additional options may be used to manipulate the status messages and to
trigger further actions on failure or success.

```html
<button
  af-api4-action="['Contact','delete',{where:['id','=','100]}}]"
  af-api4-start-msg="ts('Deleting...')"
  af-api4-success-msg="ts('Deleted')"
  af-api4-success="crmUiAlert({text:'Received ' + response.length + ' items'})"
  af-api4-error="crmUiAlert({text:'Failure: ' + error})"
>Delete</button>
<!-- Automated flag with af-api4-action-{running -->
```

### Styling

The `af-api4-action` element will have the follow classes
toggled automatically:

* `af-api4-action-running`: User has clicked to fire the action, and action is still running.
* `af-api4-action-idle`: The action is not running.
