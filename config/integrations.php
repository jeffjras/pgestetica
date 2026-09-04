<?php
return [
    'mercado_pago' => [
        'access_token' => getenv('MP_ACCESS_TOKEN') ?: '',
        'public_key' => getenv('MP_PUBLIC_KEY') ?: '',
        'webhook_secret' => getenv('MP_WEBHOOK_SECRET') ?: '',
        'sandbox' => filter_var(getenv('MP_SANDBOX') ?: '1', FILTER_VALIDATE_BOOL),
    ],
    'whatsapp' => [
        'phone_number_id' => getenv('WA_PHONE_NUMBER_ID') ?: '',
        'access_token' => getenv('WA_ACCESS_TOKEN') ?: '',
        'graph_version' => getenv('WA_GRAPH_VERSION') ?: 'v23.0',
        'template_confirmation' => getenv('WA_TEMPLATE_CONFIRMATION') ?: '',
        'template_reminder' => getenv('WA_TEMPLATE_REMINDER') ?: '',
        'language' => getenv('WA_TEMPLATE_LANGUAGE') ?: 'pt_BR',
    ],
];
