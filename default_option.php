<?php

$zeantar_vite_default_option = [
    'VITE_LOCATION' => $_SERVER['DOCUMENT_ROOT'] . '/local/vite/',
    'VITE_ENABLED' => 'Y',
    'VITE_AUTO_LOAD' => 'Y',

    'VITE_AUTO_LOAD_MASK' => '*.php',
    'VITE_AUTO_LOAD_UNMASK' => '/bitrix/*',

    'VITE_DYNAMIC' => 'Y',

    'VITE_BUILD_NOTIFICATION' => 'Y',
];