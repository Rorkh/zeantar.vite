<?php

namespace Zeantar\Vite\Controller;

use Bitrix\Main\Engine\Response\AjaxJson;
use Bitrix\Main\Config\Option;

class Vite extends \Bitrix\Main\Engine\Controller
{
    public function buildAction(): AjaxJson
    {
        $viteFolder = Option::get('zeantar.vite', 'VITE_LOCATION', null);

        if (is_null($viteFolder)) {
            return new AjaxJson(['status' => 'error']);
        }

        chdir($viteFolder);
        exec("npm run build");

        return new AjaxJson(['status' => 'ok']);
    }
}