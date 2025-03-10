<?php

namespace Zeantar\Vite;

use Bitrix\Main\Config\Option;
use Zeantar\Vite\Exception\UnclosedInlineException;
final class Events
{
    private static function findTextOccurance(string $haystack, string $needle): array
    {
        $offsets = [];
        $offset = 0;
    
        while ($offset !== false) {
            $offset = mb_strpos($haystack, $needle, $offset + 1);
            if (is_int($offset)) {
                $offsets[] = $offset; 
            }
        }

        return $offsets;
    }

    private static function mb_substr_replace(string|array $string, string|array $replacement, int $start, ?int $length = null): array|string
    {
        if (is_array($string)) {
            $num = count($string);
            // $replacement
            $replacement = is_array($replacement) ? array_slice($replacement, 0, $num) : array_pad(array($replacement), $num, $replacement);
            // $start
            if (is_array($start)) {
                $start = array_slice($start, 0, $num);
                foreach ($start as $key => $value)
                    $start[$key] = is_int($value) ? $value : 0;
            }
            else {
                $start = array_pad(array($start), $num, $start);
            }
            // $length
            if (!isset($length)) {
                $length = array_fill(0, $num, 0);
            }
            elseif (is_array($length)) {
                $length = array_slice($length, 0, $num);
                foreach ($length as $key => $value)
                    $length[$key] = isset($value) ? (is_int($value) ? $value : $num) : 0;
            }
            else {
                $length = array_pad(array($length), $num, $length);
            }
            // Recursive call
            return array_map(__FUNCTION__, $string, $replacement, $start, $length);
        }
        preg_match_all('/./us', (string)$string, $smatches);
        preg_match_all('/./us', (string)$replacement, $rmatches);
        if ($length === NULL) $length = mb_strlen($string);
        array_splice($smatches[0], $start, $length, $rmatches[0]);
        return join($smatches[0]);
    }

    private static function processDynamicMounts(Vite $vite, &$content): void
    {
        $offsets = self::findTextOccurance($content, '<!-- app');

        foreach ($offsets as $offset) {
            $buffer = $character = null;

            $ptr = $offset;

            while (true) {
                $character = mb_substr($content, $ptr, 1);

                if ($character == "\n") {
                    $buffer = str_replace('app: ', '', $buffer);

                    $buffer = str_replace(' ', '', $buffer);
                    $buffer = trim($buffer, '<!-> ');

                    if (!$vite->isDebugEnabled()) {
                        $content = self::mb_substr_replace($content, '', $offset, $ptr-$offset);
                    }

                    [$filename, $id] = explode('|', trim($buffer));
                    $vite->requireDynamicMount('../../' . $filename, $id);

                    $content = mb_substr($content, 0, $offset) . "<div id=\"$id\"></div>" . mb_substr($content, $offset);

                    break;
                }

                $ptr = $ptr + 1;
                $buffer .= $character;
            }
        }
    }

    private static function processDynamicInlines(Vite $vite, &$content): void
    {
        $startOffsets = self::findTextOccurance($content, '<!-- inline:');
        $inlines = [];

        foreach ($startOffsets as $offset) {
            $buffer = $character = null;
            $ptr = $offset;

            while (true) {
                $character = mb_substr($content, $ptr, 1);

                if ($character == "\n") {
                    $buffer = str_replace('inline: ', '', $buffer);

                    $buffer = str_replace(' ', '', $buffer);
                    $buffer = trim($buffer, '<!-> ');

                    $id = trim($buffer);
                    $inlines[] = ['id' => $id, 'start' => $offset]; 

                    break;
                }

                $ptr = $ptr + 1;
                $buffer .= $character;
            }
        }

        $endOffsets = self::findTextOccurance($content, '<!-- inline-end');

        if (count($startOffsets) !== count($endOffsets)) {
            if ($vite->isDebugEnabled()) {
                throw new UnclosedInlineException;
            } else {
                return;
            }
        }

        foreach ($inlines as $k => $inline) {
            $inlineEnd = $endOffsets[$k];

            $character = null;
            $ptr = $inlineEnd;

            while (true) {
                $character = mb_substr($content, $ptr, 1);

                if ($character == "\n") {
                    $inlineEnd = $ptr;
                    break;
                }

                $ptr = $ptr + 1;
            }

            $applicationLength = $inlineEnd - $inline['start'];
            $applicationContent = mb_substr($content, $inline['start'], $applicationLength);

            $inlineId = $inline['id'];
            $applicationContent = str_replace('<!-- inline: ' . $inlineId . ' -->', '', $applicationContent);
            $applicationContent = str_replace('<!-- inline-end -->', '', $applicationContent);

            $unique = $vite->requireDynamicApplication($applicationContent);
            $vite->requireDynamicMount("../application/$unique.vue", $inlineId);

            $content = self::mb_substr_replace(
                $content, 
                "<div id=\"$inlineId\"></div>", 
                $inline['start'], 
                $applicationLength
            );
        }
    }

    public static function OnEndBufferContent(&$content): void
    {
        $vite = Vite::getInstance();
        if (!$vite->isAutoloadEnabled() || $vite->isInjected()) {
            return;
        }

        $vite->setInjected(true);

        $queryPos = mb_strpos($_SERVER["REQUEST_URI"], "?");
		$requestUri = $queryPos === false ? $_SERVER["REQUEST_URI"] : mb_substr($_SERVER["REQUEST_URI"], 0, $queryPos);

        $excludeMask = Option::get('zeantar.vite', 'VITE_AUTO_LOAD_UNMASK');
        foreach (explode(';', $excludeMask) as $mask) {
            $mask = strtr($mask, array(
                '*' => '.*',
                '?' => '.',
            ));
            
            if (preg_match("'^".$mask."$'", $requestUri) > 0) {
                return;
            }
        }

        $isIncluded = false;
        $includeMask = Option::get('zeantar.vite', 'VITE_AUTO_LOAD_MASK');
        foreach (explode(';', $includeMask) as $mask) {
            $mask = strtr($mask, array(
                '*' => '.*',
                '?' => '.',
            ));
            
            if (preg_match("'^".$mask."$'", $requestUri) > 0) {
                $isIncluded = true;
            }
        }

        if (!$isIncluded) {
            return;
        }

        $headEndTag = mb_strpos($content, '</head>');
        $content = mb_substr($content, 0, $headEndTag) . $vite->getHead(). mb_substr($content, $headEndTag);

        if (Option::get('zeantar.vite', 'VITE_DYNAMIC') !== 'Y') {
            return;
        }

        self::processDynamicMounts($vite, $content);
        self::processDynamicInlines($vite, $content);
    }
}