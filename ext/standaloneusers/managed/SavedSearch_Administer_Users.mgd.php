<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  [
    "SavedSearch",
    "save",
    {
      "records": [
        {
          "name": "Users",
          "label": "Users",
          "form_values": null,
          "mapping_id": null,
          "search_custom_id": null,
          "api_entity": "User",
          "api_params": {
            "version": 4,
            "select": [
              "id",
              "username",
              "email",
              "is_active",
              "when_created",
              "when_last_accessed"
            ],
            "orderBy": [],
            "where": [],
            "groupBy": [],
            "join": [],
            "having": []
          },
          "expires_date": null,
          "description": null
        }
      ],
      "match": [
        "name"
      ]
    }
  ],
  [
    "SearchDisplay",
    "save",
    {
      "records": [
        {
          "name": "Users",
          "label": "Users",
          "saved_search_id.name": "Users",
          "type": "table",
          "settings": {
            "description": null,
            "sort": [],
            "limit": 50,
            "pager": [],
            "placeholder": 5,
            "columns": [
              {
                "type": "field",
                "key": "id",
                "dataType": "Integer",
                "label": "id",
                "sortable": true
              },
              {
                "type": "field",
                "key": "username",
                "dataType": "String",
                "label": "Username",
                "sortable": true
              },
              {
                "type": "field",
                "key": "email",
                "dataType": "String",
                "label": "Email",
                "sortable": true
              },
              {
                "type": "field",
                "key": "is_active",
                "dataType": "Boolean",
                "label": "Active?",
                "sortable": true,
                "editable": true
              },
              {
                "type": "field",
                "key": "when_created",
                "dataType": "Timestamp",
                "label": "When Created",
                "sortable": true
              },
              {
                "type": "field",
                "key": "when_last_accessed",
                "dataType": "Timestamp",
                "label": "When Last Accessed",
                "sortable": true
              }
            ],
            "actions": true,
            "classes": [
              "table",
              "table-striped"
            ]
          },
          "acl_bypass": false
        }
      ],
      "match": [
        "name",
        "saved_search_id"
      ]
    }
  ]
]
