The `crmDialog` is a helper for working with the `dialogService`.  In particular, it allows you to define then
dialog widgets as part of the markup.

To open a dialog:

```js
dialogService.open('someDialog', '~/Path/To/Some.html', model, options)
  .then(function(result){
    console.log('received output from dialog', result);
  });
```

```html
<!-- FILE: Path/To/Some.html -->
<div id="bootstrap-theme" crm-dialog="someDialog">
  <form name="someForm">
    
    <input type="text" ng-model="model.foo"/>

    <crm-dialog-button text="ts('Create')" icons="{primary: 'fa-plus'}" on-click="someDialog.close(model)" disabled="!someForm.$valid" />
    <crm-dialog-button text="ts('Cancel')" icons="{primary: 'fa-times'}" on-click="someDialog.cancel()" />
  </form>
</div>
```
