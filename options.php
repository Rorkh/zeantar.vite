<?php

use \Bitrix\Main\Localization\Loc;
use Zeantar\Vite\Configuration\Options;

Loc::loadMessages(__FILE__);
Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/main/options.php');

const MODULE_ID = 'zeantar.vite';

\Bitrix\Main\Loader::requireModule(MODULE_ID);

$options = new Options;

if (!Options::canRead()) {
    return;
}

$options::handleSave();
$tabs = $options::getTabs();

$tabControl = new CAdminTabControl('tabControl', $tabs);

$tabControl->Begin();
?>
    <form action="<?=($APPLICATION->GetCurPage())?>?mid=<?=MODULE_ID?>&lang=<?=LANG?>" method="post">
        <?php
        foreach($tabs as $tab) {
            $tabControl->BeginNextTab();
            if ($tab['OPTIONS']) {
                foreach ($tab['OPTIONS'] as $option) {
                    if (isset($option[3]['wide']) && $option[3][0] == 'checkbox') {
                        $isChecked = \Bitrix\Main\Config\Option::get(MODULE_ID, $option[0]) == 'Y';
                        $row = '
                        <tr>
                            <td class="adm-detail-content-cell-l" style="text-align: center;" colspan="2">
                                <label for="' . $option[0] . '">' . $option[1] . '</label><a name="opt_' . $option[0] . '"></a>
                                <input type="checkbox" id="' . $option[0] . '" name="' . $option[0] . '" value="Y"' . ($isChecked ? ' checked' : '').' class="adm-designed-checkbox">
                                <label style="margin-left: 4px;" class="adm-designed-checkbox-label" for="' . $option[0] . '" title=""></label>
                            </td>
                        </tr>';

                        echo $row;
                        continue;
                    }

                    if ($option[3][0] == 'label') {
                        $style = isset($option[3]['style']) ? ' style="' . $option[3]['style'] . '"' : '';
                        $left_width = $option[3]['left_width'] ? $option[3]['left_width'] : '50%';

                        $row = '
                        <tr>
                            <td width="' . $left_width . '" class="adm-detail-content-cell-l">' . $option[1] . ':</td>
		                    <td width="50%" class="adm-detail-content-cell-r">
							    <span' . $style . '>' . $option[2] . '</span>
					        </td>
	                    </tr>';
                        echo $row;
                        continue;
                    }

                    if (isset($option[3]['left_width'])) {
                        ob_start();
                        __AdmSettingsDrawRow(MODULE_ID, $option);
                        $row = ob_get_clean();

                        $row = preg_replace('/\t+/', '', $row);
                        $row = str_replace(
                            "<tr>\n<td width=\"50%\">",
                            '<tr><td width="' . $option[3]['left_width'] . '">',
                            $row
                        );
                        echo $row;
                    } else {
                        __AdmSettingsDrawRow(MODULE_ID, $option);
                    }
                }
            } else if ($tab['DIV'] === 'rights') {
                require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/admin/group_rights.php');
            }
        }
        $tabControl->Buttons();
        ?>
        <input type="submit" <?php if (!Options::canWrite()) echo 'disabled'?> name="apply" value="Сохранить" class="adm-btn-save"/>
        <input type="submit" <?php if (!Options::canWrite()) echo 'disabled'?> title="<?=GetMessage('MAIN_HINT_RESTORE_DEFAULTS')?>" name="restore" value="<?=GetMessage('MAIN_RESTORE_DEFAULTS')?>">
        <?=bitrix_sessid_post()?>
    </form>
<?php
$tabControl->End();
?>

<h2><?=GetMessage("ZEANTAR_VITE_SYSTEM_PROCEDURES") ?></h2>

<?php
$proceduresTabs = [
    [
        'DIV' => 'viteBuild', 
        'TAB' => GetMessage('ZEANTAR_VITE'), 
        'ICON' => 'sale_settings', 
        'TITLE' => GetMessage('ZEANTAR_VITE')
    ]
];
$proceduresTabControl = new CAdminTabControl('tabControlProc', $proceduresTabs, true, true);

$proceduresTabControl->Begin();
$proceduresTabControl->BeginNextTab();

$viteFolder = \Bitrix\Main\Config\Option::get('zeantar.vite', 'VITE_LOCATION');
$manifestLocation = $viteFolder . '/dist/.vite/manifest.json';
?>
<tr>
    <td align="left">
        <h4 style="margin-top: 0;"><?php echo GetMessage('ZEANTAR_VITE_SYSTEM_PROCEDURES_BUILD'); ?></h4>
        <input class="adm-btn-save" type="button" id="zeantar_vite_build" value="<? echo GetMessage('ZEANTAR_VITE_SYSTEM_PROCEDURES_BUILD_BUTTON'); ?>">
        <p style="margin-bottom: 0;"><? echo GetMessage('ZEANTAR_VITE_SYSTEM_PROCEDURES_BUILD_DESCRIPTION'); ?></p>
        
        <?php
        # TODO: CHANGE
        ?>

        <?php if (!file_exists($manifestLocation)) {?>
            <p id="build-warning" style="margin-bottom: 0;font-weight: bold;color: red;">Манифест не найден. Для полноценной работы модуля требуется запустить сборку</p>
        <?php }?>
    </td>
</tr>
<?php
$proceduresTabControl->End();
?>

<script>
    BX.ready(function() {
        var viteBuildBtn = BX('zeantar_vite_build');

        BX.bind(viteBuildBtn, 'click', function() {
            viteBuildBtn.disabled = true;

            BX.ajax.runAction('zeantar:vite.Vite.build', {
                data: {}
            }).then(function (response) {
                viteBuildBtn.disabled = false;
            }, function (response) {
                viteBuildBtn.disabled = false;
            });
        });

        <?php if (\Bitrix\Main\Config\Option::get(MODULE_ID, 'VITE_BUILD_NOTIFICATION') == 'Y') {?>
        var buildWarning = document.getElementById('build-warning');
        if (buildWarning) {
            const y = buildWarning.getBoundingClientRect().top + window.scrollY - 300;
            window.scroll({
                top: y,
                behavior: 'smooth'
            });
        }
        <?php }?>
    });
</script>
