<?php

namespace Zeantar\Vite\Configuration;

use Bitrix\Main\Context;

abstract class AbstractOptions
{
    protected const MODULE_ID = 'zeantar.vite';

    /**
     * @return array
     */
    abstract public static function getTabs(): array;

    /**
     * @return array
     */
    protected static function getConfigurableOptions(): array
    {
        $result = [];
        $tabs = static::getTabs() ?? [];

        foreach ($tabs as $tab) {
            foreach ($tab['OPTIONS'] as $option) {
                $result[] = $option;
            }
        }

        return $result;
    }

    /**
     * @return void
     */
    public static function handleSave(): void
    {
        if (!static::canWrite()) {
            return;
        }

        $request = Context::getCurrent()->getRequest();

        if ($request->isPost() && check_bitrix_sessid()) {
            if ($request['restore']) {
                foreach (self::getConfigurableOptions() as $option) {
                    \Bitrix\Main\Config\Option::delete(self::MODULE_ID, ['name' => $option[0]]);
                }               
            }

            $tabs = static::getTabs();

            foreach ($tabs as $tab) {
                foreach ($tab["OPTIONS"] as $option) {
                    if (!is_array($option) || isset($option['note']) || is_null($option[0])) {
                        continue;
                    }
                    
                    if ($request["apply"]) {
                        $name = $option[0];
                        $optionValue = $request->getPost($name);

                        if ($option[3][0] == 'checkbox' && $optionValue !== 'Y') {
                            $optionValue = 'N';
                        }

                        \Bitrix\Main\Config\Option::set(self::MODULE_ID, $name, $optionValue);
                    }
                }
            }
        }
    }

    /**
     * @return mixed
     */
    protected static function getRight(): mixed
    {
        global $APPLICATION;
        return $APPLICATION->GetGroupRight(self::MODULE_ID);
    }

    /**
     * @return boolean
     */
    public static function canRead(): bool
    {
        return static::getRight() >= 'R';
    }

     /**
     * @return boolean
     */
    public static function canWrite(): bool
    {
        return static::getRight() >= 'W';
    }
}
