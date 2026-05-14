<?php
require_once __DIR__ . '/../tcpdf/tcpdf.php';

/**
 * Custom PDF Report Generator
 * Beautifully styled reports with dynamic company info
 */
class PDFReport extends TCPDF {
    
    private $company_name;
    private $company_address;
    private $company_phone;
    private $company_email;
    private $report_title;
    private $report_date;
    private $filter_info;
    private $has_filter;
    
    public function __construct($title = 'Report', $orientation = 'L', $filter_info = '') {
        parent::__construct($orientation, 'mm', 'A4', true, 'UTF-8', false);
        
        $this->company_name = companyName();
        $this->company_address = companyAddress();
        $this->company_phone = companyPhone();
        $this->company_email = companyEmail();
        $this->report_title = $title;
        $this->report_date = date('M d, Y h:i A');
        $this->filter_info = $filter_info;
        $this->has_filter = !empty($filter_info);
        
        // Set document information
        $this->SetCreator($this->company_name);
        $this->SetAuthor($this->company_name);
        $this->SetTitle($title);
        $this->SetSubject($title);
        
        // Remove default header/footer
        $this->setPrintHeader(true);
        $this->setPrintFooter(true);
        
        // Set margins - add more top margin if filter is present
        $topMargin = $this->has_filter ? 30 : 22;
        $this->SetMargins(12, $topMargin, 12);
        $this->SetHeaderMargin(5);
        $this->SetFooterMargin(12);
        
        // Set auto page breaks
        $this->SetAutoPageBreak(true, 22);
        
        // Set font
        $this->SetFont('helvetica', '', 10);
    }
    
    /**
     * Custom header with company branding
     */
    public function Header() {
        // Header background
        $this->SetFillColor(44, 62, 80);
        $this->SetTextColor(255, 255, 255);
        $this->Rect(12, 8, $this->GetPageWidth() - 24, 16, 'F');
        
        // Company Name
        $this->SetFont('helvetica', 'B', 14);
        $this->SetXY(15, 9);
        $this->Cell(90, 6, $this->company_name, 0, 0, 'L');
        
        // Report Title
        $this->SetFont('helvetica', 'B', 10);
        $this->SetXY(15, 16);
        $this->Cell(90, 5, $this->report_title, 0, 0, 'L');
        
        // Contact Info on right side
        $this->SetFont('helvetica', '', 6);
        $this->SetTextColor(220, 220, 220);
        $this->SetXY($this->GetPageWidth() - 130, 9);
        $this->Cell(118, 4, $this->company_address, 0, 1, 'R');
        $this->SetX($this->GetPageWidth() - 130);
        $this->Cell(118, 4, 'Tel: ' . $this->company_phone . ' | Email: ' . $this->company_email, 0, 1, 'R');
        
        // Reset colors
        $this->SetTextColor(51, 51, 51);
        
        // Filter info bar (if filter is applied)
        if ($this->has_filter) {
            $this->SetY(26);
            $this->SetFont('helvetica', 'I', 8);
            $this->SetTextColor(255, 255, 255);
            $this->SetFillColor(142, 68, 173);
            $this->Cell(0, 6, '  Filter Applied: ' . $this->filter_info, 0, 1, 'L', true);
            $this->SetTextColor(51, 51, 51);
            $this->SetY(34);
        } else {
            $this->SetY(28);
        }
        
        // Accent line
        $this->SetDrawColor(52, 152, 219);
        $this->SetLineWidth(0.5);
        $this->Line(12, $this->GetY(), $this->GetPageWidth() - 12, $this->GetY());
        $this->Ln(4);
    }
    
    /**
     * Custom footer
     */
    public function Footer() {
        $this->SetY(-15);
        
        // Accent line
        $this->SetDrawColor(52, 152, 219);
        $this->SetLineWidth(0.5);
        $this->Line(12, $this->GetY(), $this->GetPageWidth() - 12, $this->GetY());
        $this->Ln(2);
        
        // Footer text
        $this->SetFont('helvetica', 'I', 7);
        $this->SetTextColor(127, 140, 141);
        $this->Cell(0, 4, 'Generated: ' . $this->report_date . ' | Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'L');
        $this->Cell(0, 4, $this->company_name . ' - Confidential', 0, 0, 'R');
    }
    
    /**
     * Add a summary box
     */
    public function addSummaryBox($title, $items, $columns = 2) {
        // Title
        $this->SetFont('helvetica', 'B', 12);
        $this->SetTextColor(44, 62, 80);
        $this->Cell(0, 8, $title, 0, 1, 'L');
        
        // Background for summary
        $startY = $this->GetY();
        $itemCount = count($items);
        $rows = ceil($itemCount / $columns);
        $boxHeight = ($rows * 7) + 6;
        
        $this->SetFillColor(236, 240, 241);
        $this->Rect(12, $startY, $this->GetPageWidth() - 24, $boxHeight, 'F');
        
        $this->SetY($startY + 2);
        
        $w = ($this->GetPageWidth() - 36) / $columns;
        $count = 0;
        
        foreach ($items as $label => $value) {
            $x = 18 + ($count % $columns) * $w;
            $y = $startY + 3 + floor($count / $columns) * 7;
            
            $this->SetXY($x, $y);
            $this->SetFont('helvetica', 'B', 9);
            $this->SetTextColor(44, 62, 80);
            $this->Cell(5, 6, '', 0, 0);
            $this->Cell($w * 0.4, 6, $label . ':', 0, 0, 'L');
            
            $this->SetFont('helvetica', '', 9);
            $this->SetTextColor(52, 73, 94);
            $this->Cell($w * 0.55, 6, $value, 0, 0, 'L');
            
            $count++;
        }
        
        $this->SetY($startY + $boxHeight + 4);
    }
    
    /**
     * Add a beautifully styled table
     */
    public function addStyledTable($headers, $data, $widths = null, $title = '') {
        if ($title) {
            $this->SetFont('helvetica', 'B', 11);
            $this->SetTextColor(44, 62, 80);
            $this->Cell(0, 7, $title, 0, 1, 'L');
            $this->Ln(1);
        }
        
        $num_headers = count($headers);
        if (!$widths) {
            $widths = array_fill(0, $num_headers, ($this->GetPageWidth() - 24) / $num_headers);
        }
        
        // Table header
        $this->SetFont('helvetica', 'B', 8);
        $this->SetFillColor(44, 62, 80);
        $this->SetTextColor(255, 255, 255);
        $this->SetDrawColor(44, 62, 80);
        $this->SetLineWidth(0.3);
        
        for ($i = 0; $i < $num_headers; $i++) {
            $this->Cell($widths[$i], 8, '  ' . $headers[$i], 1, 0, 'L', true);
        }
        $this->Ln();
        
        // Table rows
        $fill = false;
        $totalRows = count($data);
        $currentRow = 0;
        
        foreach ($data as $row) {
            $currentRow++;
            $isLastRow = ($currentRow == $totalRows);
            
            if ($isLastRow) {
                $this->SetFillColor(44, 62, 80);
                $this->SetTextColor(255, 255, 255);
                $this->SetFont('helvetica', 'B', 8);
            } else {
                if ($fill) {
                    $this->SetFillColor(245, 247, 250);
                } else {
                    $this->SetFillColor(255, 255, 255);
                }
                $this->SetTextColor(51, 51, 51);
                $this->SetFont('helvetica', '', 8);
            }
            
            $this->SetDrawColor(230, 230, 230);
            
            for ($i = 0; $i < $num_headers; $i++) {
                $value = isset($row[$i]) ? $row[$i] : '';
                $align = ($i == 0) ? 'L' : 'L';
                $this->Cell($widths[$i], 7, '  ' . $value, 'LR', 0, $align, true);
            }
            $this->Ln();
            $fill = !$fill;
        }
        
        // Bottom border
        $this->SetDrawColor(44, 62, 80);
        $this->SetLineWidth(0.5);
        $this->Cell(array_sum($widths), 0, '', 'T');
        $this->Ln(5);
    }
}