<?php

namespace Zeantar\Vite\Configuration;
final class Options extends AbstractOptions
{
    /**
     * @return array
     */
    private static function getMainTab(): array
    {
        return [
            'DIV' => 'main',
            'TAB' => GetMessage('MAIN_SETTINGS_TAB'),
            'TITLE' => GetMessage('MAIN_SETTINGS_TITLE'),
            'OPTIONS' => [
                GetMessage('ZEANTAR_VITE_COMMON_SETTINGS'),
                ['VITE_ENABLED', GetMessage('VITE_ENABLED'), null,
                    [
                        'checkbox',
                        'wide' => true,
                    ],
                ],
                [
                    'note' => GetMessage('VITE_ENABLED_NOTE'),
                ],
                ['VITE_DEBUG', GetMessage('VITE_DEBUG_ENABLED'), null,
                    [
                        'checkbox',
                        'wide' => true,
                    ],
                ],
                [
                    'note' => GetMessage('VITE_DEBUG_ENABLED_NOTE'),
                ],
                ['VITE_AUTO_LOAD', GetMessage('VITE_AUTO_LOAD'), null,
                    [
                        'checkbox',
                        'wide' => true
                    ],
                ],
                [
                    'note' => GetMessage('VITE_AUTO_LOAD_NOTE'),
                ],
                ['VITE_LOCATION', GetMessage('VITE_LOCATION'), null,
                    [
                        'text', 48,
                        'left_width' => '35%'
                    ],
                ],
                [
                    'note' => GetMessage('VITE_LOCATION_NOTE'),
                ],
                GetMessage('ZEANTAR_VITE_AUTO_LOAD_SETTINGS'),
                ['VITE_AUTO_LOAD_MASK', GetMessage('VITE_AUTO_LOAD_MASK'), null,
                    [
                        'text', 48,
                        'left_width' => '35%'
                    ],
                ],
                ['VITE_AUTO_LOAD_UNMASK', GetMessage('VITE_AUTO_LOAD_UNMASK'), null,
                    [
                        'text', 48,
                        'left_width' => '35%'
                    ],
                ],
                GetMessage('ZEANTAR_VITE_DYNAMIC_SETTINGS'),
                ['VITE_DYNAMIC', GetMessage('VITE_DYNAMIC_ENABLE'), null,
                    [
                        'checkbox',
                        'wide' => true
                    ],
                ],
            ],
        ];
    }

    private static function commandExists(string $cmd): bool
    {
        $return = shell_exec(sprintf("which %s", escapeshellarg($cmd)));
        return !empty($return);
    }

    /**
     * @return array
     */
    private static function getDiagnosticsTab(): array
    {
        $npmInstalled = self::commandExists('npm');

        $options = [
            'Сторонние зависимости',
            [null, 'Node Package Manager', $npmInstalled ? 'Установлен' : 'Не найден', [
                    'label',
                    'left_width' => '52%',
                    'style' => ($npmInstalled ? 'color: green;' : 'color: red;') . 'font-weight: bold'
                ]
            ],
        ];

        $viteFolder = \Bitrix\Main\Config\Option::get('zeantar.vite', 'VITE_LOCATION');
        $manifestLocation = $viteFolder . '/dist/.vite/manifest.json';

        if (file_exists($viteFolder . '/dist') && file_exists($manifestLocation)) {
            $manifestWritable = is_writable($manifestLocation);

            $options[] = 'Доступ к файлам';
            $options[] = [
                null, 'Манифест', $manifestWritable ? 'Доступен' : 'Нет доступа', [
                    'label',
                    'label_width' => '52%',
                    'style' => ($manifestWritable ? 'color: green;' : 'color: red;') . 'font-weight: bold'
                ]
            ];
        }

        return [
            'DIV' => 'diagnostics',
            'TAB' => GetMessage('DIAGNOSTICS_TAB'),
            'TITLE' => GetMessage('DIAGNOSTICS_TAB'),
            'OPTIONS' => $options
        ];
    }

    /**
     * @return array
     */
    private static function getInterfaceTab(): array
    {
        return [
            'DIV' => 'interface',
            'TAB' => GetMessage('INTERFACE_SETTINGS_TAB'),
            'TITLE' => GetMessage('INTERFACE_SETTINGS_TITLE'),
            'OPTIONS' => [
                GetMessage('ZEANTAR_VITE_ADMIN_SETTINGS'),
                ['VITE_BUILD_NOTIFICATION', GetMessage('VITE_BUILD_NOTIFICATION'), null,
                    [
                        'checkbox',
                        'wide' => true,
                    ],
                ],
                [
                    'note' => GetMessage('VITE_BUILD_NOTIFICATION_NOTE'),
                ],
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public static function getTabs(): array
    {
        return [
            self::getMainTab(),
            self::getDiagnosticsTab(),
            self::getInterfaceTab()
        ];
    }
}
