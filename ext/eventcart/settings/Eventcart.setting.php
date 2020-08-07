<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Settings metadata file
 */
return [
  'enable_cart' => [
    'name' => 'enable_cart',
    'group_name' => 'Event Cart Preferences',
    'settings_pages' => ['eventcart' => ['weight' => 10]],
    'group' => 'eventcart',
    'type' => 'Boolean',
    'html_type' => 'checkbox',
    'default' => '0',
    'add' => '4.1',
    'title' => ts('Use Shopping Cart Style Event Registration'),
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => ts('This feature allows users to register for more than one event at a time. When enabled, users will add event(s) to a "cart" and then pay for them all at once. Enabling this setting will affect online registration for all active events. The code is an alpha state, and you will potentially need to have developer resources to debug and fix sections of the codebase while testing and deploying it'),
    'help_text' => '',
    'documentation_link' => ['page' => 'CiviEvent Cart Checkout', 'resource' => 'wiki'],
  ],
  'eventcart_payment_processors' => [
    'name' => 'eventcart_payment_processors',
    'group_name' => 'Event Cart Preferences',
    'group' => 'eventcart',
    'type' => 'Array',
    'add' => '5.28',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => ts('Payment processors that will be available for the checkout'),
    'help_text' => '',
    'title' => ts('Payment processors for event cart checkout'),
    'html_type' => 'select',
    'default' => [],
    'html_attributes' => [
      'class' => 'crm-select2',
      'multiple' => TRUE,
    ],
    'pseudoconstant' => [
      'callback' => 'CRM_Event_Cart_BAO_Cart::getPaymentProcessors',
    ],
    'settings_pages' => ['eventcart' => ['weight' => 15]],
  ],
  'eventcart_paylater' => [
    'name' => 'eventcart_paylater',
    'group_name' => 'Event Cart Preferences',
    'settings_pages' => ['eventcart' => ['weight' => 20]],
    'group' => 'eventcart',
    'type' => 'Boolean',
    'html_type' => 'checkbox',
    'default' => '0',
    'add' => '5.28',
    'title' => ts('Allow pay later for event cart'),
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => ts('Enable the pay later option for the event cart checkout'),
    'help_text' => '',
  ],
  'eventcart_paylater_text' => [
    'name' => 'eventcart_paylater_text',
    'group_name' => 'Event Cart Preferences',
    'settings_pages' => ['eventcart' => ['weight' => 25]],
    'group' => 'eventcart',
    'type' => 'Text',
    'html_type' => 'text',
    'default' => 'Pay later',
    'add' => '5.28',
    'title' => ts('The text to display for pay later option'),
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => ts('This is the text that will be displayed on the payment processor selector'),
    'help_text' => '',
  ],
];
