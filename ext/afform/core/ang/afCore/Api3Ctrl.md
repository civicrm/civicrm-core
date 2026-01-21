# af-api3-ctrl

**This directive is deprecated.**

This directive is designed for querying and displaying data from APIv3. Each API call is represented with an object like this:

```js
{
  entity: 'Contact',
  action: 'get',
  params: {display_name: 'alice'},
  result: {
    is_error: 0,
    count: 10,
    values: {...}
  },
  refresh: function(){...}
}
```

You can initialize this object using `<af-api3-ctrl>` and `<af-api3>`, as in:

```html
<div
  af-api3-ctrl="apiData"
  af-api3="['Contact','get', {display_name: ''}]">

    <div crm-ui-debug="apiData"></div>

    <div>
      Filter by name: <input ng-model="apiData.params.display_name" />
    </div>

    <ul>
      <li ng-repeat="value in apiData.result.values">{{value.display_name}}</li>
    </ul>
</div>
```

By default, the API call will refresh its results automatically - as soon as the filter parameters change.

If you'd rather wait and trigger the refresh another way, then set the `af-api3-refresh` policy to `init` or `manual`. Don't forget to fire the refresh some other way, such as `ng-click`:

```html
<div
  af-api3-ctrl="apiData"
  af-api3="['Contact','get', {display_name: ''}]"
  af-api3-refresh="init">

    <div crm-ui-debug="apiData"></div>

    <div>
      Filter by name: <input ng-model="apiData.params.display_name" />
      <button ng-click="apiData.refresh()">Search</button>
    </div>

    <ul>
      <li ng-repeat="value in apiData.result.values">{{value.display_name}}</li>
    </ul>

</div>
```

And you conditionally execute some logic whenever a refresh occurs -- use
`on-refresh`. Within the scope of this statement, you have access to the
properties of the API object (`entity`, `action`, `params`, `result`).

```html
<div
  af-api3-ctrl="apiData"
  af-api3="['Contact','get', {display_name: ''}]"
  on-refresh="doSomething(result)">
  ...
</div>
```
