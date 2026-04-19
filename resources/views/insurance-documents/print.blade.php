<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>وثيقة تأمين - {{ $document->insurance_number }}</title>
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
            align-items: flex-start;
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
            text-align: center;
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
        
        .logo {
            width: 95px;
            height: 95px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .section {
            margin-bottom: 10px;
        }
        
        .section-title {
            font-size: 15px;
            font-weight: bold;
            color: #000;
            margin-bottom: 12px;
            padding-bottom: 5px;
            border-bottom: 1px solid #000;
        }
        
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 13px;
        }
        
        .info-table td {
            padding: 8px 10px;
            border: 1px solid #000;
            text-align: right;
            font-size: 13px;
            color: #000;
        }
        
        .info-table td:first-child {
            font-weight: bold;
            width: 40%;
            background: #fff;
        }
        
        .info-table td:last-child {
            width: 60%;
        }
        
        .two-column-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 11px;
        }
        
        .two-column-table td {
            padding: 5px 6px;
            border: 1px solid #000;
            text-align: right;
            font-size: 11px;
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
        
        
        .financial-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 13px;
        }
        
        .financial-table td {
            padding: 8px 10px;
            border: 1px solid #000;
            text-align: right;
            font-size: 13px;
            color: #000;
        }
        
        .financial-table td:first-child {
            font-weight: bold;
            width: 70%;
        }
        
        .financial-table td:last-child {
            width: 30%;
            text-align: left;
        }
        
        .financial-table tr:last-child td {
            font-weight: bold;
            background: #fff;
        }
        
        .company-details {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #000;
        }
        
        .company-details-left {
            flex: 1;
        }
        
        .company-details-right {
            flex: 1;
            text-align: left;
        }
        
        .company-details-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        
        .company-details-table td {
            padding: 6px 8px;
            border: 1px solid #000;
            text-align: right;
            font-size: 12px;
            color: #000;
        }
        
        .company-details-table td:first-child {
            font-weight: bold;
            width: 40%;
            background: #fff;
        }
        
        .signature-box {
            border: 2px dashed #000;
            padding: 15px;
            text-align: center;
            min-height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 10px;
        }
        
        .signature-label {
            font-weight: bold;
            color: #000;
            font-size: 13px;
        }
        
        .note-section {
            background: #fff;
            padding: 10px 12px;
            border-right: 3px solid #000;
            margin-top: 15px;
            margin-bottom: 15px;
            font-size: 12px;
            line-height: 1.6;
            color: #000;
        }
        
        .terms-section {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #000;
        }
        
        .terms-title {
            font-size: 15px;
            font-weight: bold;
            color: #000;
            margin-bottom: 10px;
        }
        
        .terms-list {
            list-style: decimal;
            padding-right: 20px;
            font-size: 11px;
            line-height: 1.8;
            color: #000;
        }
        
        .terms-list li {
            margin-bottom: 8px;
        }
        
        .jurisdiction {
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #000;
            font-size: 11px;
            line-height: 1.6;
            color: #000;
        }
        
        .footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #000;
            font-size: 12px;
            color: #000;
        }
        
        .footer div {
            display: inline-block;
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
            <div class="logo">
                <img src="/img/logo.png" alt="شعار الشركة" onerror="this.style.display='none'; this.parentElement.innerHTML='<div style=\'width:85px;height:85px;background:#000;color:#fff;display:flex;align-items:center;justify-content:center;font-size:8px;text-align:center;\'>LOGO</div>';" />
            </div>
            <div class="company-info">
                <div class="company-name">شركة المدار الليبي للتأمين</div>
                <div class="document-title">{{ $document->insurance_type ?? 'تأمين إجباري سيارات' }}</div>
             
                <div class="legal-text">
                    هذه الوثيقة صادرة وفقاً لأحكام القانون رقم ( 28 لسنة 1971م ) بشأن التأمين الاجباري من المسؤولية المدنية الناشئة من حوادث المركبات الآلية والقوانين المعدلة والقرارات.
                </div>
            </div>
            <div class="qr-code" id="qrcode"></div>
        </div>

        <!-- Document Details -->
        <div class="section">
            <table class="two-column-table">
                <tr style="vertical-align:top;">
                    <td colspan="15" style="width:480px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>بيـانــــات&nbsp;الوثيـقــــــة</nobr>
                    </td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>رقـــــم&nbsp;الوثيـقــــــــــة</nobr>
                    </td>
                    <td colspan="4" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $document->insurance_number }}</nobr>
                    </td>
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>تـــاريــــخ&nbsp;الإصـــدار</nobr>
                    </td>
                    <td colspan="7" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ \Carbon\Carbon::parse($document->issue_date)->format('d/m/Y h:i A') }}</nobr>
                    </td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>مـن&nbsp;(12:00)&nbsp;ظهرا</nobr>
                    </td>
                    <td colspan="4" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ \Carbon\Carbon::parse($document->start_date)->format('d/m/Y') }}</nobr>
                    </td>
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>إلى&nbsp;(12:00)&nbsp;ظهرا</nobr>
                    </td>
                    <td colspan="7" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $document->end_date ? \Carbon\Carbon::parse($document->end_date)->format('d/m/Y') : '-' }}</nobr>
                    </td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="6" style="width:239px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>مــدة&nbsp;التأمـــين</nobr>
                    </td>
                    <td colspan="9" style="width:239px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        {{ $printData['duration'] }}
                    </td>
                </tr>
            </table>
        </div>

        <!-- Driver and Car Details -->
        <div class="section">
            <table class="two-column-table">
                <tr style="vertical-align:top;">
                    <td colspan="15" style="width:480px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>بيانــــات&nbsp;الســــائـق&nbsp;والســــيارة</nobr>
                    </td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>اســــم&nbsp;المؤمـــــــن&nbsp;لـه</nobr>
                    </td>
                    </td>
                    <td colspan="7" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                                                <nobr>{{ $document->insured_name ?? '-' }}</nobr>

                    </td>
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>رقـــــم&nbsp;الهــــــــــــــــاتـــف</nobr>
                    <td colspan="4" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                    <nobr>{{ $document->phone ?? '-' }}</nobr>

                    </td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>رقــم&nbsp;اللوحــة&nbsp;المعدنية</nobr>

                </td>
                    <td colspan="7" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        {{ $printData['plate_number'] }}
                    </td>
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                    <nobr>قوة&nbsp;المحرك&nbsp;بالحصان</nobr>

                    </td>
                    <td colspan="4" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>
                        {{ $document->engine_power ?? '-' }}
                        </nobr>
                    </td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>رقـــــم&nbsp;الهيكـــــــــــــــل</nobr>

                </td>
                    <td colspan="7" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $document->chassis_number ?? '-' }}</nobr>

                </td>
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                    <nobr>الركاب&nbsp;المصــرح&nbsp;بهم</nobr>

                    </td>
                    <td colspan="4" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                    <nobr>{{ $document->authorized_passengers ?? '-' }}</nobr>

                    </td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>رقــم&nbsp;رخصــة&nbsp;القيــادة</nobr>

                </td>
                    <td colspan="7" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $document->driving_license_number ?? '-' }}</nobr>
                    </td>
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                    <nobr>الحمـــــــــــــولة&nbsp;بالطن</nobr>

                    </td>
                    <td colspan="4" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        {{ $printData['load_capacity'] }}
                    </td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>الغرض&nbsp;من&nbsp;الترخيص</nobr>

                </td>
                    <td colspan="7" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>
                        {{ $document->license_purpose ?? '-' }}
                        </nobr>
                    </td>
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>الـنــــــــــــــــــــــــــــــــوع</nobr>

                    </td>
                    <td colspan="4" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        {{ $printData['vehicle_type'] }}
                    </td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>الـلــــــــــــــــــــــــــــــــون</nobr>

                </td>
                    <td colspan="7" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $document->color ?? '-' }}</nobr>
                    </td>
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                    <nobr>بلـد&nbsp;الصنـــــــــــــــــــع</nobr>

                    </td>
                    <td colspan="4" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $document->manufacturing_country ?? '-' }}</nobr>
                    </td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>ســنة&nbsp;الصنــــــــــــــــع</nobr>

                </td>
                    <td colspan="7" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                    <nobr>{{ $document->year ?? '-' }}</nobr>
                    </td>
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        {{ $printData['is_customs_insurance'] ? 'الميناء' : 'الجهــــة المقيـــــــد بها' }}
                    </td>
                    <td colspan="4" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        {{ $printData['city_name'] }}
                    </td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="6" style="width:239px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>احتساب&nbsp;القسط&nbsp;-&nbsp;البيانات&nbsp;المالية</nobr>
                    </td>
                    <td colspan="9" style="width:239px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;font-weight:bold;">
                        <nobr>الشركة&nbsp;الصــادرة&nbsp;-&nbsp;معد&nbsp;الوثيقة</nobr>
                    </td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="3" style="width:125px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>تفاصيل&nbsp;الرسوم</nobr>
                    </td>
                    <td colspan="3" style="width:112px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>&nbsp;القيــمة</nobr>
                    </td>
                    <td colspan="3" style="width:90px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>&nbsp;اسم&nbsp;الوكيل</nobr>
                    </td>
                    <td colspan="4" style="width:78px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;font-weight:bold;">
                        <nobr>&nbsp;رقم&nbsp;الوكالة</nobr>
                    </td>
                    <td colspan="2" style="width:67px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>اسم&nbsp;الموظف</nobr>
                    </td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="3" style="width:125px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>قيمة&nbsp;القسط&nbsp;المقرر</nobr>
                    </td>
                    <td colspan="3" style="width:112px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ number_format($document->premium, 3) }}</nobr>
                    </td>
                    <td colspan="3" style="width:90px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $printData['agency_name'] ?? 'المدار الليبي للتأمين' }}</nobr>
                    </td>
                    <td colspan="4" style="width:78px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;font-weight:bold;">
                        <nobr>{{ $printData['agency_code'] ?? 'ML0001' }}</nobr>
                    </td>
                    <td colspan="2" style="width:67px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $printData['agent_name'] ?? ' الإدارة' }}</nobr>
                    </td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="3" style="width:125px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>الضريبــــــــــــــــــــــــة</nobr>
                    </td>
                    <td colspan="3" style="width:112px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ number_format($document->tax, 3) }}</nobr>
                    </td>
                    <td colspan="5" style="width:121px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>وقت&nbsp;الاعداد</nobr>
                    </td>
                    <td colspan="4" style="width:116px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ \Carbon\Carbon::parse($document->issue_date)->format('d/m/y H:i:s') }}</nobr>
                    </td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="3" style="width:125px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>الدمـغــــــــــــــــــــــــة</nobr>
                    </td>
                    <td colspan="3" style="width:112px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ number_format($document->stamp, 3) }}</nobr>
                    </td>
                    <td colspan="9" style="width:239px;height:12px;"></td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="3" style="width:125px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>مصاريف&nbsp;الاصـدار</nobr>
                    </td>
                    <td colspan="3" style="width:112px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ number_format($document->issue_fees, 3) }}</nobr>
                    </td>
                    <td colspan="5" style="width:121px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>التوقــيــع&nbsp;والخـــتـــم:</nobr>
                    </td>
                    <td colspan="4" style="width:116px;height:12px;"></td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="3" style="width:125px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>رســوم&nbsp;الاشــــــــراف</nobr>
                    </td>
                    <td colspan="3" style="width:112px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ number_format($document->supervision_fees, 3) }}</nobr>
                    </td>
                    <td colspan="9" style="width:239px;height:12px;"></td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="3" style="width:125px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>الاجمـــ&nbsp;رقــم&nbsp;ـــالي</nobr>
                    </td>
                    <td colspan="3" style="width:112px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ number_format($document->total, 3) }}</nobr>
                    </td>
                    <td colspan="9" style="width:239px;height:12px;"></td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="3" style="width:125px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>الاجمــ&nbsp;حروف&nbsp;ـالي</nobr>
                    </td>
                    <td colspan="12" style="width:353px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        {{ $printData['total_in_words'] }}
                    </td>
                </tr>
                @if($printData['is_customs_insurance'])
                <tr style="vertical-align:top;">
                    <td colspan="3" style="width:125px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>الملاحظة&nbsp;Note</nobr>
                    </td>
                    <td colspan="12" style="width:353px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>لا يتم التعويض في حال مزاولة المركبة الجمركية أي عمل من أعمال النقل (نقل ركاب - نقل بضائع)</nobr>
                    </td>
                </tr>
               
                </tr>
                @else
                <tr style="vertical-align:top;">
                    <td colspan="3" style="width:125px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>الملاحظة&nbsp;Note</nobr>
                    </td>
                    <td colspan="12" style="width:353px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>للتأكد&nbsp;من&nbsp;بيانات&nbsp;وثيقتك&nbsp;ادخل&nbsp;هنا&nbsp;www.mli.ly</nobr>
                    </td>
                </tr>
                @endif
            </table>
        </div>

        <!-- Terms and Conditions -->
        <div class="section">
            <table class="two-column-table" style="width: 100%; border-collapse: collapse; margin-bottom: 8px; font-size: 8px;">
                <tr style="vertical-align:top;">
                    <td colspan="15" style="width:481px;height:200px;line-height:12px;direction:rtl;text-align:right;vertical-align:top;font-size:8px;font-weight:normal;padding:4px;">
                        <nobr>شروط&nbsp;عامه</nobr><br/><nobr>1-يلتزم&nbsp;المؤمن&nbsp;بموجب&nbsp;هذه&nbsp;الوثيقة&nbsp;بتغطية&nbsp;المسؤولية&nbsp;المدنية&nbsp;الناشئة&nbsp;عن&nbsp;الوفاة&nbsp;أو&nbsp;أية&nbsp;إصابة&nbsp;بدنية&nbsp;تلحق&nbsp;بأي&nbsp;شخص&nbsp;من&nbsp;حوادث&nbsp;المركبات&nbsp;الالية&nbsp;التي&nbsp;في&nbsp;ليبيا&nbsp;المثبتة&nbsp;بياناتها&nbsp;في&nbsp;هذ&nbsp;ه&nbsp;الوثيقة&nbsp;وذالك&nbsp;عن&nbsp;مدة</nobr><br/><nobr>سريانها.</nobr><br/><nobr>ويسري&nbsp;هادا&nbsp;الالتزام&nbsp;لصالح&nbsp;الغير&nbsp;دون&nbsp;الركاب&nbsp;من&nbsp;حوادث&nbsp;المركبات&nbsp;الالية&nbsp;والدراجات&nbsp;النارية&nbsp;أيا&nbsp;كان&nbsp;نوعها&nbsp;ولصالح&nbsp;الركاب&nbsp;ايضا&nbsp;دونأعمالها&nbsp;من&nbsp;حوادث&nbsp;المركبات&nbsp;الالية&nbsp;التالية&nbsp;:</nobr><br/><nobr>•&nbsp;سيارات&nbsp;الركوبة&nbsp;العامة&nbsp;(سيارات&nbsp;الاجرة&nbsp;).</nobr><br/><nobr>•&nbsp;حافلات&nbsp;النقل&nbsp;العام&nbsp;للركاب&nbsp;والمركبات&nbsp;المقطورة&nbsp;الملحقة&nbsp;بها&nbsp;.</nobr><br/><nobr>•&nbsp;حافلات&nbsp;النقل&nbsp;الخاص&nbsp;او&nbsp;حافلات&nbsp;نل&nbsp;الركاب&nbsp;رحلات&nbsp;سياحية&nbsp;والمركبات&nbsp;الملحقة&nbsp;بها&nbsp;.</nobr><br/><nobr>•&nbsp;سيارات&nbsp;الإسعاف&nbsp;والمستشفيات&nbsp;.</nobr><br/><nobr>•&nbsp;سياراتنقل&nbsp;البضائع&nbsp;فيما&nbsp;يخصص&nbsp;الركاب&nbsp;المصرح&nbsp;بنقلهمويسري&nbsp;هدا&nbsp;التامين&nbsp;علي&nbsp;السيارات&nbsp;الخاصةوالدرجات&nbsp;النارية&nbsp;لصالح&nbsp;الغير&nbsp;دون&nbsp;الركاب&nbsp;وعلي&nbsp;باقي&nbsp;انواع&nbsp;المركبات&nbsp;الالية&nbsp;لصال&nbsp;الغير&nbsp;والركاب&nbsp;ما&nbsp;ويتبر</nobr><br/><nobr>الشخص&nbsp;راكبا&nbsp;سواء&nbsp;داخل&nbsp;المركبة&nbsp;او&nbsp;صاعدا&nbsp;اليها&nbsp;او&nbsp;نازلا&nbsp;منها&nbsp;ولايشمل&nbsp;التامين&nbsp;عمال&nbsp;المركبة&nbsp;المثبتة&nbsp;بياناتها&nbsp;في&nbsp;هده&nbsp;الوثيقة&nbsp;.</nobr><br/><nobr>2-&nbsp;يلتزم&nbsp;المؤمن&nbsp;بدفعالتعويض&nbsp;عن&nbsp;الاضرار&nbsp;المادية&nbsp;والمعنوية&nbsp;التي&nbsp;تلح&nbsp;بالأشخاص&nbsp;من&nbsp;حوادث&nbsp;المركبة&nbsp;الالية&nbsp;المؤمنة&nbsp;بموجب&nbsp;هذ&nbsp;الوثيقة&nbsp;وديا&nbsp;او&nbsp;قضائيا&nbsp;وذالك&nbsp;بقيمة&nbsp;محددة&nbsp;لا&nbsp;تتجاوزالحدالأقصىالمنصوص</nobr><br/><nobr>عليه&nbsp;بقرار&nbsp;اللجنة&nbsp;الشعبية&nbsp;العامة&nbsp;رقم&nbsp;(&nbsp;213&nbsp;لسنة&nbsp;2003&nbsp;)&nbsp;والقرارات&nbsp;المعدلة&nbsp;او&nbsp;البديلة&nbsp;له&nbsp;.</nobr><br/><nobr>3-&nbsp;يستحق&nbsp;التعويض&nbsp;عن&nbsp;الاضرار&nbsp;المادية&nbsp;والمعنوية&nbsp;للمصاب&nbsp;شخصيا&nbsp;في&nbsp;حال&nbsp;الاصابة&nbsp;الجسدية&nbsp;.والاب&nbsp;والام&nbsp;والزوج&nbsp;والاولاد&nbsp;دون&nbsp;غيرهم&nbsp;في&nbsp;حالة&nbsp;الوفاة&nbsp;ويؤدي&nbsp;التعويض&nbsp;لهم&nbsp;مرة&nbsp;واحدة&nbsp;ويوزع&nbsp;بينهم&nbsp;وفقا</nobr><br/><nobr>للقواعد&nbsp;التي&nbsp;تقررها&nbsp;المحكمة&nbsp;بحسب&nbsp;الضرر&nbsp;الذي&nbsp;لحق&nbsp;بكل&nbsp;واحد&nbsp;منهم&nbsp;.</nobr><br/><nobr>4-&nbsp;تقسط&nbsp;دعوى&nbsp;المضرور&nbsp;قبل&nbsp;المؤمن&nbsp;بانقضاء&nbsp;تلاتة&nbsp;سنوات&nbsp;من&nbsp;تاريخ&nbsp;صدور&nbsp;حكم&nbsp;نهائي&nbsp;بثبوت&nbsp;مسؤولية&nbsp;المؤمن&nbsp;له&nbsp;عن&nbsp;الحادث&nbsp;او&nbsp;الواقعة&nbsp;المسببة&nbsp;للضرر&nbsp;.</nobr><br/><nobr>5-&nbsp;لا&nbsp;يجوز&nbsp;للمؤمن&nbsp;له&nbsp;تقديم&nbsp;او&nbsp;قبول&nbsp;أي&nbsp;عرض&nbsp;فيما&nbsp;يختص&nbsp;بتعويض&nbsp;المضرور&nbsp;دون&nbsp;موافقة&nbsp;المؤمن&nbsp;كتابة&nbsp;ولا&nbsp;تعتبر&nbsp;أية&nbsp;تسوية&nbsp;بين&nbsp;المؤمن&nbsp;له&nbsp;والمضرور&nbsp;حجة&nbsp;قبل&nbsp;المؤمن&nbsp;اذا&nbsp;تمت&nbsp;دون&nbsp;موافقته&nbsp;.</nobr><br/><nobr>6-&nbsp;لا&nbsp;يجوز&nbsp;للمؤمن&nbsp;ولا&nbsp;للمؤمن&nbsp;له&nbsp;او&nbsp;يلغي&nbsp;وثيقة&nbsp;التامين&nbsp;اثناء&nbsp;مدة&nbsp;سريانها&nbsp;مادام&nbsp;الترخيص&nbsp;للمركبة&nbsp;قائما&nbsp;وفي&nbsp;حالة&nbsp;الغاء&nbsp;وثيقة&nbsp;التامين&nbsp;قبل&nbsp;انتهاء&nbsp;مدة&nbsp;سريانها&nbsp;عند&nbsp;انتاء&nbsp;الترخيص&nbsp;او&nbsp;تقديم&nbsp;وثيقة&nbsp;تامين</nobr><br/><nobr>جديدة&nbsp;بسبب&nbsp;تغيير&nbsp;بيانات&nbsp;المركبة&nbsp;او&nbsp;نقل&nbsp;قيد&nbsp;ملكيتها,&nbsp;يجب&nbsp;علي&nbsp;المؤمن&nbsp;ان&nbsp;يرد&nbsp;للمؤمن&nbsp;له&nbsp;جزء&nbsp;من&nbsp;باي&nbsp;قسط&nbsp;يتناسب&nbsp;والمدة&nbsp;من&nbsp;فترة&nbsp;التامين&nbsp;بشرط&nbsp;تقديم&nbsp;وثيقة&nbsp;للتامين&nbsp;مؤشرا&nbsp;عليها&nbsp;بما&nbsp;يفيد&nbsp;اعادتها&nbsp;الي</nobr><br/><nobr>المؤمن&nbsp;له&nbsp;من&nbsp;مكتب&nbsp;الترخيص&nbsp;المختص&nbsp;,&nbsp;وتصبح&nbsp;الوثيقة&nbsp;ملغاة&nbsp;من&nbsp;تاريخ&nbsp;التأشير&nbsp;وللمؤمن&nbsp;ان&nbsp;يستنزل&nbsp;مصروفات&nbsp;الوثيقة&nbsp;بما&nbsp;لا&nbsp;تتجاوز&nbsp;(10%)&nbsp;عشرة&nbsp;بالمائة&nbsp;من&nbsp;قيمة&nbsp;القسط&nbsp;.</nobr><br/><nobr>&nbsp;7-&nbsp;يجب&nbsp;لي&nbsp;المؤمن&nbsp;ل&nbsp;أي&nbsp;يتخدد&nbsp;كافة&nbsp;الاحتياطات&nbsp;المعقولة&nbsp;للمحافظة&nbsp;علي&nbsp;المركبة&nbsp;في&nbsp;حالة&nbsp;صالحة&nbsp;للاستعمال&nbsp;ويجوز&nbsp;للمؤمن&nbsp;التحقق&nbsp;من&nbsp;ذلك&nbsp;دون&nbsp;اعتراض&nbsp;المؤمن&nbsp;له&nbsp;:&nbsp;وعلي&nbsp;المؤمن&nbsp;له&nbsp;اخطار</nobr><br/><nobr>المؤمنوالسلطاتالمختصة&nbsp;(72)&nbsp;ساعة&nbsp;من&nbsp;وقتعلمه&nbsp;او&nbsp;علم&nbsp;من&nbsp;ينوب&nbsp;عنه&nbsp;عن&nbsp;حالات&nbsp;فقدد&nbsp;المركبة&nbsp;او&nbsp;ووقوع&nbsp;حادت&nbsp;منها&nbsp;نشأت&nbsp;عنه&nbsp;&nbsp;الوفاة&nbsp;او&nbsp;الاصابة&nbsp;البدنية&nbsp;او&nbsp;مطالبته&nbsp;بالتعويض&nbsp;الناشئ&nbsp;ن&nbsp;الوفاة&nbsp;او&nbsp;الإصابة</nobr><br/><nobr>البدنية&nbsp;ويجب&nbsp;عليه&nbsp;ايضا&nbsp;ان&nbsp;يقدم&nbsp;للمؤمن&nbsp;جميع&nbsp;الخطابات&nbsp;والمطالبات&nbsp;والانذارات&nbsp;واعلانات&nbsp;الدعاوي&nbsp;بمجرد&nbsp;تسليمها.</nobr><br/><nobr>8-&nbsp;يجوز&nbsp;للمؤمن&nbsp;ان&nbsp;يراجع&nbsp;للمؤمن&nbsp;له&nbsp;بقيمة&nbsp;ما&nbsp;يكون&nbsp;قد&nbsp;اداه&nbsp;من&nbsp;تعويض&nbsp;في&nbsp;الحالات&nbsp;الاتية&nbsp;:</nobr><br/><nobr>اذا&nbsp;تبث&nbsp;ان&nbsp;التامين&nbsp;قد&nbsp;عقد&nbsp;بناء&nbsp;علي&nbsp;ادلاء&nbsp;المؤمن&nbsp;له&nbsp;ببيانات&nbsp;كاذبة&nbsp;او&nbsp;اخفاء&nbsp;وقائع&nbsp;جوهرية&nbsp;تؤثر&nbsp;في&nbsp;حكم&nbsp;المؤمن&nbsp;علي&nbsp;قبوله&nbsp;تغطية&nbsp;الخطر&nbsp;او&nbsp;علي&nbsp;سعر&nbsp;التامين&nbsp;او&nbsp;شروطه</nobr><br/><nobr>&nbsp;استعمال&nbsp;المركب&nbsp;في&nbsp;غير&nbsp;الغرض&nbsp;المبين&nbsp;بترخيص&nbsp;او&nbsp;قبول&nbsp;ركاب&nbsp;او&nbsp;وضع&nbsp;حمولة&nbsp;اكتر&nbsp;من&nbsp;المقرر&nbsp;لها&nbsp;او&nbsp;استعمالها&nbsp;في&nbsp;السباق&nbsp;او&nbsp;اختباراتالسرعة</nobr><br/><nobr>اذا&nbsp;كان&nbsp;قائد&nbsp;المركبة&nbsp;سواء&nbsp;مؤمن&nbsp;له&nbsp;او&nbsp;شخص&nbsp;اخر&nbsp;يقودها&nbsp;بموافقته&nbsp;غير&nbsp;حائز&nbsp;على&nbsp;رخصه&nbsp;قياده&nbsp;لنوع&nbsp;المركبة&nbsp;او&nbsp;سحبت&nbsp;رخصته&nbsp;بموجب&nbsp;حكم&nbsp;جنائي</nobr><br/><nobr>اذا&nbsp;ثبت&nbsp;ان&nbsp;قائد&nbsp;المركبة&nbsp;–&nbsp;سواء&nbsp;كان&nbsp;المؤمن&nbsp;له&nbsp;او&nbsp;شخص&nbsp;اخر&nbsp;سمح&nbsp;له&nbsp;بقيادتها&nbsp;–&nbsp;ارتكب&nbsp;الحادث&nbsp;وهو&nbsp;في&nbsp;غير&nbsp;حالته&nbsp;الطبيعية&nbsp;بسبب&nbsp;سكر&nbsp;او&nbsp;تناول&nbsp;مخدرات</nobr><br/><nobr>اذا&nbsp;ثبت&nbsp;ان&nbsp;الوفاه&nbsp;او&nbsp;الإصابة&nbsp;البدنية&nbsp;&nbsp;فقد&nbsp;نشأت&nbsp;عن&nbsp;عمل&nbsp;ارتكبه&nbsp;المؤمن&nbsp;له&nbsp;عن&nbsp;اراده&nbsp;وعمد&nbsp;وسبق&nbsp;اصرار</nobr><br/><nobr>9-لا&nbsp;يترتب&nbsp;على&nbsp;حق&nbsp;الرجوع&nbsp;المقرر&nbsp;للمؤمن&nbsp;طبقا&nbsp;لأحكام&nbsp;القانون&nbsp;والشروط&nbsp;الواردة&nbsp;بهذا&nbsp;لوثي&nbsp;أي&nbsp;مساس&nbsp;بحق&nbsp;المضرور&nbsp;&nbsp;قبله&nbsp;.</nobr><br/><nobr>10-&nbsp;لا&nbsp;يتحمل&nbsp;المؤمن&nbsp;ايه&nbsp;مسؤوليه&nbsp;تقع&nbsp;بطريقه&nbsp;مباشر&nbsp;او&nbsp;غير&nbsp;مباشر&nbsp;عللا&nbsp;الاشعاعات&nbsp;الذري&nbsp;ا&nbsp;الانفجارات</nobr><br/><nobr>الاختصاص&nbsp;القضائي</nobr><br/><nobr>من&nbsp;المتفق&nbsp;عليه&nbsp;ان&nbsp;كل&nbsp;ما&nbsp;ينشئ&nbsp;من&nbsp;منازعات&nbsp;بصدد&nbsp;هذا&nbsp;العقد&nbsp;او&nbsp;بحصوص&nbsp;تنفيذه&nbsp;يكون&nbsp;من&nbsp;اختصاص&nbsp;المحاكم&nbsp;الوطنية&nbsp;التي&nbsp;يتبع&nbsp;لها&nbsp;المركز&nbsp;الرئيسي&nbsp;للشركة&nbsp;.&nbsp;وفي&nbsp;جميع&nbsp;الاحوال&nbsp;فان&nbsp;النص&nbsp;العربي&nbsp;لهذه</nobr><br/><nobr>الوثيقة&nbsp;وملاحقها.&nbsp;هو&nbsp;الواجب&nbsp;التطبيق</nobr>
                    </td>
                </tr>
            </table>
        </div>

   
     
    </div>

    @php
        $qrDataJson = json_encode($printData['qr_data']);
    @endphp
    <script>
        // إنشاء QR code يحتوي على بيانات الوثيقة - محسّن للأداء
        (function() {
            const qrData = {!! $qrDataJson !!};
            const qrText = JSON.stringify(qrData);
            const qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=85x85&data=' + encodeURIComponent(qrText);
            const qrContainer = document.getElementById('qrcode');
            if (qrContainer) {
                qrContainer.innerHTML = '<img src="' + qrApiUrl + '" alt="QR Code" style="width: 85px; height: 85px; display: block;" />';
            }
        })();

        // التفعيل التلقائي للطباعة عند التحميل
        window.onload = function() {
            // انتظار بسيط للتأكد من تحميل الـ QR code
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>

