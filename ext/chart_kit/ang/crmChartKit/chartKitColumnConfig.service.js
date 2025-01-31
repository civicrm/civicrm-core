(function (angular, $, _) {
    "use strict";

    // Provides common "option group" info for chart admin components
    angular.module('crmChartKit').factory('chartKitColumnConfig', () => {
        const ts = CRM.ts('chart_kit');

        return {
            scaleType: [
                {
                    key: 'numeric',
                    label: ts('Numeric'),
                },
                {
                    key: 'categorical',
                    label: ts('Categorical'),
                },
                {
                    key: 'date',
                    label: ts('Datetime'),
                },
            ],
            datePrecision: [
                {
                    key: 'year',
                    label: ts('Year'),
                },
                {
                    key: 'month',
                    label: ts('Month'),
                },
                {
                    key: 'week',
                    label: ts('Week'),
                },
                {
                    key: 'day',
                    label: ts('Day'),
                },
                {
                    key: 'hour',
                    label: ts('Hour'),
                },
            ],
            seriesType: [
                {
                    key: 'bar',
                    label: ts('Bar')
                },
                {
                    key: 'line',
                    label: ts('Line')
                },
                {
                    key: 'area',
                    label: ts('Area')
                },
            ],
            dataLabelType: [
                {
                    key: "none",
                    label: "None",
                },
                {
                    key: "title",
                    label: "On hover",
                },
                {
                    key: "label",
                    label: "Always show",
                }
            ],
            dataLabelFormatter: [
                {
                    key: "none",
                    label: "None",
                },
                {
                    key: "round",
                    label: "Round",
                },
                // TODO: custom date formatting
                //        {
                //            key: "formatDate",
                //            label: "Date format",
                //        },
                {
                    key: "formatMoney",
                    label: "Money formatter",
                }
            ],

        };
    });
})(angular, CRM.$, CRM._);
