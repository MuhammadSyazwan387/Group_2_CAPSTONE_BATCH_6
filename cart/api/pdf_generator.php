<?php
// PDF Generator for Voucher Redemption
require_once '../../config.php';

class VoucherPDFGenerator {
    private $tcpdf;
    
    public function __construct() {
        // Check if TCPDF is available
        if (!class_exists('TCPDF')) {
            // If TCPDF is not installed, we'll use a simple HTML to PDF approach
            // For production, install TCPDF: composer require tecnickcom/tcpdf
            throw new Exception('TCPDF library not found. Please install TCPDF or use alternative PDF generation.');
        }
        
        $this->tcpdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $this->setupPDF();
    }
    
    private function setupPDF() {
        // Set document information
        $this->tcpdf->SetCreator('Optima Bank');
        $this->tcpdf->SetAuthor('Optima Bank Voucher System');
        $this->tcpdf->SetTitle('Voucher Redemption Receipt');
        $this->tcpdf->SetSubject('Digital Voucher');
        
        // Set default header data
        $this->tcpdf->SetHeaderData('', 0, 'Optima Bank', 'Voucher Redemption Receipt');
        
        // Set header and footer fonts
        $this->tcpdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $this->tcpdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
        
        // Set default monospaced font
        $this->tcpdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        
        // Set margins
        $this->tcpdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $this->tcpdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $this->tcpdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        
        // Set auto page breaks
        $this->tcpdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        
        // Set image scale factor
        $this->tcpdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        
        // Set font
        $this->tcpdf->SetFont('helvetica', '', 10);
    }
    
    public function generateVoucherPDF($voucherData, $userData, $redemptionData) {
        // Add a page
        $this->tcpdf->AddPage();
        
        // Generate PDF content
        $html = $this->generateHTMLContent($voucherData, $userData, $redemptionData);
        
        // Write HTML content
        $this->tcpdf->writeHTML($html, true, false, true, false, '');
        
        return $this->tcpdf->Output('', 'S'); // Return PDF as string
    }
    
    private function generateHTMLContent($voucher, $user, $redemption) {
        $redemptionDate = date('F j, Y \a\t g:i A', strtotime($redemption['checkout_date']));
        $redemptionId = 'VR-' . date('Ymd') . '-' . str_pad($user['id'], 4, '0', STR_PAD_LEFT) . '-' . $voucher['id'];
        
        return '
        <style>
            .header { text-align: center; margin-bottom: 30px; }
            .voucher-box { border: 2px solid #ff4da6; padding: 20px; margin: 20px 0; border-radius: 10px; }
            .detail-row { margin: 10px 0; }
            .label { font-weight: bold; color: #333; }
            .value { color: #666; }
            .footer { text-align: center; margin-top: 30px; font-size: 10px; color: #999; }
            .qr-section { text-align: center; margin: 20px 0; }
        </style>
        
        <div class="header">
            <h1 style="color: #ff4da6;">Optima Bank</h1>
            <h2>Voucher Redemption Receipt</h2>
        </div>
        
        <div class="voucher-box">
            <h3 style="color: #ff4da6; text-align: center;">' . htmlspecialchars($voucher['voucher_name']) . '</h3>
            
            <div class="detail-row">
                <span class="label">Redemption ID:</span> 
                <span class="value">' . $redemptionId . '</span>
            </div>
            
            <div class="detail-row">
                <span class="label">Customer:</span> 
                <span class="value">' . htmlspecialchars($user['fullname']) . '</span>
            </div>
            
            <div class="detail-row">
                <span class="label">Email:</span> 
                <span class="value">' . htmlspecialchars($user['email']) . '</span>
            </div>
            
            <div class="detail-row">
                <span class="label">Redemption Date:</span> 
                <span class="value">' . $redemptionDate . '</span>
            </div>
            
            <div class="detail-row">
                <span class="label">Points Used:</span> 
                <span class="value">' . number_format($redemption['points_spent']) . ' points</span>
            </div>
            
            <div class="detail-row">
                <span class="label">Remaining Points:</span> 
                <span class="value">' . number_format($redemption['remaining_points']) . ' points</span>
            </div>
        </div>
        
        ' . (!empty($voucher['terms_and_condition']) ? '
        <div style="margin: 20px 0;">
            <h4>Terms & Conditions:</h4>
            <p style="font-size: 9px; line-height: 1.4;">' . nl2br(htmlspecialchars($voucher['terms_and_condition'])) . '</p>
        </div>
        ' : '') . '
        
        <div class="qr-section">
            <p><strong>Voucher Code: ' . $redemptionId . '</strong></p>
            <p style="font-size: 10px;">Present this receipt when using your voucher</p>
        </div>
        
        <div class="footer">
            <p>Thank you for choosing Optima Bank!</p>
            <p>This is a digitally generated receipt.</p>
            <p>For support, contact: optimabankonlinestore@gmail.com</p>
        </div>
        ';
    }
}

// Alternative simple PDF generator without TCPDF
class SimplePDFGenerator {
    public static function generateSimpleReceipt($voucher, $user, $redemption) {
        $redemptionDate = date('F j, Y \a\t g:i A', strtotime($redemption['checkout_date']));
        $redemptionId = 'VR-' . date('Ymd') . '-' . str_pad($user['id'], 4, '0', STR_PAD_LEFT) . '-' . $voucher['voucher_id'];
        
        // Simple HTML content that can be converted to PDF using browser print
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Voucher Receipt</title>
            <style>
                body { font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #ff4da6; padding-bottom: 20px; }
                .voucher-box { border: 2px solid #ff4da6; padding: 20px; margin: 20px 0; border-radius: 10px; background: #f9f9f9; }
                .detail-row { margin: 15px 0; display: flex; justify-content: space-between; }
                .label { font-weight: bold; color: #333; }
                .value { color: #666; }
                .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #999; border-top: 1px solid #ddd; padding-top: 20px; }
                .qr-section { text-align: center; margin: 20px 0; background: #fff; padding: 15px; border: 1px dashed #ff4da6; }
                h1 { color: #ff4da6; margin: 0; }
                h2 { color: #333; margin: 10px 0; }
                h3 { color: #ff4da6; text-align: center; margin-bottom: 20px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Optima Bank</h1>
                <h2>Voucher Redemption Receipt</h2>
            </div>
            
            <div class="voucher-box">
                <h3>' . htmlspecialchars($voucher['voucher_name']) . '</h3>
                
                <div class="detail-row">
                    <span class="label">Redemption ID:</span> 
                    <span class="value">' . $redemptionId . '</span>
                </div>
                
                <div class="detail-row">
                    <span class="label">Customer:</span> 
                    <span class="value">' . htmlspecialchars($user['fullname']) . '</span>
                </div>
                
                <div class="detail-row">
                    <span class="label">Email:</span> 
                    <span class="value">' . htmlspecialchars($user['email']) . '</span>
                </div>
                
                <div class="detail-row">
                    <span class="label">Redemption Date:</span> 
                    <span class="value">' . $redemptionDate . '</span>
                </div>
                
                <div class="detail-row">
                    <span class="label">Points Used:</span> 
                    <span class="value">' . number_format($redemption['points_spent']) . ' points</span>
                </div>
                
                <div class="detail-row">
                    <span class="label">Remaining Points:</span> 
                    <span class="value">' . number_format($redemption['remaining_points']) . ' points</span>
                </div>
            </div>
            
            <div class="qr-section">
                <p><strong>Voucher Code: ' . $redemptionId . '</strong></p>
                <p>Present this receipt when using your voucher</p>
            </div>
            
            <div class="footer">
                <p>Thank you for choosing Optima Bank!</p>
                <p>This is a digitally generated receipt.</p>
                <p>For support, contact: optimabankonlinestore@gmail.com</p>
            </div>
            
            <script>
                // Auto print when loaded
                window.onload = function() {
                    setTimeout(function() {
                        window.print();
                    }, 500);
                };
            </script>
        </body>
        </html>';
        
        return $html;
    }
    
    public static function generateCheckoutReceipt($order_items, $user, $redemption_data) {
        $redemptionDate = date('F j, Y \a\t g:i A', strtotime($redemption_data['checkout_date']));
        $redemptionId = 'CO-' . date('Ymd') . '-' . str_pad($user['id'], 4, '0', STR_PAD_LEFT);
        
        // Generate items HTML
        $itemsHtml = '';
        foreach ($order_items as $item) {
            $itemsHtml .= '
                <tr>
                    <td>' . htmlspecialchars($item['voucher_name']) . '</td>
                    <td style="text-align: center;">' . $item['quantity'] . '</td>
                    <td style="text-align: right;">' . number_format($item['points_per_item']) . '</td>
                    <td style="text-align: right;">' . number_format($item['total_points']) . '</td>
                </tr>';
        }
        
        // Simple HTML content for checkout receipt
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Checkout Receipt</title>
            <style>
                body { font-family: Arial, sans-serif; max-width: 700px; margin: 0 auto; padding: 20px; }
                .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #ff4da6; padding-bottom: 20px; }
                .receipt-box { border: 2px solid #ff4da6; padding: 20px; margin: 20px 0; border-radius: 10px; background: #f9f9f9; }
                .detail-row { margin: 15px 0; display: flex; justify-content: space-between; }
                .label { font-weight: bold; color: #333; }
                .value { color: #666; }
                .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #999; border-top: 1px solid #ddd; padding-top: 20px; }
                .items-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                .items-table th, .items-table td { padding: 10px; border-bottom: 1px solid #ddd; }
                .items-table th { background: #ff4da6; color: white; text-align: left; }
                .total-row { font-weight: bold; background: #f0f0f0; }
                h1 { color: #ff4da6; margin: 0; }
                h2 { color: #333; margin: 10px 0; }
                h3 { color: #ff4da6; text-align: center; margin-bottom: 20px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Optima Bank</h1>
                <h2>Checkout Receipt</h2>
            </div>
            
            <div class="receipt-box">
                <h3>Order Summary</h3>
                
                <div class="detail-row">
                    <span class="label">Order ID:</span> 
                    <span class="value">' . $redemptionId . '</span>
                </div>
                
                <div class="detail-row">
                    <span class="label">Customer:</span> 
                    <span class="value">' . htmlspecialchars($user['fullname']) . '</span>
                </div>
                
                <div class="detail-row">
                    <span class="label">Email:</span> 
                    <span class="value">' . htmlspecialchars($user['email']) . '</span>
                </div>
                
                <div class="detail-row">
                    <span class="label">Checkout Date:</span> 
                    <span class="value">' . $redemptionDate . '</span>
                </div>
                
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Voucher</th>
                            <th style="text-align: center;">Qty</th>
                            <th style="text-align: right;">Points Each</th>
                            <th style="text-align: right;">Total Points</th>
                        </tr>
                    </thead>
                    <tbody>
                        ' . $itemsHtml . '
                        <tr class="total-row">
                            <td colspan="3"><strong>Total</strong></td>
                            <td style="text-align: right;"><strong>' . number_format($redemption_data['total_points']) . ' points</strong></td>
                        </tr>
                    </tbody>
                </table>
                
                <div class="detail-row">
                    <span class="label">Total Items:</span> 
                    <span class="value">' . $redemption_data['total_items'] . '</span>
                </div>
                
                <div class="detail-row">
                    <span class="label">Points Used:</span> 
                    <span class="value">' . number_format($redemption_data['points_spent']) . ' points</span>
                </div>
                
                <div class="detail-row">
                    <span class="label">Remaining Points:</span> 
                    <span class="value">' . number_format($redemption_data['remaining_points']) . ' points</span>
                </div>
            </div>
            
            <div class="footer">
                <p>Thank you for choosing Optima Bank!</p>
                <p>This is a digitally generated receipt.</p>
                <p>All vouchers are now available in your history for use.</p>
                <p>For support, contact: optimabankonlinestore@gmail.com</p>
            </div>
            
            <script>
                // Auto print when loaded
                window.onload = function() {
                    setTimeout(function() {
                        window.print();
                    }, 500);
                };
            </script>
        </body>
        </html>';
        
        return $html;
    }
}
?>
