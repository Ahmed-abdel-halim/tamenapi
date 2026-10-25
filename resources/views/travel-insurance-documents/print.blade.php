@php
    $mainPassenger = $document->passengers->where('is_main_passenger', true)->first();
    $familyMembers = $document->passengers->where('is_main_passenger', false);
    
    // Parse duration
    $durationDays = 0;
    if ($document->duration) {
        if (preg_match('/(\d+)/', $document->duration, $matches)) {
            $durationDays = (int)$matches[1];
        }
    }
    
    // QR Code verification data
    $qrDataJson = json_encode([
        'policy' => $document->insurance_number,
        'insured' => $mainPassenger->name_ar ?? ($mainPassenger->name_en ?? ''),
        'passport' => $mainPassenger->passport_number ?? '',
        'start' => \Carbon\Carbon::parse($document->start_date)->format('Y-m-d'),
        'end' => \Carbon\Carbon::parse($document->end_date)->format('Y-m-d'),
        'total' => $document->total,
        'status' => 'ACTIVE'
    ]);
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>وثيقة تأمين المسافرين - {{ $document->insurance_number }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #005a9c;
            --primary-dark: #003b66;
            --primary-light: #e6f0fa;
            --accent: #0284c7;
            --text-dark: #0f172a;
            --text-muted: #475569;
            --border-color: #cbd5e1;
            --bg-table-head: #005a9c;
            --bg-subtle: #f8fafc;
            --badge-green: #059669;
        }

        /* Ink Saver / Eco Mode (توفير الحبر) */
        body.eco-mode {
            --primary: #1e293b;
            --primary-dark: #0f172a;
            --primary-light: #f8fafc;
            --accent: #334155;
            --bg-table-head: #334155;
            --badge-green: #1e293b;
        }
        body.eco-mode .header-banner {
            background: #fff !important;
            color: #0f172a !important;
            border: 2px solid #0f172a !important;
            box-shadow: none !important;
        }
        body.eco-mode .header-banner h1,
        body.eco-mode .header-banner h2 {
            color: #0f172a !important;
        }
        body.eco-mode .header-banner .plane-icon {
            color: #0f172a !important;
        }
        body.eco-mode .section-header {
            background: #e2e8f0 !important;
            color: #0f172a !important;
            border-bottom: 2px solid #64748b !important;
        }
        body.eco-mode .benefits-card-header {
            background: #e2e8f0 !important;
            color: #0f172a !important;
        }
        body.eco-mode .benefits-card-header .limit-badge {
            background: #0f172a !important;
            color: #fff !important;
        }
        body.eco-mode th {
            background: #f1f5f9 !important;
            color: #0f172a !important;
            border: 1px solid #94a3b8 !important;
        }
        body.eco-mode .emergency-pill {
            background: #f1f5f9 !important;
            color: #0f172a !important;
            border: 2px solid #0f172a !important;
        }
        body.eco-mode .emergency-phone {
            color: #0f172a !important;
        }
        body.eco-mode .info-card-header.blue,
        body.eco-mode .info-card-header.navy,
        body.eco-mode .info-card-header.teal {
            background: #e2e8f0 !important;
            color: #0f172a !important;
            border-bottom: 1px solid #94a3b8 !important;
        }

        /* Pre-printed stationery mode (إخفاء الترويسة والفوتر عند الطباعة على ورق مروّس مسبقاً) */
        body.stationery-mode .print-header-area {
            visibility: hidden !important;
        }
        body.stationery-mode .print-footer-area {
            visibility: hidden !important;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        @page {
            size: A4 portrait;
            margin: 4mm 6mm;
        }

        body {
            font-family: 'Tajawal', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            font-size: 8px;
            color: var(--text-dark);
            background: #eef2f6;
            line-height: 1.25;
            direction: rtl;
        }

        /* Floating Interactive Action Bar */
        .action-bar {
            position: fixed;
            top: 12px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(15, 23, 42, 0.94);
            backdrop-filter: blur(12px);
            padding: 8px 18px;
            border-radius: 9999px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.35), 0 8px 10px -6px rgba(0, 0, 0, 0.3);
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 9999;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .action-btn {
            background: #fff;
            color: #0f172a;
            border: none;
            padding: 6px 14px;
            font-family: inherit;
            font-size: 11px;
            font-weight: 700;
            border-radius: 9999px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .action-btn-primary {
            background: linear-gradient(135deg, #0284c7, #005a9c);
            color: #fff;
        }
        .action-btn.active {
            background: #059669;
            color: #fff;
        }
        .action-bar .divider {
            width: 1px;
            height: 22px;
            background: rgba(255, 255, 255, 0.25);
        }
        .action-bar span.label {
            color: #e2e8f0;
            font-size: 11px;
            font-weight: 600;
        }

        /* Printable Sheet Layout */
        .page-sheet {
            width: 210mm;
            min-height: 297mm;
            margin: 55px auto 20px auto;
            background: #fff;
            padding: 5mm 7mm;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
            position: relative;
            box-sizing: border-box;
            border-radius: 2px;
        }

        /* Top Header */
        .print-header-area {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 3px;
            border-bottom: 2px solid #005a9c;
            margin-bottom: 4px;
            min-height: 68px;
        }
        .header-logo-center {
            text-align: center;
            flex: 1;
        }
        .company-title-ar {
            font-size: 15px;
            font-weight: 900;
            color: #005a9c;
            letter-spacing: 0.3px;
        }
        .company-title-en {
            font-size: 8.5px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 0.5px;
            margin-top: 1px;
        }
        .company-slogan {
            font-size: 7.5px;
            font-weight: 600;
            color: #0284c7;
            margin-top: 1px;
        }
        .header-art-left {
            width: 135px;
            text-align: right;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .header-art-left img {
            max-height: 52px;
            object-fit: contain;
        }
        .header-qr-right {
            width: 135px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 6px;
        }
        .qr-box {
            width: 58px;
            height: 58px;
            background: #fff;
            border: 1px solid #cbd5e1;
            padding: 2px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .qr-box canvas, .qr-box img {
            width: 100% !important;
            height: 100% !important;
        }
        .qr-text {
            font-size: 6.5px;
            color: #475569;
            line-height: 1.15;
            text-align: left;
            max-width: 65px;
            direction: ltr;
        }

        /* Header Title Banner */
        .header-banner {
            background: linear-gradient(135deg, #003b66 0%, #005a9c 50%, #0284c7 100%);
            color: #fff;
            text-align: center;
            padding: 3px 12px;
            border-radius: 4px;
            margin-bottom: 4px;
            box-shadow: 0 2px 4px rgba(0, 90, 156, 0.15);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .header-banner .plane-icon {
            font-size: 13px;
            opacity: 0.9;
        }
        .header-banner h1 {
            font-size: 12.5px;
            font-weight: 900;
            margin: 0;
            line-height: 1.1;
        }
        .header-banner h2 {
            font-size: 8px;
            font-weight: 700;
            letter-spacing: 1.2px;
            margin-top: 1px;
            opacity: 0.95;
        }

        /* Meta 5-boxes row */
        .meta-strip {
            display: grid;
            grid-template-columns: 1.1fr 1fr 1fr 1fr 1.3fr;
            gap: 4px;
            margin-bottom: 4px;
        }
        .meta-card {
            border: 1px solid #93c5fd;
            border-radius: 3px;
            background: #f0f7ff;
            padding: 2px 4px;
            text-align: center;
        }
        .meta-card .meta-title {
            font-size: 7px;
            font-weight: 700;
            color: #005a9c;
            display: block;
            line-height: 1.1;
        }
        .meta-card .meta-subtitle {
            font-size: 5.8px;
            color: #64748b;
            display: block;
            margin-bottom: 1px;
        }
        .meta-card .meta-val {
            font-size: 8px;
            font-weight: 800;
            color: #0f172a;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Passenger & Trip Details Card */
        .section-box {
            border: 1px solid #93c5fd;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 4px;
            background: #fff;
        }
        .section-header {
            background: #005a9c;
            color: #fff;
            padding: 2.5px 8px;
            font-size: 8px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            padding: 3px 6px;
            gap: 2px 10px;
            font-size: 7.8px;
            background: #fbfdff;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5px 0;
            border-bottom: 1px dashed #e2e8f0;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            color: #475569;
            font-weight: 600;
            font-size: 7.2px;
        }
        .detail-label span.en {
            font-size: 6px;
            color: #94a3b8;
            display: block;
            line-height: 1;
        }
        .detail-value {
            font-weight: 800;
            color: #0f172a;
            font-size: 7.8px;
            text-align: left;
            direction: ltr;
        }
        .detail-value.rtl-val {
            direction: rtl;
            text-align: left;
        }

        /* Family members mini-table if any */
        .family-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.2px;
            margin-top: 2px;
            border-top: 1px solid #93c5fd;
        }
        .family-table th {
            background: #e0f2fe;
            color: #0369a1;
            padding: 1.5px 4px;
            text-align: right;
            font-weight: 700;
            font-size: 6.8px;
            border: 1px solid #bae6fd;
        }
        .family-table td {
            padding: 1.5px 4px;
            border: 1px solid #e2e8f0;
            font-weight: 600;
        }

        /* Benefits Table */
        .benefits-card {
            border: 1px solid #005a9c;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 4px;
        }
        .benefits-card-header {
            background: linear-gradient(90deg, #005a9c 0%, #0284c7 100%);
            color: #fff;
            padding: 2.5px 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .benefits-card-header .title {
            font-size: 8px;
            font-weight: 800;
        }
        .benefits-card-header .limit-badge {
            background: #fff;
            color: #005a9c;
            padding: 1.5px 6px;
            border-radius: 3px;
            font-size: 7.2px;
            font-weight: 800;
        }
        .benefits-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.2px;
            line-height: 1.15;
        }
        .benefits-table th {
            background: #f1f5f9;
            color: #003b66;
            font-weight: 800;
            padding: 2px 4px;
            text-align: center;
            border: 1px solid #cbd5e1;
            font-size: 6.8px;
        }
        .benefits-table td {
            padding: 1.8px 4px;
            border: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        .benefits-table tr:nth-child(even) td {
            background: #f8fafc;
        }
        .col-num {
            width: 20px;
            text-align: center;
            font-weight: 800;
            color: #005a9c;
        }
        .col-benefit {
            text-align: right;
            padding-right: 6px !important;
        }
        .col-benefit .ar {
            font-weight: 800;
            color: #0f172a;
            display: block;
            font-size: 7.2px;
        }
        .col-benefit .en {
            font-size: 6.2px;
            color: #64748b;
            font-weight: 600;
            direction: ltr;
            text-align: right;
        }
        .col-limit {
            width: 95px;
            text-align: center;
            font-weight: 800;
            color: #005a9c;
            font-size: 7.2px;
            white-space: nowrap;
        }
        .col-notes {
            width: 120px;
            text-align: center;
            font-size: 6.2px;
            color: #475569;
            font-weight: 600;
        }
        .col-notes .ar {
            display: block;
            font-weight: 700;
        }
        .col-notes .en {
            display: block;
            font-size: 5.8px;
            color: #94a3b8;
        }

        /* 3-Column Bottom Info Grid */
        .bottom-grid {
            display: grid;
            grid-template-columns: 1.18fr 0.94fr 0.88fr;
            gap: 4px;
            margin-bottom: 4px;
        }
        .info-card {
            border: 1px solid #94a3b8;
            border-radius: 4px;
            background: #fff;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .info-card-header {
            padding: 2px 6px;
            font-size: 7.2px;
            font-weight: 800;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .info-card-header.blue { background: #005a9c; }
        .info-card-header.navy { background: #003b66; }
        .info-card-header.teal { background: #0284c7; }

        .info-card-body {
            padding: 3px 5px;
            font-size: 6.5px;
            line-height: 1.18;
            color: #1e293b;
            flex: 1;
        }
        .conditions-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .conditions-list li {
            position: relative;
            padding-right: 11px;
            margin-bottom: 2px;
        }
        .conditions-list li:last-child {
            margin-bottom: 0;
        }
        .conditions-list li .num {
            position: absolute;
            right: 0;
            top: 0;
            font-weight: 800;
            color: #005a9c;
            font-size: 6.5px;
        }
        .conditions-list li .ar-cond {
            font-weight: 700;
            display: block;
        }
        .conditions-list li .en-cond {
            font-size: 5.6px;
            color: #64748b;
            display: block;
            direction: ltr;
            text-align: right;
        }

        .bullet-list {
            list-style: none;
            padding: 0;
            margin: 0 0 2px 0;
        }
        .bullet-list li {
            position: relative;
            padding-right: 8px;
            margin-bottom: 1.2px;
            font-size: 6.3px;
            font-weight: 600;
        }
        .bullet-list li::before {
            content: "•";
            position: absolute;
            right: 0;
            color: #0284c7;
            font-weight: 800;
        }
        .declaration-box {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            padding: 2px 4px;
            border-radius: 3px;
            font-size: 6px;
            margin-top: 2px;
            line-height: 1.15;
        }
        .declaration-box strong {
            color: #005a9c;
        }

        /* Emergency Assistance Card */
        .emergency-pill {
            background: #005a9c;
            color: #fff;
            padding: 2.5px 6px;
            border-radius: 4px;
            text-align: center;
            margin: 2px 0 2.5px 0;
        }
        .emergency-phone {
            font-size: 10.5px;
            font-weight: 900;
            letter-spacing: 0.5px;
            direction: ltr;
            display: block;
            line-height: 1.1;
        }
        .emergency-pill .pill-sub {
            font-size: 5.8px;
            opacity: 0.9;
        }
        .assistance-contact-item {
            display: flex;
            justify-content: space-between;
            padding: 1px 0;
            font-size: 6.2px;
            border-bottom: 1px dashed #e2e8f0;
        }
        .assistance-contact-item:last-child {
            border-bottom: none;
        }

        /* Reinsurer Network Notice */
        .network-notice {
            border: 1px solid #0284c7;
            background: #f0f9ff;
            border-radius: 3px;
            padding: 2px 6px;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 6.5px;
            line-height: 1.15;
        }
        .network-notice strong {
            color: #005a9c;
        }

        /* Financial & Signatures Section */
        .financial-strip {
            display: grid;
            grid-template-columns: 1.5fr 1fr 1fr;
            gap: 4px;
            margin-bottom: 4px;
        }
        .financial-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.2px;
            border: 1px solid #cbd5e1;
            border-radius: 3px;
            overflow: hidden;
        }
        .financial-table th {
            background: #f1f5f9;
            color: #005a9c;
            padding: 2px 4px;
            font-weight: 800;
            text-align: center;
            border: 1px solid #cbd5e1;
            font-size: 6.5px;
        }
        .financial-table td {
            padding: 2px 4px;
            text-align: center;
            font-weight: 800;
            border: 1px solid #cbd5e1;
            font-size: 7.2px;
        }
        .financial-total-words {
            grid-column: 1 / -1;
            background: #fff;
            padding: 1.5px 4px;
            font-size: 6.8px;
            font-weight: 700;
            color: #005a9c;
            border: 1px dashed #93c5fd;
            border-radius: 3px;
            margin-top: 2px;
            text-align: right;
        }

        .signature-box {
            border: 1px solid #cbd5e1;
            border-radius: 3px;
            background: #fafafa;
            padding: 2px 4px;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 44px;
        }
        .signature-title {
            font-size: 6.8px;
            font-weight: 800;
            color: #005a9c;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 1px;
        }
        .signature-stamp-area {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            font-size: 6.2px;
            font-style: italic;
        }
        .signature-name {
            font-size: 6.2px;
            color: #475569;
            font-weight: 600;
        }

        /* Footer Strip */
        .print-footer-area {
            border-top: 1.5px solid #005a9c;
            padding-top: 2px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 6.5px;
            color: #475569;
            background: #fff;
        }
        .footer-left {
            font-weight: 800;
            color: #005a9c;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .footer-center {
            text-align: center;
        }
        .footer-right {
            text-align: left;
            direction: ltr;
        }

        /* Print Media Styles */
        @media print {
            body {
                background: #fff !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .action-bar {
                display: none !important;
            }
            .page-sheet {
                box-shadow: none !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                min-height: auto !important;
                border: none !important;
            }
        }
    </style>
    <!-- Lightweight QR Generator -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
</head>
<body>

    <!-- Floating Action Bar for Agent (Interactive, hides on print) -->
    <div class="action-bar no-print">
        <span class="label">خيارات وثيقة المسافرين:</span>
        <button class="action-btn action-btn-primary" onclick="window.print()">
            🖨️ طباعة الوثيقة (Print)
        </button>
        <div class="divider"></div>
        <button class="action-btn" id="btnStationeryToggle" onclick="toggleStationeryMode()">
            📄 وضع الورق: <b id="paperModeLabel">ورق أبيض كامل</b>
        </button>
        <button class="action-btn" id="btnEcoToggle" onclick="toggleEcoMode()">
            💧 وضع الحبر: <b id="ecoModeLabel">عادي (ملون)</b>
        </button>
        <div class="divider"></div>
        <button class="action-btn" onclick="window.close()" style="background: #ef4444; color: #fff;">
            ✕ إغلاق
        </button>
    </div>

    <!-- Main Printable A4 Page Sheet -->
    <div class="page-sheet">

        <!-- Header Section (Company, Travel Logo, QR Verification) -->
        <div class="print-header-area">
            <!-- Travel Vector / Illustration & Logo -->
            <div class="header-art-left">
                <img src="{{ asset('img/logo.png') }}" alt="Logo" onerror="this.style.display='none'">
                <div>
                    <svg width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="#005a9c" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"></path>
                        <path d="M2 12h20"></path>
                    </svg>
                </div>
            </div>

            <!-- Company Titles -->
            <div class="header-logo-center">
                <div class="company-title-ar">شركة المدار الليبي للتأمين</div>
                <div class="company-title-en">ALMADAR LIBYAN INSURANCE COMPANY</div>
                <div class="company-slogan">شريككم الموثوق للحماية والأمان | Your Trusted Partner for Protection and Security</div>
            </div>

            <!-- QR Code Section -->
            <div class="header-qr-right">
                <div class="qr-text">
                    امسح للتحقق<br>
                    من صحة الوثيقة<br>
                    <strong>Scan to verify<br>the policy</strong>
                </div>
                <div class="qr-box" id="qrcode"></div>
            </div>
        </div>

        <!-- Document Main Banner -->
        <div class="header-banner">
            <span class="plane-icon">✈</span>
            <div>
                <h1>وثيقة تأمين المسافرين</h1>
                <h2>TRAVEL INSURANCE POLICY</h2>
            </div>
            <span class="plane-icon" style="transform: scaleX(-1);">✈</span>
        </div>

        <!-- 5-Metadata Cards Strip -->
        <div class="meta-strip">
            <div class="meta-card">
                <span class="meta-title">رقم الوثيقة</span>
                <span class="meta-subtitle">Policy No.</span>
                <span class="meta-val">{{ $document->insurance_number }}</span>
            </div>
            <div class="meta-card">
                <span class="meta-title">تاريخ الإصدار</span>
                <span class="meta-subtitle">Issue Date</span>
                <span class="meta-val">{{ \Carbon\Carbon::parse($document->issue_date)->format('Y/m/d') }}</span>
            </div>
            <div class="meta-card">
                <span class="meta-title">بداية التغطية</span>
                <span class="meta-subtitle">Start Date</span>
                <span class="meta-val">{{ \Carbon\Carbon::parse($document->start_date)->format('Y/m/d') }}</span>
            </div>
            <div class="meta-card">
                <span class="meta-title">نهاية التغطية</span>
                <span class="meta-subtitle">End Date</span>
                <span class="meta-val">{{ \Carbon\Carbon::parse($document->end_date)->format('Y/m/d') }}</span>
            </div>
            <div class="meta-card">
                <span class="meta-title">الوكيل / الفرع</span>
                <span class="meta-subtitle">Agent / Branch</span>
                <span class="meta-val">{{ $printData['agency_name'] ?? 'شركة المدار الليبي للتأمين' }}</span>
            </div>
        </div>

        <!-- Insured & Trip Details Card -->
        <div class="section-box">
            <div class="section-header">
                <span>👤 بيانات المؤمن له / تفاصيل الرحلة &nbsp;|&nbsp; Insured / Trip Details</span>
                <span style="font-size: 7.2px; opacity: 0.9;">نوع التأمين: {{ $document->insurance_type }}</span>
            </div>
            <div class="details-grid">
                <!-- Col 1 -->
                <div>
                    <div class="detail-row">
                        <span class="detail-label">اسم المؤمن له <span class="en">Insured Full Name</span></span>
                        <span class="detail-value rtl-val">{{ $mainPassenger->name_ar ?? '' }} {{ $mainPassenger->name_en ? ' / ' . $mainPassenger->name_en : '' }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">الجنسية <span class="en">Nationality</span></span>
                        <span class="detail-value rtl-val">{{ $mainPassenger->nationality ?? 'ليبي' }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">رقم جواز السفر <span class="en">Passport Number</span></span>
                        <span class="detail-value">{{ $mainPassenger->passport_number ?? '-' }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">جهة الاتصال في الطوارئ <span class="en">Emergency Contact</span></span>
                        <span class="detail-value">{{ $mainPassenger->phone ?? ($document->whatsapp_number ?? '-') }}</span>
                    </div>
                </div>

                <!-- Col 2 -->
                <div>
                    <div class="detail-row">
                        <span class="detail-label">تاريخ الميلاد <span class="en">Date of Birth</span></span>
                        <span class="detail-value">{{ $mainPassenger->birth_date ? \Carbon\Carbon::parse($mainPassenger->birth_date)->format('Y/m/d') : '-' }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">تاريخ المغادرة <span class="en">Departure Date</span></span>
                        <span class="detail-value">{{ \Carbon\Carbon::parse($document->start_date)->format('Y/m/d') }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">تاريخ العودة <span class="en">Return Date</span></span>
                        <span class="detail-value">{{ \Carbon\Carbon::parse($document->end_date)->format('Y/m/d') }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">مدة وسريان الوثيقة <span class="en">Policy Validity & Area</span></span>
                        <span class="detail-value rtl-val">
                            {{ $durationDays > 0 ? $durationDays . ' يوم' : ($document->duration ?? '-') }}
                            {{ $document->geographic_area ? ' (' . $document->geographic_area . ')' : '' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Family Members if present -->
            @if($familyMembers->count() > 0)
            <table class="family-table">
                <thead>
                    <tr>
                        <th style="width: 25px; text-align: center;">م</th>
                        <th>اسم المرافق / Family Member</th>
                        <th style="text-align: center;">صلة القرابة / Relation</th>
                        <th style="text-align: center;">رقم الجواز / Passport</th>
                        <th style="text-align: center;">تاريخ الميلاد / Birth Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($familyMembers as $idx => $member)
                    <tr>
                        <td style="text-align: center; font-weight: 800;">{{ $idx + 1 }}</td>
                        <td>{{ $member->name_ar }} {{ $member->name_en ? ' / ' . $member->name_en : '' }}</td>
                        <td style="text-align: center;">{{ $member->relation ?? 'مرافق' }}</td>
                        <td style="text-align: center; direction: ltr;">{{ $member->passport_number ?? '-' }}</td>
                        <td style="text-align: center;">{{ $member->birth_date ? \Carbon\Carbon::parse($member->birth_date)->format('Y/m/d') : '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>

        <!-- Travel Insurance Benefits Table (منافع تأمين المسافرين) -->
        <div class="benefits-card">
            <div class="benefits-card-header">
                <span class="title">🛡️ منافع تأمين المسافرين &nbsp;|&nbsp; Travel Insurance Benefits</span>
                <span class="limit-badge">حد التغطية الطبية: 50,000 USD (46,000 EUR) | Medical Coverage Limit</span>
            </div>
            <table class="benefits-table">
                <thead>
                    <tr>
                        <th class="col-num">م / No</th>
                        <th class="col-benefit">المنفعة بالعربية / Benefit in English</th>
                        <th class="col-limit">حد التغطية / Limit</th>
                        <th class="col-notes">ملاحظات / Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="col-num">1</td>
                        <td class="col-benefit">
                            <span class="ar">المصروفات الطبية والاستشفاء في الخارج</span>
                            <span class="en">Medical expenses and hospitalization abroad</span>
                        </td>
                        <td class="col-limit">50,000 USD</td>
                        <td class="col-notes">
                            <span class="ar">ضمن الحد الطبي</span>
                            <span class="en">Within medical limit</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="col-num">2</td>
                        <td class="col-benefit">
                            <span class="ar">علاج كوفيد-19</span>
                            <span class="en">COVID-19 Treatment</span>
                        </td>
                        <td class="col-limit">ضمن الحد الطبي / Within limit</td>
                        <td class="col-notes">
                            <span class="ar">ضمن الحد الطبي</span>
                            <span class="en">Within medical limit</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="col-num">3</td>
                        <td class="col-benefit">
                            <span class="ar">الإقامة بالمستشفى</span>
                            <span class="en">Hospitalization</span>
                        </td>
                        <td class="col-limit">ضمن الحد الطبي / Within limit</td>
                        <td class="col-notes">
                            <span class="ar">ضمن الحد الطبي</span>
                            <span class="en">Within medical limit</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="col-num">4</td>
                        <td class="col-benefit">
                            <span class="ar">العمليات الجراحية بسبب حادث</span>
                            <span class="en">Surgical operations in cases of accidents</span>
                        </td>
                        <td class="col-limit">3,000 USD*</td>
                        <td class="col-notes">
                            <span class="ar">بعد موافقة شركة المساعدة</span>
                            <span class="en">After approval of assistance company</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="col-num">5</td>
                        <td class="col-benefit">
                            <span class="ar">إعادة الترحيل في حالة الوفاة</span>
                            <span class="en">Repatriation of Mortal Remains</span>
                        </td>
                        <td class="col-limit">3,000 USD</td>
                        <td class="col-notes">
                            <span class="ar">حسب الإجراءات المعتمدة</span>
                            <span class="en">According to approved procedures</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="col-num">6</td>
                        <td class="col-benefit">
                            <span class="ar">علاج الأسنان الطارئ</span>
                            <span class="en">Emergency Dental Treatment</span>
                        </td>
                        <td class="col-limit">500 USD</td>
                        <td class="col-notes">
                            <span class="ar">حسب الجدول الطبي</span>
                            <span class="en">According to medical table</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="col-num">7</td>
                        <td class="col-benefit">
                            <span class="ar">النقل بسيارة الإسعاف</span>
                            <span class="en">Emergency Ambulance</span>
                        </td>
                        <td class="col-limit">ضمن الحد الطبي / Within limit</td>
                        <td class="col-notes">
                            <span class="ar">حسب الشروط والأحكام</span>
                            <span class="en">According to terms and conditions</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="col-num">8</td>
                        <td class="col-benefit">
                            <span class="ar">فقدان أو تأخير الأمتعة</span>
                            <span class="en">Baggage Loss or Delay</span>
                        </td>
                        <td class="col-limit">500 USD</td>
                        <td class="col-notes">
                            <span class="ar">لكل 24 ساعة</span>
                            <span class="en">Per 24 hours</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="col-num">9</td>
                        <td class="col-benefit">
                            <span class="ar">تأخير الرحلة</span>
                            <span class="en">Travel Delay</span>
                        </td>
                        <td class="col-limit">150 USD</td>
                        <td class="col-notes">
                            <span class="ar">بعد تقديم المستندات المطلوبة</span>
                            <span class="en">After providing required documents</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="col-num">10</td>
                        <td class="col-benefit">
                            <span class="ar">فقدان وثائق السفر</span>
                            <span class="en">Loss of Travel Documents</span>
                        </td>
                        <td class="col-limit">100 USD</td>
                        <td class="col-notes">
                            <span class="ar">بعد تقديم المستندات المطلوبة</span>
                            <span class="en">After providing required documents</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- 3-Column Bottom Info Grid (Conditions, Exclusions & Health Declaration, 24/7 Assistance) -->
        <div class="bottom-grid">
            <!-- Card 1: Conditions & Exclusions -->
            <div class="info-card">
                <div class="info-card-header blue">
                    <span>📋 الشروط والاستثناءات | Conditions & Exclusions</span>
                </div>
                <div class="info-card-body">
                    <ul class="conditions-list">
                        <li>
                            <span class="num">1.</span>
                            <span class="ar-cond">التأمين لا يغطي الأمراض المزمنة غير المستقرة السابقة للسفر.</span>
                            <span class="en-cond">Pre-existing chronic illnesses not covered.</span>
                        </li>
                        <li>
                            <span class="num">2.</span>
                            <span class="ar-cond">لا يغطي الإقامة بالمستشفى للمراقبة فقط بدون علاج.</span>
                            <span class="en-cond">Hospital stays for observation only excluded.</span>
                        </li>
                        <li>
                            <span class="num">3.</span>
                            <span class="ar-cond">لا يغطي العلاج التجميلي أو الاختبارات الطبية بدون مرض.</span>
                            <span class="en-cond">Cosmetic treatment/tests without illness not covered.</span>
                        </li>
                        <li>
                            <span class="num">4.</span>
                            <span class="ar-cond">الأمراض الخطيرة (سرطان، سكري، قلب، ضغط) إذا عولجت آخر 3 شهور مستثناة.</span>
                            <span class="en-cond">Serious illnesses if treated in last 3 months excluded.</span>
                        </li>
                        <li>
                            <span class="num">5.</span>
                            <span class="ar-cond">يجب إبلاغ شركة المساعدة فوراً في الطوارئ وإلا تُخلى المسؤولية.</span>
                            <span class="en-cond">Must notify assistance company ASAP in emergencies.</span>
                        </li>
                        <li>
                            <span class="num">6.</span>
                            <span class="ar-cond">تغطية كوفيد-19 مشمولة ضمن الوثيقة.</span>
                            <span class="en-cond">COVID-19 coverage included.</span>
                        </li>
                        <li>
                            <span class="num">7.</span>
                            <span class="ar-cond">الوثيقة تعتبر لاغية بمجرد حصول المؤمن له على الإقامة.</span>
                            <span class="en-cond">Policy null & void upon insured obtaining residency.</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Card 2: Additional Exclusions & Health Declaration -->
            <div class="info-card">
                <div class="info-card-header navy">
                    <span>⚠️ الاستثناءات الإضافية والإقرار | Additional & Health</span>
                </div>
                <div class="info-card-body">
                    <ul class="bullet-list">
                        <li>الأمراض السابقة للمصادقة (Pre-existing conditions).</li>
                        <li>الحروب، الكوارث الطبيعية، الإرهاب (War, terrorism).</li>
                        <li>الرياضات الخطرة والأنشطة الخطرة (Dangerous sports).</li>
                        <li>العمليات الجراحية الاختيارية (Elective surgeries).</li>
                        <li>العلاج في بلد المنشأ (Treatment in home country).</li>
                    </ul>
                    <div class="declaration-box">
                        <strong>إقرار وتعهد صحي | Health Declaration:</strong><br>
                        أقر بأنني أستوفي كافة الشروط الصحية المطلوبة، ولا أعاني من أي مرض سابق يتعارض مع الوثيقة، ولم أخفِ أي معلومات طبية، وأي إخلال يلغي الوثيقة ويسقط أي مطالبات مستقبلية.
                        <div style="font-size: 5.5px; color: #64748b; direction: ltr; text-align: left; margin-top: 1px;">
                            I confirm I have no pre-existing conditions affecting this policy. Any concealment voids coverage.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 3: Important Info & 24/7 Assistance Center -->
            <div class="info-card">
                <div class="info-card-header teal">
                    <span>ℹ️ معلومات هامة والمساعدة | Assistance 24/7</span>
                </div>
                <div class="info-card-body">
                    <div style="font-size: 6.5px; color: #334155; margin-bottom: 2px;">
                        في حالات الطوارئ: يرجى الاتصال بمركز المساعدة 24/7<br>
                        <span style="font-size: 5.8px; color: #64748b; direction: ltr; display: block;">For emergency, please contact assistance center 24/7:</span>
                    </div>

                    <div class="emergency-pill">
                        <span class="emergency-phone">+218 920926066</span>
                        <span class="pill-sub">مركز المساعدة على مدار الساعة / 24/7 Assistance</span>
                    </div>

                    <div class="assistance-contact-item">
                        <span style="font-weight: 700;">المعيد / Reinsurer:</span>
                        <span style="font-weight: 700; color: #005a9c;">دار الصحة لإدارة النفقات</span>
                    </div>
                    <div class="assistance-contact-item">
                        <span style="font-weight: 700;">هاتف إضافي:</span>
                        <span style="direction: ltr; font-weight: 700;">+49 1573 2333331</span>
                    </div>
                    <div class="assistance-contact-item">
                        <span style="font-weight: 700;">البريد الإلكتروني:</span>
                        <span style="direction: ltr; font-weight: 700; font-size: 6px;">travel@mli.ly / info@tpa.ly</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Network Liability Notice Banner -->
        <div class="network-notice">
            <div>
                <strong>المعيد: دار الصحة لإدارة النفقات الطبية (Dar Al-Sihha TPA)</strong> |
                يتم تغطية الحالات الطارئة فقط إذا تم العلاج عبر شبكة معيد التأمين. شركة المدار الليبي للتأمين غير مسؤولة عن التكاليف المتكبدة خارج هذه الشبكة.
            </div>
            <div style="font-size: 5.8px; color: #475569; direction: ltr; text-align: left; max-width: 40%;">
                Emergency cases covered ONLY if treated within reinsurer’s network. Al-Madar Libyan Insurance Co. not liable outside network.
            </div>
        </div>

        <!-- Financial Breakdown & Official Signatures -->
        <div class="financial-strip">
            <!-- Financial Table -->
            <div>
                <table class="financial-table">
                    <thead>
                        <tr>
                            <th>صافي القسط<br><span style="font-size: 5.5px;">Premium</span></th>
                            @if($document->family_members_premium > 0)
                            <th>قسط المرافقين<br><span style="font-size: 5.5px;">Family</span></th>
                            @endif
                            <th>رسم الإصدار<br><span style="font-size: 5.5px;">Issue Fee</span></th>
                            <th>الدمغة<br><span style="font-size: 5.5px;">Stamp</span></th>
                            <th style="background: #005a9c; color: #fff;">الإجمالي د.ل<br><span style="font-size: 5.5px; color: #e0f2fe;">Total LYD</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ number_format($document->premium, 2) }}</td>
                            @if($document->family_members_premium > 0)
                            <td>{{ number_format($document->family_members_premium, 2) }}</td>
                            @endif
                            <td>{{ number_format($document->issue_fees ?? 0, 2) }}</td>
                            <td>{{ number_format($document->stamp ?? 0, 2) }}</td>
                            <td style="background: #f0f7ff; color: #005a9c; font-size: 8.5px; font-weight: 900;">
                                {{ number_format($document->total, 2) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="financial-total-words">
                    المبلغ الإجمالي بالحروف: {{ $printData['total_in_words'] ?? '' }}
                </div>
            </div>

            <!-- Signature 1: Company Stamp -->
            <div class="signature-box">
                <div class="signature-title">شركة المدار الليبي للتأمين</div>
                <div class="signature-stamp-area">
                    (ختم وتوقيع الشركة المعتمد)
                </div>
                <div class="signature-name">Authorized Signature & Stamp</div>
            </div>

            <!-- Signature 2: Insured Signature -->
            <div class="signature-box">
                <div class="signature-title">توقيع المؤمن له / المسافر</div>
                <div class="signature-stamp-area">
                    (توقيع طالب التأمين / العميل)
                </div>
                <div class="signature-name">Insured / Policyholder Signature</div>
            </div>
        </div>

        <!-- Document Footer -->
        <div class="print-footer-area">
            <div class="footer-left">
                <span>✈ معاً في كل وجهة</span>
                <span style="font-size: 5.8px; color: #64748b; font-weight: 600;">| Together In Every Destination</span>
            </div>
            <div class="footer-center">
                طرابلس - صلاح الدين - بجانب كوبري صلاح الدين | Tripoli - Salah Eddin - Next to Salah Eddin Bridge
            </div>
            <div class="footer-right">
                www.mli.ly | info@mli.ly | 092003356 / 0920526066
            </div>
        </div>

    </div>

    <!-- QR Code Generator & Mode Controllers -->
    <script>
        // Generate QR Code dynamically
        (function() {
            const qrContainer = document.getElementById('qrcode');
            const qrData = {!! $qrDataJson !!};
            const qrText = 'https://mli.ly/verify?p=' + encodeURIComponent(qrData.policy) + '&d=' + encodeURIComponent(qrData.start);

            if (typeof QRCode !== 'undefined') {
                new QRCode(qrContainer, {
                    text: qrText,
                    width: 58,
                    height: 58,
                    colorDark: "#003b66",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.M
                });
            } else {
                // Fallback to QR server API
                qrContainer.innerHTML = '<img src="https://api.qrserver.com/v1/create-qr-code/?size=58x58&data=' + encodeURIComponent(qrText) + '" style="width:58px;height:58px;" alt="QR">';
            }
        })();

        // Mode 1: Pre-printed Stationery Mode (Hides Header & Footer)
        let isStationery = false;
        function toggleStationeryMode() {
            isStationery = !isStationery;
            document.body.classList.toggle('stationery-mode', isStationery);
            document.getElementById('btnStationeryToggle').classList.toggle('active', isStationery);
            document.getElementById('paperModeLabel').textContent = isStationery ? 'ورق مروّس جاهز للشركة' : 'ورق أبيض كامل';
        }

        // Mode 2: Eco Ink Saver Mode (Reduces heavy ink backgrounds)
        let isEco = false;
        function toggleEcoMode() {
            isEco = !isEco;
            document.body.classList.toggle('eco-mode', isEco);
            document.getElementById('btnEcoToggle').classList.toggle('active', isEco);
            document.getElementById('ecoModeLabel').textContent = isEco ? 'اقتصادي (توفير الحبر)' : 'عادي (ملون)';
        }
    </script>
</body>
</html>
