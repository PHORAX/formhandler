<?php
declare(strict_types=1);

namespace Typoheads\Formhandler\Hooks;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectStdWrapHookInterface;
use Typoheads\Formhandler\Utility\Globals;

class StdWrapHook implements ContentObjectStdWrapHookInterface
{
    private $originalGET;
    private $originalPOST;

    /**
     * Hook for modifying $content before core's stdWrap does anything
     *
     * @param string $content Input value undergoing processing in this function. Possibly substituted by other values fetched from another source.
     * @param array $configuration TypoScript stdWrap properties
     * @param \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $parentObject Parent content object
     * @return string Further processed $content
     */
    public function stdWrapPreProcess($content, array $configuration, ContentObjectRenderer &$parentObject): string
    {
        if (isset($configuration['sanitize']) && (int)($configuration['sanitize']) === 1) {
            $globals = GeneralUtility::makeInstance(Globals::class);
            $this->originalGET = $_GET;
            $this->originalPOST = $_POST;
            $prefix = $globals->getFormValuesPrefix();
            if (strlen($prefix) > 0) {
                $_GET[$prefix] = $globals->getGP();
                $_POST[$prefix] = $globals->getGP();
            } else {
                $_GET = array_merge($_GET, $globals->getGP());
                $_POST = array_merge($_POST, $globals->getGP());
            }
        }
        return (string) $content;
    }

    /**
     * Hook for modifying $content after core's stdWrap has processed setContentToCurrent, setCurrent, lang, data, field, current, cObject, numRows, filelist and/or preUserFunc
     *
     * @param string $content Input value undergoing processing in this function. Possibly substituted by other values fetched from another source.
     * @param array $configuration TypoScript stdWrap properties
     * @param \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $parentObject Parent content object
     * @return string Further processed $content
     */
    public function stdWrapOverride($content, array $configuration, ContentObjectRenderer &$parentObject): string
    {
        return (string) $content;
    }

    /**
     * Hook for modifying $content after core's stdWrap has processed override, preIfEmptyListNum, ifEmpty, ifBlank, listNum, trim and/or more (nested) stdWraps
     *
     * @param string $content Input value undergoing processing in this function. Possibly substituted by other values fetched from another source.
     * @param array $configuration TypoScript "stdWrap properties".
     * @param \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $parentObject Parent content object
     * @return string Further processed $content
     */
    public function stdWrapProcess($content, array $configuration, ContentObjectRenderer &$parentObject): string
    {
        return (string) $content;
    }

    /**
     * Hook for modifying $content after core's stdWrap has processed anything but debug
     *
     * @param string $content Input value undergoing processing in this function. Possibly substituted by other values fetched from another source.
     * @param array $configuration TypoScript stdWrap properties
     * @param \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $parentObject Parent content object
     * @return string Further processed $content
     */
    public function stdWrapPostProcess($content, array $configuration, ContentObjectRenderer &$parentObject): string
    {
        if (isset($configuration['sanitize']) && (int)($configuration['sanitize']) === 1) {
            $_GET = $this->originalGET;
            $_POST = $this->originalPOST;
        }
        return (string) $content;
    }
}
