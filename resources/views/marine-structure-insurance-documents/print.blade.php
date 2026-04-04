<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>وثيقة تأمين الهياكل البحرية - {{ $document->insurance_number }}</title>
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
            padding: 4px 6px;
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
                <div class="document-title">تأمين الهياكل البحرية</div>
                <div class="legal-text">
                    هذه الوثيقة خاضعة لأحكام القانون رقم (3) لسنة 2005م
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
                        <nobr>{{ $printData['insurance_number'] }}</nobr>
                    </td>
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>تـــاريــــخ&nbsp;الإصـــدار</nobr>
                    </td>
                    <td colspan="7" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $printData['issue_date'] }}</nobr>
                    </td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>مـن&nbsp;(12:00)&nbsp;ظهرا</nobr>
                    </td>
                    <td colspan="4" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $printData['start_date'] }}</nobr>
                    </td>
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>إلى&nbsp;(12:00)&nbsp;ظهرا</nobr>
                    </td>
                    <td colspan="7" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $printData['end_date'] }}</nobr>
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

        <!-- Driver and Vessel Details -->
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
                    <td colspan="7" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $printData['insured_name'] }}</nobr>
                    </td>
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>رقـــــم&nbsp;الهــــــــــــــــاتـــف</nobr>
                    </td>
                    <td colspan="4" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $printData['phone'] }}</nobr>
                    </td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>اسم&nbsp;المركب&nbsp;/&nbsp;الهيكل</nobr>
                    </td>
                    <td colspan="7" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $printData['vessel_name'] }}</nobr>
                    </td>
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>رقــم&nbsp;رخصــة&nbsp;القيــادة</nobr>
                    </td>
                    <td colspan="4" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $printData['license_number'] }}</nobr>
                    </td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>الغرض&nbsp;من&nbsp;الترخيص</nobr>
                    </td>
                    <td colspan="7" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $printData['license_purpose'] }}</nobr>
                    </td>
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>رقــم&nbsp;اللوحــة&nbsp;المعدنية</nobr>
                    </td>
                    <td colspan="4" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $printData['plate_number'] }}</nobr>
                    </td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>بلـد&nbsp;الصنـــــــــــــــــــع</nobr>
                    </td>
                    <td colspan="7" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $printData['manufacturing_country'] }}</nobr>
                    </td>
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>قوة&nbsp;المحرك&nbsp;بالحصان</nobr>
                    </td>
                    <td colspan="4" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $printData['engine_horsepower_display'] }}</nobr>
                    </td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>الـلــــــــــــــــــــــــــــــــون</nobr>
                    </td>
                    <td colspan="7" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $printData['color'] }}</nobr>
                    </td>
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>الركاب&nbsp;المصــرح&nbsp;بهم</nobr>
                    </td>
                    <td colspan="4" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $printData['passenger_count'] }}</nobr>
                    </td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>الجهــــة&nbsp;المقيـــــــد&nbsp;بها</nobr>
                    </td>
                    <td colspan="7" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        {{ $printData['registration_authority'] }}
                    </td>
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>رقـــــم&nbsp;الهيكـــــــــــــــل</nobr>
                    </td>
                    <td colspan="4" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $printData['hull_number'] }}</nobr>
                    </td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>الميناء&nbsp;او&nbsp;المرفأ</nobr>
                    </td>
                    <td colspan="7" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $printData['port'] }}</nobr>
                    </td>
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>الحمـــــــــــــولة&nbsp;بالطن</nobr>
                    </td>
                    <td colspan="4" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $printData['load_capacity'] }}</nobr>
                    </td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>نوع&nbsp;مواد&nbsp;التصنيع</nobr>
                    </td>
                    <td colspan="7" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $printData['manufacturing_material'] }}</nobr>
                    </td>
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>سعة&nbsp;خزان&nbsp;الوقود</nobr>
                    </td>
                    <td colspan="4" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $printData['fuel_tank_capacity'] }}</nobr>
                    </td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>ســنة&nbsp;الصنــــــــــــــــع</nobr>
                    </td>
                    <td colspan="7" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $printData['manufacturing_year'] }}</nobr>
                    </td>
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>الحجم</nobr>
                    </td>
                    <td colspan="4" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $printData['size'] }}</nobr>
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
                        <nobr>{{ $printData['premium'] }}</nobr>
                    </td>
                    <td colspan="3" style="width:90px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $printData['agency_name'] ?? 'المدار الليبي للتأمين' }}</nobr>
                    </td>
                    <td colspan="4" style="width:78px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;font-weight:bold;">
                        <nobr>{{ $printData['agency_code'] ?? 'ML0001' }}</nobr>
                    </td>
                    <td colspan="2" style="width:67px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $printData['agent_name'] ?? 'محمد علي' }}</nobr>
                    </td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="3" style="width:125px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>الضريبــــــــــــــــــــــــة</nobr>
                    </td>
                    <td colspan="3" style="width:112px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $printData['tax'] }}</nobr>
                    </td>
                    <td colspan="5" style="width:121px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>وقت&nbsp;الاعداد</nobr>
                    </td>
                    <td colspan="4" style="width:116px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $printData['prepared_at'] }}</nobr>
                    </td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="3" style="width:125px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>الدمـغــــــــــــــــــــــــة</nobr>
                    </td>
                    <td colspan="3" style="width:112px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $printData['stamp'] }}</nobr>
                    </td>
                    <td colspan="5" style="width:121px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>التوقــيــع&nbsp;والخـــتـــم:</nobr>
                    </td>
                    <td colspan="4" style="width:116px;height:12px;"></td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="3" style="width:125px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>مصاريف&nbsp;الاصـدار</nobr>
                    </td>
                    <td colspan="3" style="width:112px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $printData['issue_fees'] }}</nobr>
                    </td>
                    <td colspan="9" style="width:239px;height:12px;"></td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="3" style="width:125px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>رســوم&nbsp;الاشــــــــراف</nobr>
                    </td>
                    <td colspan="3" style="width:112px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $printData['supervision_fees'] }}</nobr>
                    </td>
                    <td colspan="9" style="width:239px;height:12px;"></td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="3" style="width:125px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>الاجمـــ&nbsp;رقــم&nbsp;ـــالي</nobr>
                    </td>
                    <td colspan="3" style="width:112px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $printData['total'] }}</nobr>
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
                <tr style="vertical-align:top;">
                    <td colspan="3" style="width:125px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>الملاحظة&nbsp;Note</nobr>
                    </td>
                    <td colspan="12" style="width:353px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>للتأكد&nbsp;من&nbsp;بيانات&nbsp;وثيقتك&nbsp;ادخل&nbsp;هنا&nbsp;www.mli.ly</nobr>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Terms and Conditions -->
        <div class="section">
        <table class="two-column-table" style="width: 100%; border-collapse: collapse; margin-bottom: 8px; font-size:10px;">
        <tr style="vertical-align:top;">
        <td colspan="15" style="width:481px;height:200px;line-height:12px;direction:rtl;text-align:right;vertical-align:top;font-size:10px;font-weight:normal;padding:4px;">
        <nobr>تغطي&nbsp;وثيقة&nbsp;الهياكل&nbsp;البحريه&nbsp;الأضرار&nbsp;الجسدية&nbsp;للطرف&nbsp;الثالث:</nobr><br/><nobr>التغطية&nbsp;الأساسية&nbsp;التي&nbsp;تحمي&nbsp;ضد&nbsp;الإصابة&nbsp;الجسدية&nbsp;التي&nbsp;قد&nbsp;تسببها&nbsp;لشخص&nbsp;آخر.&nbsp;يوفر&nbsp;الغطاء&nbsp;تعويضات&nbsp;للطرف&nbsp;(أو&nbsp;أطراف)&nbsp;الثالث&nbsp;من&nbsp;جراء&nbsp;إصابات&nbsp;بدنية&nbsp;أو&nbsp;وفاة&nbsp;(بما&nbsp;في&nbsp;ذلك <br>&nbsp;النفقات&nbsp;الطبية)&nbsp;نتيجة&nbsp;لحادث&nbsp;االمسؤول&nbsp;عنها.&nbsp;ولا&nbsp;تشمل&nbsp;تغطية:</nobr><br/><nobr>1-&nbsp;الأضرار&nbsp;المادية&nbsp;التي&nbsp;لحقت&nbsp;الطرف&nbsp;الثالث&nbsp;.</nobr><br/><nobr>2-&nbsp;الإصابات&nbsp;الجسدية&nbsp;أو&nbsp;الوفاة&nbsp;لصاحب&nbsp;البوليصة&nbsp;أو&nbsp;لأفراد&nbsp;أسرته&nbsp;أو&nbsp;السائق.تم&nbsp;تصميم&nbsp;هذه&nbsp;الوثيقه&nbsp;لتتوافق&nbsp;مع&nbsp;التأمين&nbsp;الإلزامي&nbsp;على&nbsp;السيارات.</nobr>
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
    </script>
</body>
</html>
