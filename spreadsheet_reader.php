<?php
/*
 * spreadsheet_reader.php
 * Shared by import_students.php and import_vouchers.php
 *
 * sr_load_rows($path, $ext)  ->  array of rows, each row an array of strings
 *   csv / txt : read with fgetcsv (comma or semicolon, auto-detected)
 *   xlsx      : read straight from the zip, no Composer / library needed
 *               (needs PHP's "zip" extension - it is ON by default in XAMPP)
 *
 * Throws RuntimeException with a message that is safe to show to the user.
 */

function sr_col_index(string $ref): int
{
    // "C5" -> 2 , "AA1" -> 26
    $letters = preg_replace('/[^A-Za-z]/', '', $ref);
    $n = 0;
    foreach (str_split(strtoupper($letters)) as $ch) {
        $n = $n * 26 + (ord($ch) - 64);
    }
    return max(0, $n - 1);
}

function sr_is_date_format(int $id, array $custom): bool
{
    // Built-in date / time formats
    if (($id >= 14 && $id <= 22) || ($id >= 27 && $id <= 36) || ($id >= 45 && $id <= 47) || ($id >= 50 && $id <= 58)) {
        return true;
    }
    if (!isset($custom[$id])) return false;
    $code = $custom[$id];
    $code = preg_replace('/"[^"]*"/', '', $code);      // "text"
    $code = preg_replace('/\[[^\]]*\]/', '', $code);   // [Red] [$-409]
    $code = preg_replace('/\\\\./', '', $code);        // \x
    return (bool)preg_match('/[dmyhs]/i', $code);
}

function sr_serial_to_datetime(float $n): string
{
    $days = (int)floor($n);
    $secs = (int)round(($n - $days) * 86400);
    if ($secs >= 86400) { $days++; $secs = 0; }
    if ($days < 1) return gmdate('H:i:s', $secs);               // time only
    if ($days >= 60) $days -= 1;                                 // Excel's fake 29 Feb 1900
    $dt = new DateTime('1899-12-31', new DateTimeZone('UTC'));
    $dt->modify("+{$days} days")->modify("+{$secs} seconds");
    return $dt->format('Y-m-d H:i:s');
}

function sr_xml(ZipArchive $zip, string $name): ?SimpleXMLElement
{
    $idx = $zip->locateName($name, ZipArchive::FL_NOCASE);
    if ($idx === false) return null;
    $stat = $zip->statIndex($idx);
    if ($stat && $stat['size'] > 80 * 1024 * 1024) {
        throw new RuntimeException('The Excel file is too large to read.');
    }
    $data = $zip->getFromIndex($idx);
    if ($data === false) return null;
    $xml = @simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);
    return $xml ?: null;
}

function sr_read_xlsx(string $path): array
{
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('Excel import needs the PHP "zip" extension. In XAMPP open php.ini, remove the ";" in front of extension=zip, then restart Apache.');
    }
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        throw new RuntimeException('This does not look like a valid .xlsx file. Open it in Excel and use File > Save As > Excel Workbook (.xlsx).');
    }

    try {
        /* ---- shared strings ---- */
        $shared = [];
        if ($ss = sr_xml($zip, 'xl/sharedStrings.xml')) {
            foreach ($ss->si as $si) {
                if (isset($si->t)) {
                    $shared[] = (string)$si->t;
                } else {
                    $txt = '';
                    foreach ($si->r as $r) $txt .= (string)$r->t;
                    $shared[] = $txt;
                }
            }
        }

        /* ---- which cell styles are dates ---- */
        $custom = [];
        $xfIsDate = [];
        if ($st = sr_xml($zip, 'xl/styles.xml')) {
            if (isset($st->numFmts->numFmt)) {
                foreach ($st->numFmts->numFmt as $nf) {
                    $custom[(int)$nf['numFmtId']] = (string)$nf['formatCode'];
                }
            }
            if (isset($st->cellXfs->xf)) {
                foreach ($st->cellXfs->xf as $xf) {
                    $xfIsDate[] = sr_is_date_format((int)$xf['numFmtId'], $custom);
                }
            }
        }

        /* ---- find the first worksheet ---- */
        $sheetPath = null;
        $wb   = sr_xml($zip, 'xl/workbook.xml');
        $rels = sr_xml($zip, 'xl/_rels/workbook.xml.rels');
        if ($wb && $rels && isset($wb->sheets->sheet[0])) {
            $rid = (string)$wb->sheets->sheet[0]->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
            foreach ($rels->Relationship as $rel) {
                if ((string)$rel['Id'] === $rid) {
                    $t = (string)$rel['Target'];
                    $sheetPath = ($t !== '' && $t[0] === '/') ? ltrim($t, '/') : 'xl/' . $t;
                    break;
                }
            }
        }
        $sheet = $sheetPath ? sr_xml($zip, $sheetPath) : null;
        if (!$sheet) $sheet = sr_xml($zip, 'xl/worksheets/sheet1.xml');
        if (!$sheet) throw new RuntimeException('Could not find any sheet inside the Excel file.');

        /* ---- read the cells ---- */
        $rows = [];
        if (isset($sheet->sheetData->row)) {
            foreach ($sheet->sheetData->row as $row) {
                $line = [];
                $next = 0;
                foreach ($row->c as $c) {
                    $idx = isset($c['r']) ? sr_col_index((string)$c['r']) : $next;
                    while ($next < $idx) { $line[] = ''; $next++; }

                    $type = (string)$c['t'];
                    $val  = '';
                    if ($type === 's') {
                        $val = $shared[(int)$c->v] ?? '';
                    } elseif ($type === 'inlineStr') {
                        if (isset($c->is->t)) {
                            $val = (string)$c->is->t;
                        } else {
                            foreach ($c->is->r as $r) $val .= (string)$r->t;
                        }
                    } elseif ($type === 'b') {
                        $val = ((string)$c->v === '1') ? 'TRUE' : 'FALSE';
                    } elseif ($type === 'str' || $type === 'e') {
                        $val = (string)$c->v;
                    } else {                                           // number (or date)
                        $raw = (string)$c->v;
                        if ($raw !== '' && is_numeric($raw)) {
                            $styleId = isset($c['s']) ? (int)$c['s'] : 0;
                            if (!empty($xfIsDate[$styleId])) {
                                $val = sr_serial_to_datetime((float)$raw);
                            } elseif (strpos($raw, 'E') !== false || strpos($raw, 'e') !== false) {
                                $val = rtrim(rtrim(sprintf('%.10F', (float)$raw), '0'), '.');
                            } else {
                                $val = $raw;
                            }
                        } else {
                            $val = $raw;
                        }
                    }
                    $line[] = trim($val);
                    $next++;
                }
                $rows[] = $line;
            }
        }
        return $rows;
    } finally {
        $zip->close();
    }
}

function sr_read_delimited(string $path): array
{
    $h = fopen($path, 'r');
    if (!$h) throw new RuntimeException('Could not open the file.');

    // Excel in some countries saves CSV with ";" instead of ","
    $first = (string)fgets($h);
    rewind($h);
    $delim = (substr_count($first, ';') > substr_count($first, ',')) ? ';' : ',';

    $rows = [];
    while (($r = fgetcsv($h, 0, $delim)) !== false) {
        $rows[] = array_map(fn($v) => (string)$v, $r);
    }
    fclose($h);
    return $rows;
}

function sr_load_rows(string $path, string $ext): array
{
    $ext = strtolower($ext);
    if ($ext === 'xlsx') return sr_read_xlsx($path);
    if ($ext === 'xls') {
        throw new RuntimeException('Old Excel files (.xls) are not supported. Open the file in Excel, choose File > Save As > "Excel Workbook (.xlsx)" (or CSV), and upload that instead.');
    }
    if ($ext === 'csv' || $ext === 'txt') return sr_read_delimited($path);
    throw new RuntimeException('Only .csv, .txt and .xlsx files are allowed.');
}
