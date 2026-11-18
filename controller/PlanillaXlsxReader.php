<?php

declare(strict_types=1);

namespace App\Controller;

final class PlanillaXlsxReader
{
    private const MAIN_SHEET = 'Lectura';
    private const HEADERS = [
        'Placa', 'Lote', 'Descripcion', 'Nom_ubicacion_fisica',
        'Identificacion_responsable', 'Nombre_responsable', 'Centro_costo',
        'Nombre_centro_costo', 'Nombre_estado', 'Depreciado',
    ];

    /** @return array{headerRow:int, rows:iterable<array{row:int,values:array<string,string|null>}>} */
    public function read(string $path): array
    {
        if (!class_exists(\ZipArchive::class) || !function_exists('simplexml_load_string')) {
            throw new \RuntimeException('El servidor requiere las extensiones ZIP y SimpleXML de PHP para importar XLSX.');
        }
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) throw new \InvalidArgumentException('El archivo XLSX no se pudo abrir.');
        try {
            $workbook = $this->xml($zip, 'xl/workbook.xml');
            $relationships = $this->xml($zip, 'xl/_rels/workbook.xml.rels');
            $targets = [];
            foreach ($relationships->xpath('//*[local-name()="Relationship"]') ?: [] as $relationship) {
                $targets[(string) $relationship['Id']] = (string) $relationship['Target'];
            }
            $sheetPath = null;
            foreach ($workbook->xpath('//*[local-name()="sheets"]/*[local-name()="sheet"]') ?: [] as $sheet) {
                if ((string) $sheet['name'] !== self::MAIN_SHEET) continue;
                $relationshipId = (string) $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')->id;
                $target = $targets[$relationshipId] ?? null;
                if (!is_string($target) || $target === '') break;
                $sheetPath = str_starts_with($target, '/') ? ltrim($target, '/') : 'xl/' . ltrim($target, '/');
                break;
            }
            if ($sheetPath === null) throw new \InvalidArgumentException('El libro debe contener una hoja llamada Lectura.');
            $strings = $this->sharedStrings($zip);
            $sheet = $this->xml($zip, $sheetPath);
            $headers = null;
            $headerRow = 0;
            $rows = [];
            foreach ($sheet->xpath('//*[local-name()="sheetData"]/*[local-name()="row"]') ?: [] as $row) {
                $number = (int) $row['r'];
                $values = $this->rowValues($row, $strings);
                if ($headers === null) {
                    $normalized = array_map(static fn ($value) => trim((string) $value), $values);
                    if (in_array('Placa', $normalized, true) && in_array('Centro_costo', $normalized, true)) {
                        $headers = $normalized;
                        $headerRow = $number;
                    }
                    continue;
                }
                $record = [];
                foreach (self::HEADERS as $header) {
                    $column = array_search($header, $headers, true);
                    $value = $column === false ? null : ($values[$column] ?? null);
                    $record[$header] = $value === null || trim($value) === '' ? null : trim($value);
                }
                if (count(array_filter($record, static fn ($value) => $value !== null)) === 0) continue;
                $rows[] = ['row' => $number, 'values' => $record];
            }
            if ($headers === null) throw new \InvalidArgumentException('No se encontraron los encabezados de la planilla en la hoja Lectura.');
            foreach (self::HEADERS as $header) {
                if (!in_array($header, $headers, true)) throw new \InvalidArgumentException("Falta la columna requerida {$header}.");
            }
            return ['headerRow' => $headerRow, 'rows' => $rows];
        } finally {
            $zip->close();
        }
    }

    private function xml(\ZipArchive $zip, string $path): \SimpleXMLElement
    {
        $stat = $zip->statName($path);
        if (!is_array($stat) || ($stat['size'] ?? 0) > 100 * 1024 * 1024) throw new \InvalidArgumentException('El XLSX contiene una hoja ausente o demasiado grande.');
        $contents = $zip->getFromName($path);
        if ($contents === false) throw new \InvalidArgumentException('El XLSX contiene una hoja ausente o demasiado grande.');
        $previous = libxml_use_internal_errors(true);
        try {
            $xml = simplexml_load_string($contents, \SimpleXMLElement::class, LIBXML_NONET | LIBXML_COMPACT);
            if (!$xml instanceof \SimpleXMLElement) throw new \InvalidArgumentException('El XLSX contiene XML inválido.');
            return $xml;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    /** @return list<string> */
    private function sharedStrings(\ZipArchive $zip): array
    {
        $contents = $zip->getFromName('xl/sharedStrings.xml');
        if ($contents === false) return [];
        if (strlen($contents) > 100 * 1024 * 1024) throw new \InvalidArgumentException('La tabla de textos del XLSX es demasiado grande.');
        $previous = libxml_use_internal_errors(true);
        try {
            $xml = simplexml_load_string($contents, \SimpleXMLElement::class, LIBXML_NONET | LIBXML_COMPACT);
            if (!$xml instanceof \SimpleXMLElement) throw new \InvalidArgumentException('La tabla de textos del XLSX es inválida.');
            $strings = [];
            foreach ($xml->xpath('//*[local-name()="si"]') ?: [] as $item) {
                $value = '';
                foreach ($item->xpath('.//*[local-name()="t"]') ?: [] as $text) $value .= (string) $text;
                $strings[] = $value;
            }
            return $strings;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    /** @return array<int,string|null> */
    private function rowValues(\SimpleXMLElement $row, array $strings): array
    {
        $values = [];
        foreach ($row->xpath('./*[local-name()="c"]') ?: [] as $cell) {
            $reference = (string) $cell['r'];
            if (!preg_match('/^([A-Z]+)/', $reference, $match)) continue;
            $column = 0;
            foreach (str_split($match[1]) as $letter) $column = $column * 26 + ord($letter) - 64;
            $type = (string) $cell['t'];
            $rawValue = $cell->xpath('./*[local-name()="v"]')[0] ?? null;
            if ($type === 's') $value = $strings[(int) ($rawValue ?? 0)] ?? null;
            elseif ($type === 'inlineStr') $value = implode('', array_map('strval', $cell->xpath('./*[local-name()="is"]//*[local-name()="t"]') ?: []));
            else $value = $rawValue === null ? null : (string) $rawValue;
            $values[$column - 1] = $value;
        }
        return $values;
    }
}
