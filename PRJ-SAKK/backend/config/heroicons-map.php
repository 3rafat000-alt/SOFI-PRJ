<?php

// Material Icons → Heroicons v2 Outline name mapping
// Key: Material Icon name (snake_case)
// Value: Heroicon v2 Outline component name (kebab-case)
// All icons render as <x-heroicon-o-{value} />

return [

    // ── Navigation / UI ─────────────────────────────────────
    'add'              => 'plus',
    'close'            => 'x-mark',
    'clear'            => 'x-mark',
    'cancel'           => 'x-mark',
    'check'            => 'check',
    'search'           => 'magnifying-glass',
    'search_off'       => 'magnifying-glass',
    'refresh'          => 'arrow-path',
    'save'             => 'document-arrow-down',
    'filter_list'      => 'funnel',
    'tune'             => 'adjustments-horizontal',
    'settings'         => 'cog-6-tooth',
    'undo'             => 'arrow-uturn-left',
    'sort'             => 'bars-3',

    // ── Arrows / Direction ──────────────────────────────────
    'arrow_back'       => 'arrow-left',
    'arrow_forward'    => 'arrow-right',
    'arrow_upward'     => 'arrow-up',
    'arrow_downward'   => 'arrow-down',
    'chevron_left'     => 'chevron-left',
    'chevron_right'    => 'chevron-right',
    'north_east'       => 'arrow-up-right',
    'south_west'       => 'arrow-down-left',
    'expand_less'      => 'chevron-up',
    'expand_more'      => 'chevron-down',
    'swap_vert'        => 'arrows-up-down',

    // ── Status / Alerts ─────────────────────────────────────
    'info'             => 'information-circle',
    'warning'          => 'exclamation-triangle',
    'error'            => 'exclamation-circle',
    'error_outline'    => 'exclamation-circle',
    'priority_high'    => 'exclamation-circle',
    'check_circle'     => 'check-circle',
    'done_all'         => 'check-badge',
    'pending'          => 'minus-circle',
    'verified'         => 'check-badge',
    'verified_user'    => 'check-badge',

    // ── Actions ─────────────────────────────────────────────
    'edit'             => 'pencil',
    'delete'           => 'trash',
    'delete_forever'   => 'trash',
    'delete_sweep'     => 'trash',
    'visibility'       => 'eye',
    'visibility_off'   => 'eye-slash',
    'preview'          => 'eye',
    'download'         => 'arrow-down-tray',
    'upload_file'      => 'arrow-up-tray',
    'save_alt'         => 'document-arrow-down',
    'send'             => 'paper-airplane',
    'open_in_new'      => 'arrow-top-right-on-square',
    'content_copy'     => 'document-duplicate',
    'link'             => 'link',
    'share'            => 'share',
    'flag'             => 'flag',
    'schedule_send'    => 'paper-airplane',

    // ── Finance / Commerce ──────────────────────────────────
    'account_balance'          => 'building-library',
    'account_balance_wallet'   => 'wallet',
    'attach_money'             => 'currency-dollar',
    'currency_exchange'        => 'arrows-right-left',
    'currency_bitcoin'         => 'currency-dollar',
    'credit_card'              => 'credit-card',
    'credit_card_off'          => 'credit-card',
    'payments'                 => 'banknotes',
    'receipt'                  => 'receipt-percent',
    'receipt_long'             => 'document-text',
    'price_change'             => 'currency-dollar',
    'payments'                 => 'banknotes',
    'toll'                     => 'currency-dollar',
    'savings'                  => 'wallet',
    'monetization_on'          => 'currency-dollar',
    'commission'               => 'receipt-percent',
    'balance'                  => 'scale',
    'calculate'                => 'calculator',
    'percent'                  => 'percent-badge',
    'shopping_cart'            => 'shopping-cart',

    // ── Business / Office ───────────────────────────────────
    'business'                 => 'building-office',
    'apartment'                => 'building-office-2',
    'store'                    => 'building-storefront',
    'storefront'               => 'building-storefront',
    'add_business'             => 'building-storefront',
    'gavel'                    => 'scale',
    'scale'                    => 'scale',
    'work'                     => 'briefcase',
    'dashboard'                => 'squares-2x2',
    'analytics'                => 'chart-bar',
    'insights'                 => 'chart-bar',
    'show_chart'               => 'chart-bar',
    'monitoring'               => 'presentation-chart-line',
    'trending_up'              => 'arrow-trending-up',
    'monitor_heart'            => 'heart',
    'campaign'                 => 'megaphone',

    // ── People / Users ──────────────────────────────────────
    'person'            => 'user',
    'people'            => 'users',
    'group'             => 'users',
    'groups'            => 'users',
    'group_add'         => 'user-plus',
    'group_off'         => 'user-minus',
    'person_off'        => 'user-minus',
    'support_agent'     => 'lifebuoy',
    'contact_support'   => 'lifebuoy',
    'how_to_reg'        => 'identification',

    // ── Communication ───────────────────────────────────────
    'mail'                => 'envelope',
    'drafts'              => 'envelope-open',
    'mark_email_read'     => 'envelope-open',
    'forum'               => 'chat-bubble-left-right',
    'chat'                => 'chat-bubble-left-ellipsis',
    'sms'                 => 'chat-bubble-left-ellipsis',
    'phone'               => 'phone',
    'notifications'       => 'bell',
    'notifications_active'=> 'bell-alert',
    'notifications_off'   => 'bell-slash',
    'contactless'         => 'credit-card',
    'inbox'               => 'inbox',

    // ── Security ────────────────────────────────────────────
    'lock'                => 'lock-closed',
    'no_encryption'       => 'lock-open',
    'security'            => 'shield-check',
    'shield'              => 'shield-check',
    'gpp_bad'             => 'shield-exclamation',
    'verified_user'       => 'shield-check',
    'vpn_key'             => 'key',
    'key_off'             => 'key',
    'admin_panel_settings'=> 'shield-check',
    'block'               => 'no-symbol',
    'do_not_disturb'      => 'no-symbol',

    // ── Devices / Hardware ──────────────────────────────────
    'smartphone'             => 'device-phone-mobile',
    'phone_iphone'           => 'device-phone-mobile',
    'phone_android'          => 'device-phone-mobile',
    'devices'                => 'computer-desktop',
    'computer'               => 'computer-desktop',
    'memory'                 => 'cpu-chip',
    'wifi_tethering'         => 'wifi',
    'power_settings_new'     => 'power',

    // ── Files / Docs ────────────────────────────────────────
    'description'        => 'document-text',
    'note'               => 'document',
    'insert_drive_file'  => 'document-plus',
    'folder_open'        => 'folder-open',
    'folder_copy'        => 'folder-plus',
    'picture_as_pdf'     => 'document',
    'cloud_download'     => 'cloud-arrow-down',
    'system_update'      => 'cloud-arrow-down',
    'backup'             => 'cloud-arrow-up',

    // ── Data / Tech ─────────────────────────────────────────
    'api'                => 'code-bracket',
    'webhook'            => 'variable',
    'data_object'        => 'code-bracket',
    'hub'                => 'circle-stack',
    'lan'                => 'server',
    'code'               => 'code-bracket',
    'qr_code_2'          => 'qr-code',
    'tag'                => 'tag',

    // ── Time / Schedule ─────────────────────────────────────
    'schedule'           => 'clock',
    'history'            => 'clock',
    'hourglass_empty'    => 'clock',
    'hourglass_top'      => 'clock',
    'today'              => 'calendar-days',
    'event_available'    => 'check-circle',
    'event_busy'         => 'x-circle',

    // ── Shapes / Symbols ────────────────────────────────────
    'circle'             => 'circle-stack',
    'fiber_manual_record'=> 'circle-stack',
    'star'               => 'star',
    'grade'              => 'star',
    'toggle_on'          => 'check-circle',
    'bolt'               => 'bolt',
    'speed'              => 'bolt',
    'sparkles'           => 'sparkles',

    // ── Environment ─────────────────────────────────────────
    'rocket_launch'      => 'rocket-launch',
    'science'            => 'beaker',
    'bug_report'         => 'bug-ant',
    'network_check'      => 'signal',

    // ── Maps / Location ─────────────────────────────────────
    'location_on'        => 'map-pin',
    'map'                => 'map',
    'public'             => 'globe-alt',
    'language'           => 'language',

    // ── Objects ─────────────────────────────────────────────
    'light_bulb'         => 'light-bulb',
    'puzzle_piece'       => 'puzzle-piece',
    'gift'               => 'gift',
    'identification'     => 'identification',

    // ── Misc ────────────────────────────────────────────────
    'auto_delete'        => 'clock',
    'cleaning_services'  => 'sparkles',
    'handyman'           => 'wrench-screwdriver',
    'pin'                => 'map-pin',
    'label'              => 'tag',
    'sync'               => 'arrows-right-left',
    'sync_alt'           => 'arrows-right-left',
    'inventory'          => 'queue-list',
    'receipt_long'       => 'document-text',
    'checklist'          => 'clipboard-document-list',

    // ── Dynamic / Computed ──────────────────────────────────
    'add_circle'          => 'plus-circle',
    'remove_circle'       => 'x-circle',
    'shopping_cart'       => 'shopping-cart',
    'admin_panel'         => 'shield-check',
    'admin_panel_settings' => 'shield-check',

    // ── Nav / UI extras ──────────────────────────────────────────
    'menu'                => 'bars-3',
    'notifications_none'  => 'bell',
    'notifications_off'   => 'bell-slash',
    'keyboard'            => 'command-line',
    'logout'              => 'arrow-left-on-rectangle',
    'list'                => 'queue-list',
    'extension'           => 'puzzle-piece',
    'expand_more'         => 'chevron-down',

    // ── Status / misc (were unmapped → rendered as ❓) ─────────────
    'key'                    => 'key',
    'radio_button_unchecked' => 'minus-circle',
];
