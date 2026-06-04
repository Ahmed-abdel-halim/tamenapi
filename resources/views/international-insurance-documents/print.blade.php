<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بطاقة تأمين دولي - {{ $document->document_number }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        @page {
            size: A4;
            margin: 8mm 10mm;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Tajawal', 'Arial', 'Tahoma', sans-serif;
            font-size: 11px;
            color: #000;
            background: #fff;
            line-height: 1.4;
        }

        .page {
            width: 100%;
            background: #fff;
            border: 2px solid #c00;
            padding: 6px;
        }

        /* ===== HEADER ===== */
        .header-outer {
            border: 1.5px solid #c00;
            margin-bottom: 4px;
        }

        .header-top {
            background: #c00;
            color: #fff;
            text-align: center;
            padding: 3px 6px;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .header-main {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 6px 8px;
            gap: 6px;
            border-bottom: 1px solid #c00;
        }

        .logo-box {
            width: 72px;
            height: 72px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .logo-box img {
            max-width: 70px;
            max-height: 70px;
            object-fit: contain;
        }

        /* Arab Union logo SVG placeholder */
        .arab-union-logo {
            width: 68px;
            height: 68px;
        }

        .header-center {
            flex: 1;
            text-align: center;
            padding: 0 8px;
        }

        .header-title-ar {
            font-size: 14px;
            font-weight: 900;
            color: #c00;
            line-height: 1.3;
            margin-bottom: 2px;
        }

        .header-title-en {
            font-size: 10px;
            font-weight: 700;
            color: #000;
            line-height: 1.3;
            direction: ltr;
        }

        .header-subtitle {
            font-size: 12px;
            font-weight: 800;
            color: #000;
            margin-top: 3px;
        }

        .header-info-row {
            display: flex;
            justify-content: center;
            gap: 20px;
            padding: 4px 8px;
            background: #fff8f8;
            border-top: 1px solid #c00;
            font-size: 10px;
        }

        .header-info-item {
            display: flex;
            gap: 4px;
            align-items: center;
        }

        .header-info-label {
            font-weight: 700;
            color: #c00;
        }

        /* ===== COMPANY BANNER ===== */
        .company-banner {
            background: #c00;
            color: #fff;
            text-align: center;
            padding: 4px 6px;
            font-size: 11px;
            font-weight: 800;
            margin-bottom: 4px;
            border: 1px solid #900;
        }

        /* ===== SECTIONS ===== */
        .section {
            border: 1.5px solid #c00;
            margin-bottom: 4px;
        }

        .section-header {
            background: #c00;
            color: #fff;
            text-align: center;
            padding: 3px 6px;
            font-size: 10px;
            font-weight: 800;
        }

        .section-body {
            padding: 0;
        }

        /* ===== DATA TABLE ===== */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table td {
            padding: 4px 6px;
            border: 1px solid #ddd;
            font-size: 10.5px;
            vertical-align: middle;
        }

        .data-table .lbl {
            font-weight: 800;
            color: #800;
            background: #fff5f5;
            width: 28%;
            white-space: nowrap;
        }

        .data-table .val {
            font-weight: 600;
            color: #000;
        }

        .data-table .lbl-en {
            font-size: 8.5px;
            color: #999;
            display: block;
            font-weight: 400;
            direction: ltr;
        }

        /* ===== FINANCIAL TABLE ===== */
        .fin-table {
            width: 100%;
            border-collapse: collapse;
        }

        .fin-table td {
            padding: 4px 8px;
            border: 1px solid #ddd;
            font-size: 10.5px;
        }

        .fin-table .fin-lbl {
            font-weight: 800;
            color: #800;
            background: #fff5f5;
            width: 60%;
        }

        .fin-table .fin-val {
            font-weight: 700;
            color: #000;
            text-align: center;
            direction: ltr;
        }

        .fin-table .fin-total td {
            background: #c00;
            color: #fff;
            font-weight: 900;
            font-size: 11px;
        }

        /* ===== GRID LAYOUT ===== */
        .two-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4px;
            margin-bottom: 4px;
        }

        .three-col {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 4px;
            margin-bottom: 4px;
        }

        /* ===== VALIDITY BAR ===== */
        .validity-bar {
            border: 1.5px solid #c00;
            margin-bottom: 4px;
        }

        .validity-bar .v-header {
            background: #c00;
            color: #fff;
            text-align: center;
            padding: 3px;
            font-size: 10px;
            font-weight: 800;
        }

        .validity-row {
            display: flex;
            align-items: stretch;
        }

        .validity-cell {
            flex: 1;
            text-align: center;
            padding: 5px 4px;
            border-right: 1px solid #c00;
        }

        .validity-cell:last-child { border-right: none; }

        .validity-cell .vc-label {
            font-size: 9px;
            color: #c00;
            font-weight: 700;
            display: block;
        }

        .validity-cell .vc-value {
            font-size: 11px;
            font-weight: 800;
            color: #000;
            display: block;
        }

        /* ===== QR + DOC NUMBER ROW ===== */
        .qr-docnum-row {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 5px 8px;
            background: #fff8f8;
            border-bottom: 1px solid #c00;
        }

        .qr-box {
            width: 72px;
            height: 72px;
            flex-shrink: 0;
            border: 1px solid #c00;
            padding: 2px;
        }

        .qr-box img { width: 100%; height: 100%; }

        .doc-num-block {
            flex: 1;
            text-align: center;
        }

        .doc-num-label {
            font-size: 9px;
            color: #c00;
            font-weight: 700;
        }

        .doc-num-value {
            font-size: 18px;
            font-weight: 900;
            color: #c00;
            letter-spacing: 1px;
        }

        .doc-issue-line {
            font-size: 9px;
            color: #666;
            margin-top: 2px;
        }

        /* ===== COUNTRIES BAR ===== */
        .countries-bar {
            background: #c00;
            color: #fff;
            text-align: center;
            padding: 5px 8px;
            font-size: 11px;
            font-weight: 800;
            margin-bottom: 4px;
            border: 1px solid #900;
        }

        /* ===== SIGNATURE / STAMP ===== */
        .sig-row {
            display: flex;
            gap: 4px;
            margin-bottom: 4px;
        }

        .sig-box {
            flex: 1;
            border: 1.5px dashed #c00;
            min-height: 50px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 4px;
        }

        .sig-label {
            font-size: 9px;
            font-weight: 800;
            color: #c00;
        }

        /* ===== NOTES ===== */
        .note-box {
            border: 1.5px solid #c00;
            margin-bottom: 4px;
        }

        .note-header {
            background: #c00;
            color: #fff;
            padding: 3px 6px;
            font-size: 10px;
            font-weight: 800;
        }

        .note-body {
            padding: 5px 8px;
            font-size: 9px;
            line-height: 1.7;
            color: #000;
        }

        /* ===== FOOTER ===== */
        .footer {
            border-top: 2px solid #c00;
            padding-top: 4px;
            text-align: center;
            font-size: 9px;
            color: #666;
        }

        @media print {
            body { padding: 0; margin: 0; }
            .no-print { display: none; }
            @page { margin: 8mm 10mm; }
        }
    </style>
</head>
<body>
<div class="page">

    {{-- ===== HEADER ===== --}}
    <div class="header-outer">
        <div class="header-top">
            الاتحاد العربي للتأمين / Arab Union of Insurance &nbsp;|&nbsp;
            شركة المدار الليبي للتأمين / Al-Madar Libyan Insurance
        </div>
        <div class="header-main">
            {{-- لوجو الاتحاد العربي --}}
            <div class="logo-box">
                <svg class="arab-union-logo" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="50" cy="50" r="48" fill="none" stroke="#c00" stroke-width="3"/>
                    <circle cx="50" cy="50" r="38" fill="none" stroke="#c00" stroke-width="1.5"/>
                    <!-- Olive branches -->
                    <path d="M18,50 Q25,30 40,28 Q30,40 35,50" fill="#2a7a2a" opacity="0.9"/>
                    <path d="M82,50 Q75,30 60,28 Q70,40 65,50" fill="#2a7a2a" opacity="0.9"/>
                    <path d="M18,50 Q25,70 40,72 Q30,60 35,50" fill="#2a7a2a" opacity="0.9"/>
                    <path d="M82,50 Q75,70 60,72 Q70,60 65,50" fill="#2a7a2a" opacity="0.9"/>
                    <!-- Globe lines -->
                    <ellipse cx="50" cy="50" rx="18" ry="28" fill="none" stroke="#c00" stroke-width="1.2"/>
                    <line x1="22" y1="50" x2="78" y2="50" stroke="#c00" stroke-width="1.2"/>
                    <line x1="27" y1="35" x2="73" y2="35" stroke="#c00" stroke-width="0.8"/>
                    <line x1="27" y1="65" x2="73" y2="65" stroke="#c00" stroke-width="0.8"/>
                    <circle cx="50" cy="50" r="17" fill="none" stroke="#c00" stroke-width="1.2"/>
                    <!-- Car icon -->
                    <rect x="34" y="46" width="32" height="12" rx="3" fill="#c00"/>
                    <rect x="38" y="40" width="24" height="9" rx="2" fill="#c00"/>
                    <circle cx="39" cy="59" r="4" fill="#fff" stroke="#c00" stroke-width="1.5"/>
                    <circle cx="61" cy="59" r="4" fill="#fff" stroke="#c00" stroke-width="1.5"/>
                    <text x="50" y="75" text-anchor="middle" font-size="6" fill="#c00" font-weight="bold" font-family="Arial">ARAB UNION</text>
                </svg>
            </div>

            {{-- العنوان المركزي --}}
            <div class="header-center">
                <div class="header-title-ar">بطاقة التأمين العربية الموحدة</div>
                <div class="header-title-en">Arab Unified Insurance Card</div>
                <div class="header-title-ar" style="font-size:12px; margin-top:2px;">عن سير السيارات (المركبات) عبر البلاد العربية</div>
                <div class="header-title-en" style="font-size:9px;">For Vehicles Traveling Across Arab Countries</div>
                <div class="header-subtitle" style="margin-top:3px; color:#c00; border-top: 1px solid #c00; padding-top:3px;">
                    للمركبات الليبية — Libyan Vehicles
                </div>
            </div>

            {{-- لوجو المدار --}}
            <div class="logo-box">
                <img src="/img/logo.png" alt="شعار المدار"
                     onerror="this.style.display='none'; this.parentElement.innerHTML='<div style=\'width:70px;height:70px;background:#c00;color:#fff;display:flex;align-items:center;justify-content:center;font-size:7px;text-align:center;\'>المدار الليبي</div>'" />
            </div>
        </div>

        {{-- بيانات الوثيقة والـ QR --}}
        <div class="qr-docnum-row">
            <div class="qr-box" id="qrcode"></div>
            <div class="doc-num-block">
                <div class="doc-num-label">رقم الوثيقة / Policy Number</div>
                <div class="doc-num-value">{{ $document->document_number }}</div>
                <div class="doc-issue-line">
                    تاريخ الإصدار: {{ \Carbon\Carbon::parse($document->issue_date)->format('d/m/Y H:i') }}
                    &nbsp;|&nbsp;
                    Issue Date
                </div>
                @if($document->external_policy_number ?? null)
                <div class="doc-issue-line" style="color:#c00; font-weight:700; margin-top:3px;">
                    رقم بطاقة الاتحاد: {{ $document->external_policy_number }}
                </div>
                @endif
            </div>
            <div style="text-align:center; min-width:90px;">
                <div style="font-size:9px; color:#c00; font-weight:700;">شركة المدار الليبي للتأمين</div>
                <div style="font-size:8px; color:#666;">Al-Madar Libyan Insurance Co.</div>
                <div style="font-size:9px; font-weight:700; margin-top:3px;">{{ $printData['agency_name'] ?? 'الإدارة العامة' }}</div>
                <div style="font-size:8px; color:#666;">كود: {{ $printData['agency_code'] ?? 'ML0001' }}</div>
                <div style="font-size:8px; font-weight:700; color:#c00;">{{ $printData['agent_name'] ?? 'الإدارة' }}</div>
            </div>
        </div>
    </div>

    {{-- ===== فترة التغطية ===== --}}
    <div class="validity-bar">
        <div class="v-header">📅 فترة سريان التأمين — Insurance Coverage Period</div>
        <div class="validity-row">
            <div class="validity-cell">
                <span class="vc-label">من (12:00 ظهراً) — From Noon</span>
                <span class="vc-value">{{ \Carbon\Carbon::parse($document->start_date)->format('d/m/Y') }}</span>
            </div>
            <div class="validity-cell" style="background:#fff8f8;">
                <span class="vc-label">مدة التأمين — Duration</span>
                <span class="vc-value">{{ $document->number_of_days }} يوم / Day</span>
            </div>
            <div class="validity-cell">
                <span class="vc-label">إلى (12:00 ظهراً) — To Noon</span>
                <span class="vc-value">{{ \Carbon\Carbon::parse($document->end_date)->format('d/m/Y') }}</span>
            </div>
        </div>
    </div>

    {{-- ===== بيانات المؤمن له والمركبة ===== --}}
    <div class="two-col">
        {{-- المؤمن له --}}
        <div class="section">
            <div class="section-header">👤 بيانات المؤمن له — Insured Details</div>
            <div class="section-body">
                <table class="data-table">
                    <tr>
                        <td class="lbl">الاسم<span class="lbl-en">Name</span></td>
                        <td class="val">{{ $document->insured_name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">العنوان<span class="lbl-en">Address</span></td>
                        <td class="val">{{ $document->insured_address ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">الهاتف<span class="lbl-en">Phone</span></td>
                        <td class="val">{{ $document->phone ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">واتساب<span class="lbl-en">WhatsApp</span></td>
                        <td class="val">{{ $document->whatsapp_number ?? '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- المركبة --}}
        <div class="section">
            <div class="section-header">🚗 بيانات المركبة — Vehicle Details</div>
            <div class="section-body">
                <table class="data-table">
                    <tr>
                        <td class="lbl">النوع / الماركة<span class="lbl-en">Make / Model</span></td>
                        <td class="val">
                            {{ $document->vehicleType
                                ? ($document->vehicleType->brand . ($document->vehicleType->category ? ' / ' . $document->vehicleType->category : ''))
                                : '-' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="lbl">سنة الصنع<span class="lbl-en">Year</span></td>
                        <td class="val">{{ $document->year ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">رقم اللوحة<span class="lbl-en">Plate No.</span></td>
                        <td class="val" style="font-weight:900; font-size:12px; color:#c00;">{{ $document->plate_number ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">رقم الهيكل<span class="lbl-en">Chassis No.</span></td>
                        <td class="val" style="font-size:9px;">{{ $document->chassis_number ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">الجنسية<span class="lbl-en">Nationality</span></td>
                        <td class="val">{{ $document->vehicle_nationality ?? 'ليبية / Libyan' }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    {{-- ===== البند والبلد المزار ===== --}}
    <div class="two-col">
        <div class="section">
            <div class="section-header">🌍 البلد المزار — Visited Country</div>
            <div class="section-body">
                <table class="data-table">
                    <tr>
                        <td class="lbl">البلد<span class="lbl-en">Country</span></td>
                        <td class="val" style="font-size:13px; font-weight:900; color:#c00;">{{ $document->visited_country ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">عدد الدول<span class="lbl-en">No. of Countries</span></td>
                        <td class="val">{{ $document->number_of_countries ?? '1' }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">البند<span class="lbl-en">Item Type</span></td>
                        <td class="val">{{ $document->item_type ?? '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- المبالغ المالية --}}
        <div class="section">
            <div class="section-header">💰 البيانات المالية — Financial Data</div>
            <div class="section-body">
                <table class="fin-table">
                    <tr>
                        <td class="fin-lbl">القسط اليومي<span style="font-size:8px;font-weight:400;display:block;color:#999">Daily Premium</span></td>
                        <td class="fin-val">{{ number_format($document->daily_premium ?? 0, 3) }} د.ل</td>
                    </tr>
                    <tr>
                        <td class="fin-lbl">القسط الإجمالي<span style="font-size:8px;font-weight:400;display:block;color:#999">Total Premium</span></td>
                        <td class="fin-val">{{ number_format($document->premium ?? 0, 3) }} د.ل</td>
                    </tr>
                    <tr>
                        <td class="fin-lbl">الضريبة<span style="font-size:8px;font-weight:400;display:block;color:#999">Tax</span></td>
                        <td class="fin-val">{{ number_format($document->tax ?? 0, 3) }} د.ل</td>
                    </tr>
                    <tr>
                        <td class="fin-lbl">رسوم الإشراف<span style="font-size:8px;font-weight:400;display:block;color:#999">Supervision Fees</span></td>
                        <td class="fin-val">{{ number_format($document->supervision_fees ?? 0, 3) }} د.ل</td>
                    </tr>
                    <tr>
                        <td class="fin-lbl">مصاريف الإصدار<span style="font-size:8px;font-weight:400;display:block;color:#999">Issue Fees</span></td>
                        <td class="fin-val">{{ number_format($document->issue_fees ?? 0, 3) }} د.ل</td>
                    </tr>
                    <tr>
                        <td class="fin-lbl">دمغة المحررات<span style="font-size:8px;font-weight:400;display:block;color:#999">Stamp Duty</span></td>
                        <td class="fin-val">{{ number_format($document->stamp ?? 0, 3) }} د.ل</td>
                    </tr>
                    <tr class="fin-total">
                        <td class="fin-lbl" style="color:#fff;background:#c00;font-size:12px;">الإجمالي النهائي / TOTAL</td>
                        <td class="fin-val" style="color:#fff;background:#c00;font-size:13px;font-weight:900;">{{ number_format($document->total ?? 0, 3) }} د.ل</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    {{-- ===== الإجمالي بالحروف ===== --}}
    <div style="border: 1.5px solid #c00; margin-bottom:4px; padding: 5px 8px; background:#fff8f8; text-align:center;">
        <span style="font-size:9px; color:#c00; font-weight:700;">الإجمالي بالحروف — Amount in Words: </span>
        <span style="font-size:10px; font-weight:800; color:#000;">{{ $printData['total_in_words'] ?? '' }}</span>
    </div>

    {{-- ===== التوقيع والختم ===== --}}
    <div class="sig-row">
        <div class="sig-box">
            <div class="sig-label">توقيع المؤمن له / Insured Signature</div>
            <div style="flex:1;"></div>
        </div>
        <div class="sig-box">
            <div class="sig-label">وقت الإعداد / Issued At</div>
            <div style="font-size:10px; font-weight:700; margin-top:5px;">{{ \Carbon\Carbon::parse($document->issue_date)->format('d/m/Y H:i:s') }}</div>
        </div>
        <div class="sig-box">
            <div class="sig-label">توقيع وختم الوكيل / Agent Stamp</div>
            <div style="flex:1;"></div>
        </div>
    </div>

    {{-- ===== ملاحظة ===== --}}
    <div style="border: 1.5px solid #c00; margin-bottom:4px; text-align:center; padding:4px 6px; background:#fff;">
        <span style="font-size:9px; color:#c00; font-weight:700;">ملاحظة Note: </span>
        <span style="font-size:9px; color:#000;">للتأكد من بيانات وثيقتك ادخل هنا — To verify your policy data visit: <strong>www.mli.ly</strong></span>
    </div>

    {{-- ===== الشروط والأحكام ===== --}}
    <div class="note-box">
        <div class="note-header">⚖️ الشروط العامة — General Terms & Conditions</div>
        <div class="note-body">
            <strong>1-</strong> يلتزم المؤمن بموجب هذه الوثيقة بتغطية المسؤولية المدنية الناشئة عن الوفاة أو أية إصابة بدنية تلحق بأي شخص من حوادث المركبات الآلية في ليبيا المثبتة بياناتها في هذه الوثيقة وذلك عن مدة سريانها.<br>
            <strong>2-</strong> يلتزم المؤمن بدفع التعويض عن الأضرار المادية والمعنوية التي تلحق بالأشخاص من حوادث المركبة الآلية المؤمنة بموجب هذه الوثيقة وديًا أو قضائيًا وذلك بقيمة محددة لا تتجاوز الحد الأقصى المنصوص عليه بقرار اللجنة الشعبية العامة رقم (213 لسنة 2003) والقرارات المعدلة أو البديلة له.<br>
            <strong>3-</strong> يستحق التعويض عن الأضرار المادية والمعنوية للمصاب شخصيًا في حال الإصابة الجسدية، والأب والأم والزوج والأولاد دون غيرهم في حالة الوفاة.<br>
            <strong>4-</strong> تسقط دعوى المضرور قبل المؤمن بانقضاء ثلاثة سنوات من تاريخ صدور حكم نهائي بثبوت مسؤولية المؤمن له عن الحادث أو الواقعة المسببة للضرر.<br>
            <strong>5-</strong> لا يجوز للمؤمن له تقديم أو قبول أي عرض فيما يختص بتعويض المضرور دون موافقة المؤمن كتابةً.<br>
            <strong>6-</strong> لا يجوز للمؤمن ولا للمؤمن له إلغاء وثيقة التأمين أثناء مدة سريانها ما دام الترخيص للمركبة قائمًا.<br>
            <strong>الاختصاص القضائي:</strong> من المتفق عليه أن كل ما ينشأ من منازعات بصدد هذا العقد أو بخصوص تنفيذه يكون من اختصاص المحاكم الوطنية التي يتبع لها المركز الرئيسي للشركة. وفي جميع الأحوال فإن النص العربي لهذه الوثيقة هو الواجب التطبيق.
        </div>
    </div>

    {{-- ===== FOOTER ===== --}}
    <div class="footer">
        <strong>شركة المدار الليبي للتأمين</strong> — Al-Madar Libyan Insurance Co.
        &nbsp;|&nbsp; هاتف: 021-3614278 &nbsp;|&nbsp; بريد: info@mli.ly &nbsp;|&nbsp; www.mli.ly
        &nbsp;|&nbsp; <strong>وثيقة رقم:</strong> {{ $document->document_number }}
    </div>
</div>

@php
    $qrDataJson = json_encode($printData['qr_data'] ?? ['doc' => $document->document_number]);
@endphp
<script>
    (function() {
        const qrData = {!! $qrDataJson !!};
        const qrText = JSON.stringify(qrData);
        const qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=72x72&data=' + encodeURIComponent(qrText);
        const qrContainer = document.getElementById('qrcode');
        if (qrContainer) {
            qrContainer.innerHTML = '<img src="' + qrApiUrl + '" alt="QR Code" style="width:100%;height:100%;display:block;" />';
        }
    })();

    window.onload = function() {
        setTimeout(function() { window.print(); }, 600);
    };
</script>
</body>
</html>
