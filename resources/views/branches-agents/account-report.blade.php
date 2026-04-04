<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>كشف حساب الوكيل - {{ $branchAgent->agency_name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        @page {
            size: A4;
            margin: 12mm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Tajawal', 'Arial', 'Tahoma', sans-serif;
            font-size: 12px;
            color: #000;
            background: #fff;
            padding: 0;
            line-height: 1.5;
        }
        
        .document-container {
            max-width: 100%;
            margin: 0 auto;
            background: #fff;
            padding: 10px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            padding-bottom: 10px;
            border-bottom: 2px solid #000;
        }
        
        .qr-code {
            width: 85px;
            height: 85px;
            background: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 8px;
            text-align: center;
            flex-shrink: 0;
        }
        
        .company-info {
            text-align: right;
            flex: 1;
            padding: 0 12px;
        }
        
        .company-name {
            font-size: 19px;
            font-weight: bold;
            color: #000;
            margin-bottom: 4px;
        }
        
        .document-title {
            font-size: 17px;
            color: #000;
            font-weight: 700;
            margin-bottom: 6px;
        }
        
        .legal-text {
            font-size: 10px;
            color: #000;
            line-height: 1.4;
            margin-top: 4px;
        }
        
        .agent-info-box {
            background: #f0f0f0;
            padding: 8px 12px;
            margin-top: 8px;
            margin-bottom: 8px;
            border-radius: 4px;
            text-align: center;
            font-size: 11px;
        }
        
        .logo {
            width: 95px;
            height: 95px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            min-width: 95px;
            min-height: 95px;
            position: relative;
        }
        
        .logo img {
            width: 95px;
            height: 95px;
            object-fit: contain;
            display: block;
            max-width: 95px;
            max-height: 95px;
        }
        
        .logo-placeholder {
            width: 95px;
            height: 95px;
            background: #000;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8px;
            text-align: center;
            flex-shrink: 0;
            position: absolute;
            top: 0;
            left: 0;
        }
        
        .section {
            margin-bottom: 10px;
        }
        
        .two-column-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 10px;
        }
        
        .two-column-table td {
            padding: 4px 5px;
            border: 1px solid #000;
            text-align: right;
            font-size: 10px;
            color: #000;
        }
        
        .two-column-table tr:first-child td,
        .two-column-table tr:nth-child(2) td {
            width: 25%;
        }
        
        .two-column-table tr:last-child td:first-child {
            width: 25%;
        }
        
        .two-column-table tr:last-child td[colspan="3"] {
            width: 75%;
        }
        
        .two-column-table td:nth-child(odd) {
            font-weight: bold;
            background: #fff;
        }
        
        @media print {
            body {
                padding: 0;
                margin: 0;
            }
            
            .document-container {
                max-width: 100%;
                padding: 0;
            }
            
            .no-print {
                display: none;
            }
            
            @page {
                margin: 12mm;
            }
        }
    </style>
</head>
<body>
    <div class="document-container">
        <!-- Header -->
        <div class="header">
            <div class="company-info">
                <div class="company-name">شركة المدار الليبي للتأمين</div>
                <div class="document-title">
                    @if($type === 'monthly' && $year && $month)
                        @php
                            $monthNames = [
                                1 => 'يناير',
                                2 => 'فبراير',
                                3 => 'مارس',
                                4 => 'أبريل',
                                5 => 'مايو',
                                6 => 'يونيو',
                                7 => 'يوليو',
                                8 => 'أغسطس',
                                9 => 'سبتمبر',
                                10 => 'أكتوبر',
                                11 => 'نوفمبر',
                                12 => 'ديسمبر'
                            ];
                            $monthName = $monthNames[$month] ?? $month;
                        @endphp
                        إغلاق حساب شهري للوكيل - {{ $monthName }} - {{ $month }}
                    @else
                        إغلاق حساب كامل للوكيل
                    @endif
                </div>
            </div>
            <div class="logo">
                <img src="/img/logo.png" alt="شعار الشركة" onerror="this.style.display='none'; this.parentElement.innerHTML='<div class=\'logo-placeholder\'>LOGO</div>';" />
            </div>
        </div>
        
        <!-- Agent Info Table -->
        <div class="section">
            <table class="two-column-table" style="width: 100%; margin-bottom: 8px;">
                <tr style="vertical-align:top;">
                    <td colspan="1" style="width:50%;height:14px;line-height:13px;direction:rtl;text-align:center;vertical-align:middle;background:#f0f0f0;padding:8px;font-weight:bold;font-size:13px;">
                        <nobr>اسم الوكيل</nobr>
                    </td>
                    <td colspan="1" style="width:50%;height:14px;line-height:13px;direction:rtl;text-align:center;vertical-align:middle;background:#f0f0f0;padding:8px;font-weight:bold;font-size:13px;">
                        <nobr>رقم الختم</nobr>
                    </td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="1" style="width:50%;height:14px;line-height:13px;direction:rtl;text-align:center;vertical-align:middle;padding:8px;font-weight:normal;font-size:13px;">
                        <nobr>{{ $branchAgent->agency_name }} - {{ $branchAgent->agent_name }}</nobr>
                    </td>
                    <td colspan="1" style="width:50%;height:14px;line-height:13px;direction:rtl;text-align:center;vertical-align:middle;padding:8px;font-weight:normal;font-size:13px;">
                        <nobr>{{ $branchAgent->stamp_number ?? '-' }}</nobr>
                    </td>
                </tr>
            </table>
        </div>

        @php
            $counter = 1;
            // ترتيب الفئات حسب المثال المطلوب
            $categoryOrder = [
                'تأمين الحوادث الشخصيه' => 'فئة تأمين الحوادث الشخصيه',
                'تأمين السيارات' => 'فئة تامين السيارات',
                'تأمين السيارات دولي' => 'فئة تأمين السيارات دولي',
                'تأمين المسافرين' => 'فئة تأمين المسافرين',
                'تأمين الهياكل البحرية' => 'فئة تأمين الهياكل البحرية',
                'تأمين الوافدين' => 'فئة تأمين الوافدين',
                'تأمين المسؤولية المهنية' => 'فئة تأمين المسؤولية المهنية'
            ];
        @endphp

        @foreach($categoryOrder as $categoryKey => $categoryLabel)
            @if(isset($documentsByCategory[$categoryKey]) && count($documentsByCategory[$categoryKey]) > 0)
                <div class="section">
                    <table class="two-column-table">
                        <tr style="vertical-align:top;">
                            <td colspan="10" style="width:480px;height:14px;line-height:13px;direction:rtl;text-align:right;vertical-align:middle;font-size:12px;font-weight:bold;padding:6px;">
                                <nobr>{{ $categoryLabel }}</nobr>
                            </td>
                        </tr>
                        <tr style="vertical-align:top;">
                            <td colspan="1" style="width:40px;height:11px;line-height:10px;direction:rtl;text-align:center;vertical-align:middle;font-size:10px;font-weight:bold;">
                                <nobr>#</nobr>
                            </td>
                            <td colspan="1" style="width:80px;height:11px;line-height:10px;direction:rtl;text-align:center;vertical-align:middle;font-size:10px;font-weight:bold;">
                                <nobr>الفئة</nobr>
                            </td>
                            <td colspan="1" style="width:80px;height:11px;line-height:10px;direction:rtl;text-align:center;vertical-align:middle;font-size:10px;font-weight:bold;">
                                <nobr>اسم المؤمن</nobr>
                            </td>
                            <td colspan="1" style="width:60px;height:11px;line-height:10px;direction:rtl;text-align:center;vertical-align:middle;font-size:10px;font-weight:bold;">
                                <nobr>رقم الهاتف</nobr>
                            </td>
                            <td colspan="1" style="width:70px;height:11px;line-height:10px;direction:rtl;text-align:center;vertical-align:middle;font-size:10px;font-weight:bold;">
                                <nobr>كود التأمين</nobr>
                            </td>
                            <td colspan="1" style="width:60px;height:11px;line-height:10px;direction:rtl;text-align:center;vertical-align:middle;font-size:10px;font-weight:bold;">
                                <nobr>نسبة الوكيل</nobr>
                            </td>
                            <td colspan="1" style="width:70px;height:11px;line-height:10px;direction:rtl;text-align:center;vertical-align:middle;font-size:10px;font-weight:bold;">
                                <nobr>القيمة للوكيل</nobr>
                            </td>
                            <td colspan="1" style="width:70px;height:11px;line-height:10px;direction:rtl;text-align:center;vertical-align:middle;font-size:10px;font-weight:bold;">
                                <nobr>القيمة للشركة</nobr>
                            </td>
                            <td colspan="1" style="width:70px;height:11px;line-height:10px;direction:rtl;text-align:center;vertical-align:middle;font-size:10px;font-weight:bold;">
                                <nobr>قيمة التأمين</nobr>
                            </td>
                            <td colspan="1" style="width:60px;height:11px;line-height:10px;direction:rtl;text-align:center;vertical-align:middle;font-size:10px;font-weight:bold;">
                                <nobr>التاريخ</nobr>
                            </td>
                        </tr>
                        @foreach($documentsByCategory[$categoryKey] as $doc)
                        <tr style="vertical-align:top;">
                            <td colspan="1" style="width:40px;height:11px;line-height:10px;direction:rtl;text-align:center;vertical-align:middle;font-size:10px;font-weight:normal;">
                                <nobr>{{ $counter++ }}</nobr>
                            </td>
                            <td colspan="1" style="width:80px;height:11px;line-height:10px;direction:rtl;text-align:center;vertical-align:middle;font-size:10px;font-weight:normal;">
                                <nobr>{{ $doc['category'] }}</nobr>
                            </td>
                            <td colspan="1" style="width:80px;height:11px;line-height:10px;direction:rtl;text-align:center;vertical-align:middle;font-size:10px;font-weight:normal;">
                                <nobr>{{ $doc['insured_name'] }}</nobr>
                            </td>
                            <td colspan="1" style="width:60px;height:11px;line-height:10px;direction:rtl;text-align:center;vertical-align:middle;font-size:10px;font-weight:normal;">
                                <nobr>{{ $doc['phone'] }}</nobr>
                            </td>
                            <td colspan="1" style="width:70px;height:11px;line-height:10px;direction:rtl;text-align:center;vertical-align:middle;font-size:10px;font-weight:normal;">
                                <nobr>{{ $doc['document_number'] }}</nobr>
                            </td>
                            <td colspan="1" style="width:60px;height:11px;line-height:10px;direction:rtl;text-align:center;vertical-align:middle;font-size:10px;font-weight:normal;">
                                <nobr>{{ number_format($doc['percentage'], 2) }}%</nobr>
                            </td>
                            <td colspan="1" style="width:70px;height:11px;line-height:10px;direction:rtl;text-align:center;vertical-align:middle;font-size:10px;font-weight:normal;">
                                <nobr>{{ number_format($doc['agent_amount'], 3) }}</nobr>
                            </td>
                            <td colspan="1" style="width:70px;height:11px;line-height:10px;direction:rtl;text-align:center;vertical-align:middle;font-size:10px;font-weight:normal;">
                                <nobr>{{ number_format($doc['company_amount'], 3) }}</nobr>
                            </td>
                            <td colspan="1" style="width:70px;height:11px;line-height:10px;direction:rtl;text-align:center;vertical-align:middle;font-size:10px;font-weight:normal;">
                                <nobr>{{ number_format($doc['total'], 3) }}</nobr>
                            </td>
                            <td colspan="1" style="width:60px;height:11px;line-height:10px;direction:rtl;text-align:center;vertical-align:middle;font-size:10px;font-weight:normal;">
                                <nobr>{{ $doc['date'] ? \Carbon\Carbon::parse($doc['date'])->format('d/m/Y') : '-' }}</nobr>
                            </td>
                        </tr>
                        @endforeach
                    </table>
                </div>
            @endif
        @endforeach

        <!-- Summary Table - القيم والنسب والمجموع -->
        <div class="section">
            <table class="two-column-table">
                <tr style="vertical-align:top;">
                    <td colspan="1" style="width:96px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;font-weight:bold;">
                        <nobr>القيمة للشركة</nobr>
                    </td>
                    <td colspan="1" style="width:96px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;font-weight:bold;">
                        <nobr>القيمة للوكيل</nobr>
                    </td>
                    <td colspan="1" style="width:96px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;font-weight:bold;">
                        <nobr>نسبة الشركة</nobr>
                    </td>
                    <td colspan="1" style="width:96px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;font-weight:bold;">
                        <nobr>نسبة الوكيل</nobr>
                    </td>
                    <td colspan="1" style="width:96px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;font-weight:bold;">
                        <nobr>المجموع</nobr>
                    </td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="1" style="width:96px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;font-weight:normal;">
                        <nobr>{{ number_format($totalCompanyAmount, 3) }}</nobr>
                    </td>
                    <td colspan="1" style="width:96px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;font-weight:normal;">
                        <nobr>{{ number_format($totalAmount, 3) }}</nobr>
                    </td>
                    <td colspan="1" style="width:96px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;font-weight:normal;">
                        <nobr>{{ number_format($companyPercentage, 2) }}%</nobr>
                    </td>
                    <td colspan="1" style="width:96px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;font-weight:normal;">
                        <nobr>{{ number_format($agentPercentage, 2) }}%</nobr>
                    </td>
                    <td colspan="1" style="width:96px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;font-weight:normal;">
                        <nobr>{{ number_format($grandTotal, 3) }}</nobr>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Summary Table - المتبقي والمدفوع والمستحقة -->
        <div class="section">
            <table class="two-column-table">
                <tr style="vertical-align:top;">
                    <td colspan="1" style="width:120px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;font-weight:bold;">
                        <nobr>المتبقي</nobr>
                    </td>
                    <td colspan="1" style="width:120px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;font-weight:normal;">
                        <nobr>{{ number_format($remainingAmount, 3) }}</nobr>
                    </td>
                    <td colspan="1" style="width:120px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;font-weight:bold;">
                        <nobr>المدفوع</nobr>
                    </td>
                    <td colspan="1" style="width:120px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;font-weight:normal;">
                        <nobr>{{ number_format($paidAmount, 3) }}</nobr>
                    </td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="1" style="width:120px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;font-weight:bold;">
                        <nobr>القيمة المستحقة</nobr>
                    </td>
                    <td colspan="3" style="width:360px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;font-weight:normal;">
                        <nobr>{{ number_format($dueAmount, 3) }}</nobr>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Footer -->
        <div class="section">
            <table class="two-column-table" style="width: 100%; border-collapse: collapse; margin-bottom: 8px; font-size: 11px; border: none;">
                <tr style="vertical-align:top;">
                    <td colspan="1" style="width:100%;height:auto;line-height:12px;direction:rtl;text-align:left;vertical-align:middle;font-size:11px;padding:4px;border:none;">
                        <nobr>{{ \Carbon\Carbon::now()->format('d-m-Y | H:i:s') }}</nobr>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
