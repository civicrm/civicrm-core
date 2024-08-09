# Search Kit Charts

Visualise data from search kit.

Work in progress... patches welcome :)

Aiming for stability / backwards compatibility from v0.8 onwards - but if your chart breaks, try deleting and recreating it.

## Pre-release changelog

v0.1 - initial release - dc bundle + pie/bar/row/line charts

v0.2 - standardise all the naming to "ChartKit" (*requires reinstall!*); fix for dynamic filtering on afforms (thanks @mattwire :)); fix row ordering on row chart

v0.3 - allow multiple columns per axis; alternative count/list "reducer" options for column values

v0.4 - stacked and grouped bar charts; rerender as you change the settings; remove column selections; lots of other minor cleanup

v0.5 - multi-line charts; consistent color assignment for stacked/grouped bar charts; support data labels for grouped bar charts

v0.6 - add more reducers (percentage count / percentage total); configure label type per column; settings and formatting cleanup; chart download buttons

v0.7 - refactors bar and line charts so they are interchangeable and composable; adds a bunch more format settings - axes labels and gridlines, value label formatting, legend position; fixes ordering of categorical line charts

v0.8 - refactor to use a single display type + component for all chart types. allows user to swap between all different chart types dynamically, and simplifies adding new chart types. *will break existing displays - they will need to be recreated using the new chart type*

v0.9 - implement date rounding for date columns - allows maker neater charts with nice year/month/day labels without using server side group by; adds configurable right y axis label

## Contributing

### Design overview

The visualisations are rendered using dc/d3.js library. DC reference is here: https://dc-js.github.io/dc.js/docs/html/index.html

The `crmChartKit` module provides two components:
- `crmChartKit/crmSearchDisplayChartKit.component.js` => the Search Display component that renders a display
- `crmChartKit/searchAdminDisplayChartKit.component.js` => admin component for setting up the display in SearchKit

Different chart types are implemented as angular services, which provide some functions for admin and rendering:

E.g.
- `crmChartKit/chartTypes/chartKitPie.service.js` => pie chart - simple example
- `crmChartKit/chartTypes/chartKitStack.service.js` => line / bar charts - a bit more complicated

A lot of the functionality in existing charts is generic to all the chart types. The only things a chart type service *needs* to provide are:
- a `getAxes` method to provide the axes configuration for selecting columns in the admin
- either a `getChartConstructor` method to tell the render which DC chart type to use, or a `buildChart` method to provide more customised rendering

The various chart types show other places the chart type can hook in.

### Add a setting

Add setting `getInitialSettings` or `getInitialFormatSettings` - either in the base admin component or a specific chart type.

Add a form element for the setting with  `ng-model="$ctrl.display.settings.[your_setting_prop]` - either in one of the `ang/crmChartKit/ChartKitAdmin....html` partials for all charts, or the `ang/crmChartKit/chartTypes/chartKit[TYPE]Admin.html` for a specific chart.

Edit the render logic in `ang/crmChartKit/crmSearchDisplayChartKit.component.js` or `ang/crmChartKit/chartTypes/chartKit[TYPE].service.js` to use your setting.

### To implement a new chart type

To add a new chart type, add an additional service and admin template in `ang/crmChartKit/chartTypes` and then add to the list of available chart types in `ang/crmChartKit/chartKitTypes.service.js`

TODO: implementing a new chart type in a separate extension. Need to add some kind of hook to gather chart types from elsewhere in `ang/crmChartKit/chartKitTypes.service.js`...
