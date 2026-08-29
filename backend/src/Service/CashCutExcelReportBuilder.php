<?php

namespace App\Service;

use App\Entity\CashCut;

class CashCutExcelReportBuilder
{
    /**
     * @param CashCut[] $cashCuts
     */
    public function build(array $cashCuts): string
    {
        $worksheets = [
            $this->buildSummaryWorksheet($cashCuts),
            $this->buildPaymentsWorksheet($cashCuts),
            $this->buildWithdrawalsWorksheet($cashCuts),
        ];

        return $this->xlsx($worksheets);
    }

    /**
     * @param CashCut[] $cashCuts
     */
    private function buildSummaryWorksheet(array $cashCuts): array
    {
        $totalCollected = 0.0;
        $totalWithdrawn = 0.0;
        $paymentsCount = 0;
        $uniqueCustomerIds = [];

        foreach ($cashCuts as $cashCut) {
            $totalCollected += (float) $cashCut->getTotalAmount();
            $totalWithdrawn += (float) $cashCut->getAmountToWithdraw();

            foreach ($cashCut->getCustomerPayments() as $payment) {
                $paymentsCount++;
                $customerId = $payment->getCustomer()?->getId();

                if ($customerId !== null) {
                    $uniqueCustomerIds[$customerId] = true;
                }
            }
        }

        return [
            'name' => 'Resumen',
            'rows' => [
            ['Metrica', 'Valor'],
            ['Cantidad de cortes cerrados', count($cashCuts)],
            ['Total recaudado', $totalCollected],
            ['Total retirado', $totalWithdrawn],
            ['Saldo neto', $totalCollected - $totalWithdrawn],
            ['Cantidad de cobros', $paymentsCount],
            ['Abonados unicos cobrados', count($uniqueCustomerIds)],
            ],
        ];
    }

    /**
     * @param CashCut[] $cashCuts
     */
    private function buildPaymentsWorksheet(array $cashCuts): array
    {
        $rows = [[
            'ID del corte',
            'Fecha/hora de cierre',
            'Fecha de pago',
            'Usuario',
            'Abonado',
            'Cliente',
            'Monto',
        ]];

        foreach ($cashCuts as $cashCut) {
            foreach ($cashCut->getCustomerPayments() as $payment) {
                $rows[] = [
                    $cashCut->getId(),
                    $this->formatDate($cashCut->getClosedAt()),
                    $this->formatDate($payment->getPaidAt()),
                    $cashCut->getUser()?->getUsername(),
                    $payment->getCustomer()?->getSubscriberNumber(),
                    $payment->getCustomer()?->getFullName(),
                    (float) $payment->getAmount(),
                ];
            }
        }

        return [
            'name' => 'Cobros',
            'rows' => $rows,
        ];
    }

    /**
     * @param CashCut[] $cashCuts
     */
    private function buildWithdrawalsWorksheet(array $cashCuts): array
    {
        $rows = [[
            'ID del corte',
            'Fecha/hora de cierre',
            'Usuario',
            'Total recaudado',
            'Comision del usuario',
            'Total retirado',
            'Cantidad de cobros',
        ]];

        foreach ($cashCuts as $cashCut) {
            $rows[] = [
                $cashCut->getId(),
                $this->formatDate($cashCut->getClosedAt()),
                $cashCut->getUser()?->getUsername(),
                (float) $cashCut->getTotalAmount(),
                (float) $cashCut->getUserCommissionAmount(),
                (float) $cashCut->getAmountToWithdraw(),
                count($cashCut->getCustomerPayments()),
            ];
        }

        return [
            'name' => 'Retiros',
            'rows' => $rows,
        ];
    }

    /**
     * @param array<int, array{name: string, rows: array<int, array<int, mixed>>}> $worksheets
     */
    private function xlsx(array $worksheets): string
    {
        $path = tempnam(sys_get_temp_dir(), 'beruki-cash-cut-report-');

        if ($path === false) {
            throw new \RuntimeException('Could not create a temporary XLSX file.');
        }

        $zip = new \ZipArchive();

        if ($zip->open($path, \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Could not open a temporary XLSX file.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypes(count($worksheets)));
        $zip->addFromString('_rels/.rels', $this->rootRelationships());
        $zip->addFromString('xl/workbook.xml', $this->workbook($worksheets));
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelationships(count($worksheets)));
        $zip->addFromString('xl/styles.xml', $this->styles());

        foreach ($worksheets as $index => $worksheet) {
            $zip->addFromString(
                sprintf('xl/worksheets/sheet%d.xml', $index + 1),
                $this->worksheet($worksheet['rows'])
            );
        }

        $zip->close();

        $contents = file_get_contents($path);
        @unlink($path);

        if ($contents === false) {
            throw new \RuntimeException('Could not read the generated XLSX file.');
        }

        return $contents;
    }

    private function contentTypes(int $worksheetCount): string
    {
        $worksheetOverrides = '';

        for ($sheet = 1; $sheet <= $worksheetCount; $sheet++) {
            $worksheetOverrides .= sprintf(
                '<Override PartName="/xl/worksheets/sheet%d.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>',
                $sheet
            );
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . $worksheetOverrides
            . '</Types>';
    }

    private function rootRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    /**
     * @param array<int, array{name: string, rows: array<int, array<int, mixed>>}> $worksheets
     */
    private function workbook(array $worksheets): string
    {
        $sheets = '';

        foreach ($worksheets as $index => $worksheet) {
            $sheetId = $index + 1;
            $sheets .= sprintf(
                '<sheet name="%s" sheetId="%d" r:id="rId%d"/>',
                $this->escape($worksheet['name']),
                $sheetId,
                $sheetId
            );
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . $sheets . '</sheets>'
            . '</workbook>';
    }

    private function workbookRelationships(int $worksheetCount): string
    {
        $relationships = '';

        for ($sheet = 1; $sheet <= $worksheetCount; $sheet++) {
            $relationships .= sprintf(
                '<Relationship Id="rId%d" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet%d.xml"/>',
                $sheet,
                $sheet
            );
        }

        $relationships .= sprintf(
            '<Relationship Id="rId%d" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>',
            $worksheetCount + 1
        );

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . $relationships
            . '</Relationships>';
    }

    private function styles(): string
    {
        return <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
                <fonts count="3">
                    <font>
                        <sz val="11"/>
                        <color rgb="FF171717"/>
                        <name val="Calibri"/>
                    </font>
                    <font>
                        <b/>
                        <sz val="11"/>
                        <color rgb="FFFFFFFF"/>
                        <name val="Calibri"/>
                    </font>
                    <font>
                        <b/>
                        <sz val="11"/>
                        <color rgb="FF6236E8"/>
                        <name val="Calibri"/>
                    </font>
                </fonts>
                <fills count="4">
                    <fill><patternFill patternType="none"/></fill>
                    <fill><patternFill patternType="gray125"/></fill>
                    <fill>
                        <patternFill patternType="solid">
                            <fgColor rgb="FF6236E8"/>
                            <bgColor indexed="64"/>
                        </patternFill>
                    </fill>
                    <fill>
                        <patternFill patternType="solid">
                            <fgColor rgb="FFF5F6F8"/>
                            <bgColor indexed="64"/>
                        </patternFill>
                    </fill>
                </fills>
                <borders count="2">
                    <border>
                        <left/><right/><top/><bottom/><diagonal/>
                    </border>
                    <border>
                        <left style="thin"><color rgb="FFD9DDE3"/></left>
                        <right style="thin"><color rgb="FFD9DDE3"/></right>
                        <top style="thin"><color rgb="FFD9DDE3"/></top>
                        <bottom style="thin"><color rgb="FFD9DDE3"/></bottom>
                        <diagonal/>
                    </border>
                </borders>
                <cellStyleXfs count="1">
                    <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
                </cellStyleXfs>
                <cellXfs count="5">
                    <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
                    <xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">
                        <alignment horizontal="center"/>
                    </xf>
                    <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1"/>
                    <xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>
                    <xf numFmtId="0" fontId="2" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>
                </cellXfs>
                <cellStyles count="1">
                    <cellStyle name="Normal" xfId="0" builtinId="0"/>
                </cellStyles>
            </styleSheet>
            XML;
    }

    /**
     * @param array<int, array<int, mixed>> $rows
     */
    private function worksheet(array $rows): string
    {
        $xmlRows = [];

        foreach ($rows as $rowIndex => $row) {
            $cells = [];
            $isTotalRow = in_array($row[0] ?? null, ['Total recaudado', 'Total retirado', 'Saldo neto'], true);

            foreach ($row as $columnIndex => $value) {
                $cells[] = $this->cell($value, $rowIndex + 1, $columnIndex + 1, $isTotalRow);
            }

            $xmlRows[] = sprintf('<row r="%d">%s</row>', $rowIndex + 1, implode('', $cells));
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetPr><tabColor rgb="FF6236E8"/></sheetPr>'
            . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . '<cols><col min="1" max="10" width="22" customWidth="1"/></cols>'
            . '<sheetData>' . implode('', $xmlRows) . '</sheetData>'
            . '</worksheet>';
    }

    private function cell(mixed $value, int $row, int $column, bool $isTotalRow): string
    {
        $reference = $this->columnName($column) . $row;
        $style = $this->style($row, $isTotalRow);

        if (is_int($value) || is_float($value)) {
            return sprintf('<c r="%s" s="%d"><v>%s</v></c>', $reference, $style, $value);
        }

        return sprintf(
            '<c r="%s" s="%d" t="inlineStr"><is><t>%s</t></is></c>',
            $reference,
            $style,
            $this->escape((string) ($value ?? ''))
        );
    }

    private function style(int $row, bool $isTotalRow): int
    {
        if ($row === 1) {
            return 1;
        }

        if ($isTotalRow) {
            return 4;
        }

        return $row % 2 === 0 ? 2 : 3;
    }

    private function columnName(int $column): string
    {
        $name = '';

        while ($column > 0) {
            $column--;
            $name = chr(65 + ($column % 26)) . $name;
            $column = intdiv($column, 26);
        }

        return $name;
    }

    private function formatDate(?\DateTimeInterface $dateTime): string
    {
        return $dateTime?->format('Y-m-d H:i:s') ?? '';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
