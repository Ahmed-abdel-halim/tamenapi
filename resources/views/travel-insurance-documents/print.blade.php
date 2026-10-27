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
        'insured' => $mainPassenger->name_en ?? ($mainPassenger->name_ar ?? ''),
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
    <title>وثيقة تأمين المسافرين - {{ $document->insurance_number }} - TRAVEL POLICY</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Tajawal:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #005a9c;
            --primary-dark: #003b66;
            --primary-light: #f0f7ff;
            --accent: #0284c7;
            --text-dark: #0f172a;
            --text-muted: #1e293b;
            --text-secondary: #334155;
            --border-color: #94a3b8;
            --border-dark: #475569;
            --bg-table-head: #f1f5f9;
            --bg-subtle: #f8fafc;
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
            margin: 3mm 5mm;
        }

        body {
            font-family: 'Tajawal', 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            font-size: 9px;
            color: var(--text-dark);
            background: #eef2f6;
            line-height: 1.25;
            direction: rtl;
        }

        /* Floating Minimalist Action Bar (Print & Close only) */
        .action-bar {
            position: fixed;
            top: 12px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(12px);
            padding: 8px 18px;
            border-radius: 9999px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.35);
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
            padding: 7px 18px;
            font-family: inherit;
            font-size: 12px;
            font-weight: 800;
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
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .action-btn-primary {
            background: linear-gradient(135deg, #0284c7, #005a9c);
            color: #fff;
        }

        /* Printable Sheet Layout - Sized to fill the full A4 Page Height */
        .page-sheet {
            width: 210mm;
            height: 290mm;
            max-height: 290mm;
            margin: 50px auto 15px auto;
            background: #fff;
            padding: 3.5mm 5.5mm;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
            position: relative;
            box-sizing: border-box;
            border-radius: 2px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
        }

        /* 1. Top Header */
        .print-header-area {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 3px;
            border-bottom: 2px solid var(--primary);
            min-height: 60px;
        }
        .header-logo-left {
            width: 135px;
            text-align: right;
            display: flex;
            align-items: center;
        }
        .header-logo-left img {
            max-height: 54px;
            max-width: 130px;
            object-fit: contain;
        }
        .header-logo-center {
            text-align: center;
            flex: 1;
        }
        .company-title-ar {
            font-size: 15.5px;
            font-weight: 900;
            color: var(--primary);
            letter-spacing: 0.3px;
            line-height: 1.15;
        }
        .company-title-en {
            font-family: 'Inter', sans-serif;
            font-size: 11px;
            font-weight: 900;
            color: #003b66;
            letter-spacing: 0.8px;
            margin-top: 1px;
            direction: ltr;
        }
        .company-slogan {
            font-size: 8.5px;
            font-weight: 700;
            color: var(--accent);
            margin-top: 1px;
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
            border: 1px solid var(--border-color);
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
            font-size: 7.5px;
            color: var(--text-dark);
            line-height: 1.2;
            text-align: left;
            max-width: 68px;
            direction: ltr;
            font-weight: 700;
        }

        /* 2. Document Title Banner (Eco ink-saving style with crisp outline) */
        .header-banner {
            background: #f8fafc;
            color: var(--primary);
            text-align: center;
            padding: 3.5px 14px;
            border-radius: 4px;
            border: 1.8px solid var(--primary);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .header-banner .plane-icon {
            font-size: 14px;
            color: var(--primary);
        }
        .header-banner h1 {
            font-size: 13.5px;
            font-weight: 900;
            margin: 0;
            line-height: 1.15;
            color: var(--primary);
        }
        .header-banner h2 {
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 1.5px;
            margin-top: 1px;
            color: #003b66;
            direction: ltr;
        }

        /* 3. 5-Metadata Cards Strip */
        .meta-strip {
            display: grid;
            grid-template-columns: 1.1fr 1fr 1fr 1fr 1.35fr;
            gap: 4px;
        }
        .meta-card {
            border: 1.2px solid #94a3b8;
            border-radius: 4px;
            background: #f8fafc;
            padding: 2.5px 5px;
            text-align: center;
        }
        .meta-card .meta-title {
            font-size: 8.5px;
            font-weight: 800;
            color: var(--primary);
            display: block;
            line-height: 1.15;
        }
        .meta-card .meta-subtitle {
            font-family: 'Inter', sans-serif;
            font-size: 8px;
            font-weight: 800;
            color: #003b66;
            display: block;
            margin-bottom: 1px;
            direction: ltr;
        }
        .meta-card .meta-val {
            font-family: 'Inter', 'Tajawal', sans-serif;
            font-size: 10px;
            font-weight: 900;
            color: #0f172a;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* 4. Insured & Trip Details Card */
        .section-box {
            border: 1.2px solid #94a3b8;
            border-radius: 4px;
            overflow: hidden;
            background: #fff;
        }
        .section-header {
            background: #f1f5f9;
            color: var(--primary);
            border-bottom: 1.5px solid var(--primary);
            padding: 3px 10px;
            font-size: 9.2px;
            font-weight: 900;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .section-header .en-sub {
            font-family: 'Inter', sans-serif;
            font-size: 9px;
            font-weight: 800;
            color: #003b66;
        }
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            padding: 3.5px 10px;
            gap: 1.5px 14px;
            font-size: 8.8px;
            background: #fff;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 2px 0;
            border-bottom: 1px dashed #cbd5e1;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            color: #1e293b;
            font-weight: 800;
            font-size: 8.5px;
        }
        .detail-label span.en {
            font-family: 'Inter', sans-serif;
            font-size: 8px;
            font-weight: 800;
            color: #003b66;
            display: block;
            line-height: 1.1;
            direction: ltr;
            text-align: right;
        }
        .detail-value {
            font-weight: 900;
            color: #0f172a;
            font-size: 9.5px;
            text-align: left;
            direction: ltr;
        }
        .detail-value.rtl-val {
            direction: rtl;
            text-align: left;
        }
        .detail-value .en-name {
            font-family: 'Inter', sans-serif;
            font-weight: 900;
            color: #003b66;
            font-size: 10px;
            direction: ltr;
            display: inline-block;
        }

        /* Family members mini-table if any */
        .family-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5px;
            margin-top: 2px;
            border-top: 1px solid var(--border-color);
        }
        .family-table th {
            background: #f1f5f9;
            color: #003b66;
            padding: 2.5px 6px;
            text-align: right;
            font-weight: 900;
            font-size: 8px;
            border: 1px solid var(--border-color);
        }
        .family-table td {
            padding: 2px 6px;
            border: 1px solid #e2e8f0;
            font-weight: 800;
            color: #0f172a;
        }

        /* 5. Benefits Table */
        .benefits-card {
            border: 1.6px solid var(--primary);
            border-radius: 4px;
            overflow: hidden;
            background: #fff;
        }
        .benefits-card-header {
            background: #f1f5f9;
            color: var(--primary);
            border-bottom: 1.5px solid var(--primary);
            padding: 3px 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .benefits-card-header .title {
            font-size: 9.5px;
            font-weight: 900;
            color: var(--primary);
        }
        .benefits-card-header .title .en-sub {
            font-family: 'Inter', sans-serif;
            font-size: 9.2px;
            font-weight: 900;
            color: #003b66;
        }
        .benefits-card-header .limit-badge {
            background: var(--primary);
            color: #fff;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 8.8px;
            font-weight: 900;
            letter-spacing: 0.3px;
        }
        .benefits-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5px;
            line-height: 1.2;
        }
        .benefits-table th {
            background: #f8fafc;
            color: #003b66;
            font-weight: 900;
            padding: 3px 6px;
            text-align: center;
            border: 1px solid var(--border-color);
            font-size: 8.5px;
        }
        .benefits-table th .en-col {
            font-family: 'Inter', sans-serif;
            font-size: 8px;
            font-weight: 900;
            color: #003b66;
            display: block;
            direction: ltr;
        }
        .benefits-table td {
            padding: 2px 6px;
            border: 1px solid #cbd5e1;
            vertical-align: middle;
        }
        .benefits-table tr:nth-child(even) td {
            background: #fbfdff;
        }
        .col-num {
            width: 24px;
            text-align: center;
            font-weight: 900;
            color: var(--primary);
            font-size: 9px;
        }
        .col-benefit {
            text-align: right;
            padding-right: 8px !important;
        }
        .col-benefit .ar {
            font-weight: 800;
            color: var(--text-dark);
            display: block;
            font-size: 8.5px;
        }
        .col-benefit .en {
            font-family: 'Inter', sans-serif;
            font-size: 8.8px;
            color: #003b66;
            font-weight: 800;
            direction: ltr;
            text-align: right;
            display: block;
            line-height: 1.15;
            letter-spacing: 0.2px;
        }
        .col-limit {
            width: 110px;
            text-align: center;
            font-weight: 900;
            color: #0f172a;
            font-size: 9px;
            white-space: nowrap;
        }
        .col-notes {
            width: 135px;
            text-align: center;
            font-size: 7.5px;
            color: var(--text-dark);
            font-weight: 700;
        }
        .col-notes .ar {
            display: block;
            font-weight: 800;
            color: #1e293b;
            font-size: 7.8px;
        }
        .col-notes .en {
            font-family: 'Inter', sans-serif;
            display: block;
            font-size: 7.6px;
            color: #003b66;
            font-weight: 800;
            direction: ltr;
        }

        /* 6. 3-Column Bottom Info Grid */
        .bottom-grid {
            display: grid;
            grid-template-columns: 1.2fr 0.94fr 0.88fr;
            gap: 4px;
        }
        .info-card {
            border: 1.2px solid #94a3b8;
            border-radius: 4px;
            background: #fff;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .info-card-header {
            padding: 2.5px 8px;
            font-size: 8.5px;
            font-weight: 900;
            color: var(--primary);
            background: #f1f5f9;
            border-bottom: 1.5px solid var(--primary);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .info-card-header .en-h {
            font-family: 'Inter', sans-serif;
            font-size: 8.2px;
            font-weight: 800;
            color: #003b66;
        }
        .info-card-body {
            padding: 3.5px 6px;
            font-size: 7.5px;
            line-height: 1.2;
            color: #0f172a;
            flex: 1;
        }
        .conditions-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .conditions-list li {
            position: relative;
            padding-right: 12px;
            margin-bottom: 1.6px;
        }
        .conditions-list li:last-child {
            margin-bottom: 0;
        }
        .conditions-list li .num {
            position: absolute;
            right: 0;
            top: 0;
            font-weight: 900;
            color: var(--primary);
            font-size: 7.8px;
        }
        .conditions-list li .ar-cond {
            font-weight: 700;
            display: block;
            font-size: 7.5px;
            color: #1e293b;
        }
        .conditions-list li .en-cond {
            font-family: 'Inter', sans-serif;
            font-size: 7.4px;
            font-weight: 800;
            color: #003b66;
            display: block;
            direction: ltr;
            text-align: right;
            line-height: 1.15;
        }

        .bullet-list {
            list-style: none;
            padding: 0;
            margin: 0 0 2px 0;
        }
        .bullet-list li {
            position: relative;
            padding-right: 9px;
            margin-bottom: 1.5px;
            font-size: 7.4px;
            font-weight: 800;
            color: #1e293b;
        }
        .bullet-list li::before {
            content: "•";
            position: absolute;
            right: 0;
            color: var(--primary);
            font-weight: 900;
        }
        .declaration-box {
            background: #f8fafc;
            border: 1px dashed var(--border-color);
            padding: 2.5px 5px;
            border-radius: 3px;
            font-size: 7.2px;
            margin-top: 1.5px;
            line-height: 1.2;
        }
        .declaration-box strong {
            color: var(--primary);
            font-size: 7.6px;
        }
        .declaration-box .en-decl {
            font-family: 'Inter', sans-serif;
            font-size: 7.2px;
            font-weight: 800;
            color: #003b66;
            direction: ltr;
            text-align: left;
            margin-top: 1px;
            line-height: 1.15;
        }

        /* Emergency Assistance Card */
        .emergency-pill {
            background: #f8fafc;
            color: var(--primary);
            border: 1.6px solid var(--primary);
            padding: 3px 6px;
            border-radius: 4px;
            text-align: center;
            margin: 1.5px 0 2.5px 0;
        }
        .emergency-phone {
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 0.5px;
            direction: ltr;
            display: block;
            line-height: 1.15;
            color: var(--primary);
        }
        .emergency-pill .pill-sub {
            font-family: 'Inter', 'Tajawal', sans-serif;
            font-size: 7.8px;
            font-weight: 800;
            color: #0f172a;
        }
        .assistance-contact-item {
            display: flex;
            justify-content: space-between;
            padding: 1.2px 0;
            font-size: 7.5px;
            border-bottom: 1px dashed #cbd5e1;
        }
        .assistance-contact-item:last-child {
            border-bottom: none;
        }

        /* 7. Reinsurer Network Notice */
        .network-notice {
            border: 1.2px solid var(--primary);
            background: #f8fafc;
            border-radius: 3px;
            padding: 2.5px 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 7.6px;
            line-height: 1.2;
        }
        .network-notice strong {
            color: var(--primary);
        }
        .network-notice .en-net {
            font-family: 'Inter', sans-serif;
            font-size: 7.5px;
            font-weight: 800;
            color: #003b66;
            direction: ltr;
            text-align: left;
            max-width: 40%;
            line-height: 1.15;
        }

        /* 8. Financial & Signatures Section */
        .financial-strip {
            display: grid;
            grid-template-columns: 1.5fr 1fr 1fr;
            gap: 4px;
        }
        .financial-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5px;
            border: 1.2px solid #94a3b8;
            border-radius: 3px;
            overflow: hidden;
        }
        .financial-table th {
            background: #f1f5f9;
            color: var(--primary);
            padding: 2px 4px;
            font-weight: 900;
            text-align: center;
            border: 1px solid #cbd5e1;
            font-size: 7.8px;
        }
        .financial-table th .en-th {
            font-family: 'Inter', sans-serif;
            font-size: 7.5px;
            font-weight: 900;
            color: #003b66;
            display: block;
            direction: ltr;
        }
        .financial-table td {
            padding: 2.5px 4px;
            text-align: center;
            font-weight: 900;
            border: 1px solid #cbd5e1;
            font-size: 9px;
            color: #0f172a;
        }
        .financial-total-words {
            background: #fff;
            padding: 2px 5px;
            font-size: 7.8px;
            font-weight: 800;
            color: var(--primary);
            border: 1px dashed #93c5fd;
            border-radius: 3px;
            margin-top: 2px;
            text-align: right;
        }

        .signature-box {
            border: 1.2px solid #94a3b8;
            border-radius: 3px;
            background: #fafafa;
            padding: 2.5px 5px;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 50px;
        }
        .signature-title {
            font-size: 8px;
            font-weight: 900;
            color: var(--primary);
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 1.5px;
        }
        .signature-stamp-area {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            font-size: 7.5px;
            font-style: italic;
            font-weight: 700;
        }
        .signature-name {
            font-family: 'Inter', sans-serif;
            font-size: 7.8px;
            color: #003b66;
            font-weight: 800;
            direction: ltr;
        }

        /* 9. Footer Strip */
        .print-footer-area {
            border-top: 1.5px solid var(--primary);
            padding-top: 2.5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 7.8px;
            color: #1e293b;
            background: #fff;
            font-weight: 700;
        }
        .footer-left {
            font-weight: 900;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .footer-center {
            text-align: center;
            font-weight: 700;
            color: #334155;
        }
        .footer-right {
            text-align: left;
            direction: ltr;
            font-weight: 800;
            font-family: 'Inter', sans-serif;
            color: #003b66;
        }

        /* Print Media Styles */
        @media print {
            body {
                background: #fff !important;
                padding: 0 !important;
                margin: 0 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .action-bar, .no-print {
                display: none !important;
            }
            .page-sheet {
                box-shadow: none !important;
                margin: 0 !important;
                padding: 2.5mm 4.5mm !important;
                width: 100% !important;
                height: 290mm !important;
                max-height: 290mm !important;
                border: none !important;
                page-break-after: avoid !important;
                page-break-inside: avoid !important;
            }
        }
    </style>
    <!-- Lightweight QR Generator -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
</head>
<body>

    <!-- Floating Minimalist Action Bar (Print & Close only) -->
    <div class="action-bar no-print">
        <button class="action-btn action-btn-primary" onclick="window.print()">
            🖨️ طباعة الوثيقة (Print Policy)
        </button>
        <button class="action-btn" onclick="window.close()" style="background: #ef4444; color: #fff;">
            ✕ إغلاق
        </button>
    </div>

    <!-- Main Printable A4 Page Sheet (Fits 100% of A4 Height) -->
    <div class="page-sheet">

        <!-- 1. Header Section (Company, Clean Logo, QR Verification) -->
        <div class="print-header-area">
            <!-- Company Logo Only -->
            <div class="header-logo-left">
                <img src="{{ asset('img/logo.png') }}" alt="Logo" onerror="this.style.display='none'">
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
                    <strong style="color: #003b66;">Scan to verify<br>the policy</strong>
                </div>
                <div class="qr-box" id="qrcode"></div>
            </div>
        </div>

        <!-- 2. Document Main Banner -->
        <div class="header-banner">
            <span class="plane-icon">✈</span>
            <div>
                <h1>وثيقة تأمين المسافرين</h1>
                <h2>TRAVEL INSURANCE POLICY</h2>
            </div>
            <span class="plane-icon" style="transform: scaleX(-1);">✈</span>
        </div>

        <!-- 3. 5-Metadata Cards Strip -->
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

        <!-- 4. Insured & Trip Details Card -->
        <div class="section-box">
            <div class="section-header">
                <span>👤 بيانات المؤمن له / تفاصيل الرحلة &nbsp;|&nbsp; <span class="en-sub">Insured & Trip Details</span></span>
                <span style="font-size: 8.5px; font-weight: 800; color: #003b66;">نوع التأمين: {{ $document->insurance_type }}</span>
            </div>
            <div class="details-grid">
                <!-- Col 1 -->
                <div>
                    <div class="detail-row">
                        <span class="detail-label">اسم المؤمن له <span class="en">Insured Full Name</span></span>
                        <span class="detail-value rtl-val">
                            {{ $mainPassenger->name_ar ?? '' }}
                            @if(!empty($mainPassenger->name_en))
                                <span class="en-name">/ {{ strtoupper($mainPassenger->name_en) }}</span>
                            @endif
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">الجنسية <span class="en">Nationality</span></span>
                        <span class="detail-value rtl-val">
                            {{ $mainPassenger->nationality ?? 'ليبي' }}
                            <span class="en-name" style="font-size: 8.5px;">/ {{ strtoupper($mainPassenger->nationality ?? 'Libyan') }}</span>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">رقم جواز السفر <span class="en">Passport Number</span></span>
                        <span class="detail-value" style="font-size: 10.5px; font-weight: 900; letter-spacing: 0.8px; color: #003b66;">
                            {{ $mainPassenger->passport_number ?? '-' }}
                        </span>
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
                        <span class="detail-value rtl-val" style="font-weight: 900; color: #003b66;">
                            {{ $durationDays > 0 ? $durationDays . ' يوم (' . $durationDays . ' Days)' : ($document->duration ?? '-') }}
                            {{ $document->geographic_area ? ' - ' . $document->geographic_area : '' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Family Members if present -->
            @if($familyMembers->count() > 0)
            <table class="family-table">
                <thead>
                    <tr>
                        <th style="width: 25px; text-align: center;">م / No</th>
                        <th>اسم المرافق / Family Member Name</th>
                        <th style="text-align: center;">صلة القرابة / Relation</th>
                        <th style="text-align: center;">رقم الجواز / Passport No.</th>
                        <th style="text-align: center;">تاريخ الميلاد / Birth Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($familyMembers as $idx => $member)
                    <tr>
                        <td style="text-align: center; font-weight: 900; color: #003b66;">{{ $idx + 1 }}</td>
                        <td>
                            {{ $member->name_ar }}
                            @if(!empty($member->name_en))
                                <span style="font-family: 'Inter', sans-serif; font-weight: 900; color: #003b66;">/ {{ strtoupper($member->name_en) }}</span>
                            @endif
                        </td>
                        <td style="text-align: center;">{{ $member->relation ?? 'مرافق' }}</td>
                        <td style="text-align: center; direction: ltr; font-weight: 900; color: #003b66;">{{ $member->passport_number ?? '-' }}</td>
                        <td style="text-align: center;">{{ $member->birth_date ? \Carbon\Carbon::parse($member->birth_date)->format('Y/m/d') : '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>

        <!-- 5. Travel Insurance Benefits Table (منافع تأمين المسافرين) -->
        <div class="benefits-card">
            <div class="benefits-card-header">
                <span class="title">🛡️ منافع تأمين المسافرين &nbsp;|&nbsp; <span class="en-sub">Travel Insurance Benefits</span></span>
                <span class="limit-badge">حد التغطية الطبية: 50,000 USD (46,000 EUR) | Medical Coverage Limit</span>
            </div>
            <table class="benefits-table">
                <thead>
                    <tr>
                        <th class="col-num">م<br><span class="en-col">No.</span></th>
                        <th class="col-benefit">المنفعة بالعربية / <span class="en-col" style="display:inline;">Benefit in English</span></th>
                        <th class="col-limit">حد التغطية<br><span class="en-col">Limit of Coverage</span></th>
                        <th class="col-notes">ملاحظات<br><span class="en-col">Remarks</span></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="col-num">1</td>
                        <td class="col-benefit">
                            <span class="ar">المصروفات الطبية والاستشفاء في الخارج</span>
                            <span class="en">Medical Expenses & Hospitalization Abroad</span>
                        </td>
                        <td class="col-limit" style="font-size: 10px; font-weight: 900; color: #003b66;">50,000 USD</td>
                        <td class="col-notes">
                            <span class="ar">شامل العلاج الطارئ</span>
                            <span class="en">Emergency Inpatient/Outpatient</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="col-num">2</td>
                        <td class="col-benefit">
                            <span class="ar">علاج كوفيد-19</span>
                            <span class="en">COVID-19 Treatment & Hospitalization</span>
                        </td>
                        <td class="col-limit">
                            <span class="ar" style="font-size: 7.8px;">ضمن الحد الطبي</span>
                            <span class="en" style="font-family: 'Inter', sans-serif; font-size: 8.5px; font-weight: 900; color: #003b66; display: block;">Within Medical Limit</span>
                        </td>
                        <td class="col-notes">
                            <span class="ar">ضمن الحد الطبي</span>
                            <span class="en">Within Medical Limit</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="col-num">3</td>
                        <td class="col-benefit">
                            <span class="ar">الإقامة بالمستشفى</span>
                            <span class="en">Hospital Confinement / Room & Board</span>
                        </td>
                        <td class="col-limit">
                            <span class="ar" style="font-size: 7.8px;">ضمن الحد الطبي</span>
                            <span class="en" style="font-family: 'Inter', sans-serif; font-size: 8.5px; font-weight: 900; color: #003b66; display: block;">Within Medical Limit</span>
                        </td>
                        <td class="col-notes">
                            <span class="ar">ضمن الحد الطبي</span>
                            <span class="en">Within Medical Limit</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="col-num">4</td>
                        <td class="col-benefit">
                            <span class="ar">العمليات الجراحية بسبب حادث</span>
                            <span class="en">Emergency Surgery Due to Accident</span>
                        </td>
                        <td class="col-limit" style="font-weight: 900; color: #003b66;">3,000 USD*</td>
                        <td class="col-notes">
                            <span class="ar">بموافقة شركة المساعدة</span>
                            <span class="en">With Prior Approval</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="col-num">5</td>
                        <td class="col-benefit">
                            <span class="ar">إعادة الجثمان في حالة الوفاة</span>
                            <span class="en">Repatriation of Mortal Remains</span>
                        </td>
                        <td class="col-limit" style="font-weight: 900; color: #003b66;">3,000 USD</td>
                        <td class="col-notes">
                            <span class="ar">حسب الإجراءات المعتمدة</span>
                            <span class="en">Approved Repatriation</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="col-num">6</td>
                        <td class="col-benefit">
                            <span class="ar">علاج الأسنان الطارئ</span>
                            <span class="en">Emergency Dental Care (Pain Relief)</span>
                        </td>
                        <td class="col-limit" style="font-weight: 900; color: #003b66;">500 USD</td>
                        <td class="col-notes">
                            <span class="ar">حسب الجدول الطبي</span>
                            <span class="en">Emergency Relief Only</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="col-num">7</td>
                        <td class="col-benefit">
                            <span class="ar">النقل الطبي والإسعاف الطارئ</span>
                            <span class="en">Emergency Medical Transportation / Ambulance</span>
                        </td>
                        <td class="col-limit">
                            <span class="ar" style="font-size: 7.8px;">ضمن الحد الطبي</span>
                            <span class="en" style="font-family: 'Inter', sans-serif; font-size: 8.5px; font-weight: 900; color: #003b66; display: block;">Within Medical Limit</span>
                        </td>
                        <td class="col-notes">
                            <span class="ar">حسب الشروط</span>
                            <span class="en">Standard Policy Terms</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="col-num">8</td>
                        <td class="col-benefit">
                            <span class="ar">فقدان أو تأخير الأمتعة المسجلة</span>
                            <span class="en">Checked Baggage Loss or Delay</span>
                        </td>
                        <td class="col-limit" style="font-weight: 900; color: #003b66;">500 USD</td>
                        <td class="col-notes">
                            <span class="ar">لكل 24 ساعة تأخير</span>
                            <span class="en">Per 24h of Delay</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="col-num">9</td>
                        <td class="col-benefit">
                            <span class="ar">تأخير إقلاع الرحلة الجوية</span>
                            <span class="en">Flight / Travel Delay</span>
                        </td>
                        <td class="col-limit" style="font-weight: 900; color: #003b66;">150 USD</td>
                        <td class="col-notes">
                            <span class="ar">بموجب إثبات من الناقل</span>
                            <span class="en">Airline Report Required</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="col-num">10</td>
                        <td class="col-benefit">
                            <span class="ar">فقدان وثائق السفر الرسمية</span>
                            <span class="en">Loss of Passport & Travel Documents</span>
                        </td>
                        <td class="col-limit" style="font-weight: 900; color: #003b66;">100 USD</td>
                        <td class="col-notes">
                            <span class="ar">تكاليف الاستخراج البديل</span>
                            <span class="en">Replacement Costs</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- 6. 3-Column Bottom Info Grid (Conditions, Exclusions & Health Declaration, 24/7 Assistance) -->
        <div class="bottom-grid">
            <!-- Card 1: Conditions & Exclusions -->
            <div class="info-card">
                <div class="info-card-header">
                    <span>📋 الشروط والاستثناءات</span>
                    <span class="en-h">Conditions & Exclusions</span>
                </div>
                <div class="info-card-body">
                    <ul class="conditions-list">
                        <li>
                            <span class="num">1.</span>
                            <span class="ar-cond">التأمين لا يغطي الأمراض المزمنة السابقة للسفر.</span>
                            <span class="en-cond">Pre-existing chronic illnesses not covered.</span>
                        </li>
                        <li>
                            <span class="num">2.</span>
                            <span class="ar-cond">لا يغطي الإقامة للمراقبة فقط بدون علاج طبي.</span>
                            <span class="en-cond">Hospital stay for observation only is excluded.</span>
                        </li>
                        <li>
                            <span class="num">3.</span>
                            <span class="ar-cond">لا يغطي العمليات التجميلية أو الفحوصات الروتينية.</span>
                            <span class="en-cond">Cosmetic/routine checkups strictly excluded.</span>
                        </li>
                        <li>
                            <span class="num">4.</span>
                            <span class="ar-cond">الأمراض المستعصية المعالجة بآخر 3 شهور مستثناة.</span>
                            <span class="en-cond">Critical diseases treated in last 3 months excluded.</span>
                        </li>
                        <li>
                            <span class="num">5.</span>
                            <span class="ar-cond">يجب إخطار مركز المساعدة فوراً في الطوارئ.</span>
                            <span class="en-cond">Assistance center must be notified immediately.</span>
                        </li>
                        <li>
                            <span class="num">6.</span>
                            <span class="ar-cond">تغطية كوفيد-19 مشمولة بالكامل ضمن الوثيقة.</span>
                            <span class="en-cond">COVID-19 coverage fully included under policy.</span>
                        </li>
                        <li>
                            <span class="num">7.</span>
                            <span class="ar-cond">تُلغى الوثيقة تلقائياً بمجرد الحصول على إقامة بالخارج.</span>
                            <span class="en-cond">Policy voids upon obtaining foreign residency.</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Card 2: Additional Exclusions & Health Declaration -->
            <div class="info-card">
                <div class="info-card-header">
                    <span>⚠️ الاستثناءات والإقرار</span>
                    <span class="en-h">Exclusions & Declaration</span>
                </div>
                <div class="info-card-body">
                    <ul class="bullet-list">
                        <li>الأمراض السابقة / Pre-existing medical conditions</li>
                        <li>الحروب والنزاعات والإرهاب / War, civil unrest, terrorism</li>
                        <li>الرياضات الخطرة والمغامرات / Hazardous sports & activities</li>
                        <li>العمليات الاختيارية / Elective surgeries & treatments</li>
                        <li>العلاج في بلد المنشأ / Treatments in country of origin</li>
                    </ul>
                    <div class="declaration-box">
                        <strong>إقرار صحي | Health Declaration:</strong>
                        <div style="font-size: 7px; color: #1e293b; line-height: 1.15;">
                            أقر بأنني لائق صحياً ولا أعاني من أي مرض يتعارض مع شروط السفر، وأي إخفاء يلغي الوثيقة.
                        </div>
                        <div class="en-decl">
                            I declare being medically fit without undeclared pre-existing conditions. Concealment voids coverage.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 3: Important Info & 24/7 Assistance Center -->
            <div class="info-card">
                <div class="info-card-header">
                    <span>ℹ️ المساعدة الدولية 24/7</span>
                    <span class="en-h">24/7 International Assistance</span>
                </div>
                <div class="info-card-body">
                    <div style="font-size: 7.5px; color: #1e293b; margin-bottom: 2px; font-weight: 700;">
                        في حالات الطوارئ بالخارج، اتصل فوراً:
                        <span style="font-family: 'Inter', sans-serif; font-size: 7.2px; color: #003b66; font-weight: 800; direction: ltr; display: block;">
                            For emergencies abroad, contact 24/7:
                        </span>
                    </div>

                    <div class="emergency-pill">
                        <span class="emergency-phone">+218 920926066</span>
                        <span class="pill-sub">مركز المساعدة على مدار الساعة | 24/7 Emergency Line</span>
                    </div>

                    <div class="assistance-contact-item">
                        <span style="font-weight: 800;">شركة المساعدة / TPA:</span>
                        <span style="font-weight: 900; color: #003b66;">دار الصحة / Dar Al-Sihha</span>
                    </div>
                    <div class="assistance-contact-item">
                        <span style="font-weight: 800;">هاتف أوروبا / Europe Line:</span>
                        <span style="font-family: 'Inter', sans-serif; direction: ltr; font-weight: 900; color: #003b66;">+49 1573 2333331</span>
                    </div>
                    <div class="assistance-contact-item">
                        <span style="font-weight: 800;">البريد / E-mail:</span>
                        <span style="font-family: 'Inter', sans-serif; direction: ltr; font-weight: 800; font-size: 7.8px; color: #003b66;">travel@mli.ly / info@tpa.ly</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 7. Network Liability Notice Banner -->
        <div class="network-notice">
            <div style="font-weight: 700; color: #1e293b;">
                <strong>المعيد: دار الصحة لإدارة النفقات الطبية (Dar Al-Sihha TPA)</strong> |
                تُغطى الحالات الطارئة فقط عبر شبكة المعيد المعتمدة. الشركة غير مسؤولة عن النفقات خارج الشبكة دون إذن مسبق.
            </div>
            <div class="en-net">
                Emergency covered ONLY within reinsurer network. Al-Madar Insurance is not liable for costs outside network without prior authorization.
            </div>
        </div>

        <!-- 8. Financial Breakdown & Official Signatures -->
        <div class="financial-strip">
            <!-- Financial Table -->
            <div>
                <table class="financial-table">
                    <thead>
                        <tr>
                            <th>صافي القسط<span class="en-th">Net Premium</span></th>
                            @if($document->family_members_premium > 0)
                            <th>قسط المرافقين<span class="en-th">Family Add-on</span></th>
                            @endif
                            <th>رسم الإصدار<span class="en-th">Issue Fee</span></th>
                            <th>الدمغة<span class="en-th">Stamp Fee</span></th>
                            <th style="background: #f1f5f9; color: var(--primary); font-weight: 900;">الإجمالي د.ل<span class="en-th" style="color: var(--primary);">Total LYD</span></th>
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
                            <td style="color: var(--primary); font-size: 10.5px; font-weight: 900;">
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
                <div class="signature-name">Authorized Signature & Official Stamp</div>
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

        <!-- 9. Document Footer -->
        <div class="print-footer-area">
            <div class="footer-left">
                <span>✈ معاً في كل وجهة</span>
                <span style="font-family: 'Inter', sans-serif; font-size: 7.8px; color: #003b66; font-weight: 800;">| Together In Every Destination</span>
            </div>
            <div class="footer-center">
                طرابلس - صلاح الدين - بجانب كوبري صلاح الدين | Tripoli - Salah Eddin - Libya
            </div>
            <div class="footer-right">
                www.mli.ly | travel@mli.ly | 092003356 / 0920526066
            </div>
        </div>

    </div>

    <!-- QR Code Generator -->
    <script>
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
                qrContainer.innerHTML = '<img src="https://api.qrserver.com/v1/create-qr-code/?size=58x58&data=' + encodeURIComponent(qrText) + '" style="width:58px;height:58px;" alt="QR">';
            }
        })();
    </script>
</body>
</html>
