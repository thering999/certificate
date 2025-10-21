<?php
/**
 * CSV/Excel Export Helper Class
 * Handles proper UTF-8 encoding for Thai text
 * 
 * @author HDC AI System
 * @version 1.0.0
 */

class DataExporter {
    
    /**
     * Export data as CSV with UTF-8 BOM
     * 
     * @param string $filename Output filename
     * @param array $headers Column headers
     * @param array $data Array of data rows
     * @return bool Success status
     */
    public static function exportCsv($filename, $headers, $data) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM - critical for Excel to recognize Thai text correctly
        fwrite($output, "\xEF\xBB\xBF");
        
        // Write headers
        fputcsv($output, $headers);
        
        // Write data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        return true;
    }
    
    /**
     * Export data as Excel XML (compatible with old Excel)
     * Better than HTML, supports proper Thai text
     * 
     * @param string $filename Output filename
     * @param string $sheetName Sheet name
     * @param array $headers Column headers with Thai labels
     * @param array $data Array of data rows
     * @return bool Success status
     */
    public static function exportExcelXml($filename, $sheetName, $headers, $data) {
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        ob_start();
        
        // Excel XML header
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<?mso-application progid="Excel.Sheet"?>';
        echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" ';
        echo 'xmlns:o="urn:schemas-microsoft-com:office:office" ';
        echo 'xmlns:x="urn:schemas-microsoft-com:office:excel" ';
        echo 'xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" ';
        echo 'xmlns:html="http://www.w3.org/TR/REC-html40">';
        
        // Document properties
        echo '<DocumentProperties xmlns="urn:schemas-microsoft-com:office:office">';
        echo '<Title>' . htmlspecialchars($sheetName, ENT_XML1, 'UTF-8') . '</Title>';
        echo '<Created>' . date('Y-m-dTH:i:sZ') . '</Created>';
        echo '</DocumentProperties>';
        
        // Styles
        echo '<Styles>';
        echo '<Style ss:ID="Default"><Alignment ss:Vertical="Bottom"/><Font ss:FontName="Tahoma" ss:Size="11"/></Style>';
        echo '<Style ss:ID="HeaderStyle"><Font ss:FontName="Tahoma" ss:Size="11" ss:Bold="1" ss:Color="FFFFFF"/>';
        echo '<Interior ss:Color="366092" ss:Pattern="Solid"/>';
        echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/></Style>';
        echo '<Style ss:ID="DataStyle"><Font ss:FontName="Tahoma" ss:Size="11"/>';
        echo '<Alignment ss:Horizontal="Left" ss:Vertical="Center"/></Style>';
        echo '</Styles>';
        
        // Worksheet
        echo '<Worksheet ss:Name="' . htmlspecialchars($sheetName, ENT_XML1, 'UTF-8') . '">';
        echo '<Table>';
        
        // Header row
        echo '<Row ss:StyleID="HeaderStyle" ss:Height="25">';
        foreach ($headers as $header) {
            echo '<Cell><Data ss:Type="String">' . htmlspecialchars($header, ENT_XML1, 'UTF-8') . '</Data></Cell>';
        }
        echo '</Row>';
        
        // Data rows
        foreach ($data as $rowData) {
            echo '<Row ss:StyleID="DataStyle">';
            foreach ($rowData as $value) {
                // Determine data type
                $type = 'String';
                if (is_numeric($value) && !preg_match('/^0/', (string)$value)) {
                    $type = 'Number';
                }
                
                echo '<Cell><Data ss:Type="' . $type . '">';
                echo htmlspecialchars((string)$value, ENT_XML1, 'UTF-8');
                echo '</Data></Cell>';
            }
            echo '</Row>';
        }
        
        echo '</Table>';
        echo '</Worksheet>';
        echo '</Workbook>';
        
        // Get buffered output
        $output = ob_get_clean();
        
        // Add UTF-8 BOM
        echo "\xEF\xBB\xBF" . $output;
        
        return true;
    }
    
    /**
     * Export data as XLSX (modern Excel format)
     * Requires PhpSpreadsheet library
     * 
     * @param string $filename Output filename
     * @param string $sheetName Sheet name
     * @param array $headers Column headers
     * @param array $data Array of data rows
     * @return bool Success status
     */
    public static function exportExcelXlsx($filename, $sheetName, $headers, $data) {
        if (!class_exists('\\PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
            // Fallback to XML if PhpSpreadsheet not available
            return self::exportExcelXml($filename, $sheetName, $headers, $data);
        }
        
        try {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle($sheetName);
            
            // Write headers
            foreach ($headers as $col => $header) {
                $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
                // Style header
                $sheet->getStyleByColumnAndRow($col + 1, 1)->getFont()->setBold(true);
                $sheet->getStyleByColumnAndRow($col + 1, 1)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FF366092');
                $sheet->getStyleByColumnAndRow($col + 1, 1)->getFont()->getColor()->setARGB('FFFFFFFF');
            }
            
            // Write data
            $row = 2;
            foreach ($data as $rowData) {
                foreach ($rowData as $col => $value) {
                    $sheet->setCellValueByColumnAndRow($col + 1, $row, $value);
                }
                $row++;
            }
            
            // Auto-size columns
            foreach (range(1, count($headers)) as $col) {
                $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
            }
            
            // Output
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            $writer->save('php://output');
            
            return true;
        } catch (Exception $e) {
            // Fallback to XML
            return self::exportExcelXml($filename, $sheetName, $headers, $data);
        }
    }
    
    /**
     * Export data as JSON with proper UTF-8 encoding
     * 
     * @param string $filename Output filename
     * @param array $data Array of data rows
     * @return bool Success status
     */
    public static function exportJson($filename, $data) {
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        return true;
    }
    
    /**
     * Convert database result to array
     * 
     * @param mysqli_result $result Database query result
     * @return array Array of rows
     */
    public static function resultToArray($result) {
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }
    
    /**
     * Get column values from array of data
     * 
     * @param array $data Array of rows
     * @param array $columns Column names to extract
     * @return array Extracted data
     */
    public static function extractColumns($data, $columns) {
        $extracted = [];
        foreach ($data as $row) {
            $newRow = [];
            foreach ($columns as $col) {
                $newRow[] = $row[$col] ?? '';
            }
            $extracted[] = $newRow;
        }
        return $extracted;
    }
    
    /**
     * Format Thai date
     * 
     * @param string $dateString Date in Y-m-d format
     * @return string Thai formatted date
     */
    public static function formatThaiDate($dateString) {
        if (empty($dateString)) {
            return '';
        }
        
        $months = [
            '01' => 'มกราคม', '02' => 'กุมภาพันธ์', '03' => 'มีนาคม',
            '04' => 'เมษายน', '05' => 'พฤษภาคม', '06' => 'มิถุนายน',
            '07' => 'กรกฎาคม', '08' => 'สิงหาคม', '09' => 'กันยายน',
            '10' => 'ตุลาคม', '11' => 'พฤศจิกายน', '12' => 'ธันวาคม'
        ];
        
        $parts = explode('-', $dateString);
        if (count($parts) === 3) {
            $year = (int)$parts[0] + 543; // Convert to Buddhist Era
            $month = $months[$parts[1]] ?? $parts[1];
            $day = (int)$parts[2];
            return "$day $month $year";
        }
        
        return $dateString;
    }
}
?>
