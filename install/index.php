<?php

use Zeantar\Vite\Events;

class zeantar_vite extends CModule
{
    public $MODULE_ID = "zeantar.vite";

    public function __construct()
    {
        $arModuleVersion = [];
        include(__DIR__ . "/version.php");

        if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion)) {
            $this->MODULE_VERSION = $arModuleVersion["VERSION"];
            $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        }

        $this->MODULE_NAME = GetMessage("MODULE_NAME");
        $this->MODULE_DESCRIPTION = GetMessage("MODULE_DESCRIPTION");
        $this->PARTNER_NAME = GetMessage('PARTNER_NAME');
        $this->MODULE_URI = GetMessage('PARTNER_URI');
    }

    public function DoInstall()
    {
        $eventManager = \Bitrix\Main\EventManager::getInstance();

        $eventManager->registerEventHandlerCompatible('main', 'OnEndBufferContent', $this->MODULE_ID, Events::class, 'OnEndBufferContent', 300);

        RegisterModule($this->MODULE_ID);
        return true;
    }

    public function DoUninstall()
    {
        UnRegisterModule($this->MODULE_ID);
        return true;
    }
}