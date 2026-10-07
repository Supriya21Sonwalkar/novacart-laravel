<?php

namespace App\Services;

use RuntimeException;
use ZipArchive;

class XlsxReader
{
    /**
     * Read an XLSX file and return rows as arrays.
     *
     * First row contains the Excel headers.
     */
    public function read($filePath)
    {
        if (!file_exists($filePath)) {
            throw new RuntimeException(
                'Excel file was not found.'
            );
        }

        if (!class_exists('ZipArchive')) {
            throw new RuntimeException(
                'PHP ZipArchive extension is not enabled.'
            );
        }

        $zip = new ZipArchive();

        if ($zip->open($filePath) !== true) {
            throw new RuntimeException(
                'Unable to open the Excel file.'
            );
        }

        /*
         * ---------------------------------------------------------
         * Load shared strings
         * ---------------------------------------------------------
         */

        $sharedStrings = [];

        if ($zip->locateName('xl/sharedStrings.xml') !== false) {

            $xmlContent = $zip->getFromName(
                'xl/sharedStrings.xml'
            );

            if ($xmlContent === false) {
                $zip->close();

                throw new RuntimeException(
                    'Unable to read Excel shared strings.'
                );
            }

            $xml = simplexml_load_string($xmlContent);

            if ($xml === false) {
                $zip->close();

                throw new RuntimeException(
                    'Invalid sharedStrings.xml file.'
                );
            }

            /*
             * Register the SpreadsheetML namespace.
             */
            $xml->registerXPathNamespace(
                'x',
                'http://schemas.openxmlformats.org/spreadsheetml/2006/main'
            );

            $stringNodes = $xml->xpath('//x:si');

            if ($stringNodes !== false) {

                foreach ($stringNodes as $stringNode) {

                    /*
                     * IMPORTANT:
                     * XPath namespace registrations are not reliably
                     * inherited by SimpleXMLElement child objects.
                     */
                    $stringNode->registerXPathNamespace(
                        'x',
                        'http://schemas.openxmlformats.org/spreadsheetml/2006/main'
                    );

                    $text = '';

                    /*
                     * A shared string can contain one or more <t> elements.
                     */
                    $textNodes = $stringNode->xpath('.//x:t');

                    if ($textNodes !== false) {

                        foreach ($textNodes as $textNode) {
                            $text .= (string) $textNode;
                        }
                    }

                    $sharedStrings[] = $text;
                }
            }
        }

        /*
         * ---------------------------------------------------------
         * Find the first worksheet
         * ---------------------------------------------------------
         */

        $sheetPath = $this->findFirstWorksheet($zip);

        if (!$sheetPath) {
            $zip->close();

            throw new RuntimeException(
                'No worksheet was found in the Excel file.'
            );
        }

        /*
         * ---------------------------------------------------------
         * Read worksheet XML
         * ---------------------------------------------------------
         */

        $sheetContent = $zip->getFromName($sheetPath);

        if ($sheetContent === false) {
            $zip->close();

            throw new RuntimeException(
                'Unable to read the Excel worksheet.'
            );
        }

        $sheetXml = simplexml_load_string($sheetContent);

        if ($sheetXml === false) {
            $zip->close();

            throw new RuntimeException(
                'Invalid worksheet XML.'
            );
        }

        $sheetXml->registerXPathNamespace(
            'x',
            'http://schemas.openxmlformats.org/spreadsheetml/2006/main'
        );

        $rowNodes = $sheetXml->xpath(
            '//x:sheetData/x:row'
        );

        if ($rowNodes === false) {
            $zip->close();

            throw new RuntimeException(
                'Unable to read worksheet rows.'
            );
        }

        $rows = [];

        foreach ($rowNodes as $rowNode) {

            /*
             * IMPORTANT:
             * Register namespace on each row node before using
             * relative XPath expressions.
             */
            $rowNode->registerXPathNamespace(
                'x',
                'http://schemas.openxmlformats.org/spreadsheetml/2006/main'
            );

            $cells = [];

            $cellNodes = $rowNode->xpath('./x:c');

            if ($cellNodes === false) {
                continue;
            }

            foreach ($cellNodes as $cellNode) {

                /*
                 * IMPORTANT:
                 * Register namespace on each cell node because
                 * additional XPath calls are made on this object.
                 */
                $cellNode->registerXPathNamespace(
                    'x',
                    'http://schemas.openxmlformats.org/spreadsheetml/2006/main'
                );

                $cellReference = isset($cellNode['r'])
                    ? (string) $cellNode['r']
                    : '';

                $columnIndex = $this->columnIndex(
                    $cellReference
                );

                $value = '';

                $type = isset($cellNode['t'])
                    ? (string) $cellNode['t']
                    : '';

                /*
                 * Get the cell value.
                 */
                $valueNodes = $cellNode->xpath('./x:v');

                $rawValue = '';

                if (
                    $valueNodes !== false &&
                    isset($valueNodes[0])
                ) {
                    $rawValue = (string) $valueNodes[0];
                }

                /*
                 * Shared string.
                 */
                if ($type === 's') {

                    $sharedIndex = (int) $rawValue;

                    $value = isset(
                        $sharedStrings[$sharedIndex]
                    )
                        ? $sharedStrings[$sharedIndex]
                        : '';
                }

                /*
                 * Inline string.
                 */
                elseif ($type === 'inlineStr') {

                    $textNodes = $cellNode->xpath(
                        './/x:is//x:t'
                    );

                    if ($textNodes !== false) {

                        foreach ($textNodes as $textNode) {
                            $value .= (string) $textNode;
                        }
                    }
                }

                /*
                 * Boolean.
                 */
                elseif ($type === 'b') {

                    $value = $rawValue === '1'
                        ? 'TRUE'
                        : 'FALSE';
                }

                /*
                 * Normal number/date/text.
                 */
                else {

                    $value = $rawValue;
                }

                /*
                 * Put the value into its correct Excel column.
                 */
                $cells[$columnIndex] = $value;
            }

            /*
             * Find the largest column used by this row.
             */
            $maxColumn = empty($cells)
                ? -1
                : max(array_keys($cells));

            $row = [];

            for ($i = 0; $i <= $maxColumn; $i++) {

                $row[$i] = isset($cells[$i])
                    ? $cells[$i]
                    : '';
            }

            $rows[] = $row;
        }

        $zip->close();

        return $rows;
    }

    /**
     * Find the first worksheet in the workbook.
     */
    protected function findFirstWorksheet(ZipArchive $zip)
    {
        /*
         * First try the standard worksheet path.
         */
        if (
            $zip->locateName(
                'xl/worksheets/sheet1.xml'
            ) !== false
        ) {
            return 'xl/worksheets/sheet1.xml';
        }

        /*
         * If sheet1 does not exist, inspect workbook.xml.
         */
        if (
            $zip->locateName(
                'xl/workbook.xml'
            ) === false
        ) {
            return null;
        }

        $workbookContent = $zip->getFromName(
            'xl/workbook.xml'
        );

        if ($workbookContent === false) {
            return null;
        }

        $workbook = simplexml_load_string(
            $workbookContent
        );

        if ($workbook === false) {
            return null;
        }

        $workbook->registerXPathNamespace(
            'x',
            'http://schemas.openxmlformats.org/spreadsheetml/2006/main'
        );

        $sheets = $workbook->xpath(
            '//x:sheets/x:sheet'
        );

        if (
            $sheets === false ||
            !isset($sheets[0])
        ) {
            return null;
        }

        $relationshipId = (string) $sheets[0]['r:id'];

        if ($relationshipId === '') {
            return null;
        }

        /*
         * Read workbook relationships.
         */
        if (
            $zip->locateName(
                'xl/_rels/workbook.xml.rels'
            ) === false
        ) {
            return null;
        }

        $relsContent = $zip->getFromName(
            'xl/_rels/workbook.xml.rels'
        );

        if ($relsContent === false) {
            return null;
        }

        $rels = simplexml_load_string(
            $relsContent
        );

        if ($rels === false) {
            return null;
        }

        $rels->registerXPathNamespace(
            'r',
            'http://schemas.openxmlformats.org/package/2006/relationships'
        );

        $relationships = $rels->xpath(
            '//r:Relationship'
        );

        if ($relationships === false) {
            return null;
        }

        foreach ($relationships as $relationship) {

            $id = (string) $relationship['Id'];

            if ($id !== $relationshipId) {
                continue;
            }

            $target = (string) $relationship['Target'];

            if ($target === '') {
                return null;
            }

            /*
             * Most XLSX files use:
             * /worksheets/sheet1.xml
             */
            $target = ltrim(
                $target,
                '/'
            );

            if (strpos($target, 'xl/') !== 0) {
                $target = 'xl/' . $target;
            }

            return $target;
        }

        return null;
    }

    /**
     * Convert Excel cell reference to zero-based column index.
     *
     * Example:
     * A  -> 0
     * B  -> 1
     * Z  -> 25
     * AA -> 26
     */
    protected function columnIndex($cellReference)
    {
        if ($cellReference === '') {
            return 0;
        }

        preg_match(
            '/^[A-Z]+/i',
            $cellReference,
            $matches
        );

        if (empty($matches)) {
            return 0;
        }

        $letters = strtoupper(
            $matches[0]
        );

        $index = 0;

        for ($i = 0; $i < strlen($letters); $i++) {

            $index =
                ($index * 26) +
                (ord($letters[$i]) - 64);
        }

        return $index - 1;
    }
}