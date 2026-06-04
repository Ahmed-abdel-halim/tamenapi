<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>بطاقة تأمين دولي - {{ $document->document_number }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        @page { size: A4; margin: 6mm 8mm; }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Tajawal','Arial',sans-serif; font-size:10px; color:#000; background:#fff; }

        .card { width:100%; border:2px solid #000; }

        /* ====== HEADER ====== */
        .hdr { display:flex; border-bottom:2px solid #000; }

        /* الجانب الأيمن: شعار الاتحاد + شعار المدار */
        .hdr-right {
            width:100px;
            border-left:1px solid #000;
            display:flex;
            flex-direction:column;
            align-items:center;
            justify-content:center;
            padding:4px;
            gap:4px;
        }
        .hdr-right img { width:44px; height:44px; object-fit:contain; }

        /* المنتصف: العنوان */
        .hdr-center {
            flex:1;
            text-align:center;
            padding:6px 4px;
            display:flex;
            flex-direction:column;
            align-items:center;
            justify-content:center;
        }
        .hdr-title1 { font-size:17px; font-weight:900; line-height:1.25; }
        .hdr-title2 { font-size:12px; font-weight:700; line-height:1.3; margin-top:2px; }
        .hdr-title3 { font-size:14px; font-weight:900; color:#c00; margin-top:3px; }

        /* الجانب الأيسر: علم ليبيا */
        .hdr-left {
            width:90px;
            border-right:1px solid #000;
            display:flex;
            align-items:center;
            justify-content:center;
            padding:4px;
        }

        /* ====== COMPANY INFO ROW ====== */
        .company-row {
            display:flex;
            border-bottom:1px solid #000;
            font-size:9px;
        }
        .company-block {
            flex:1;
            border-left:1px solid #000;
            padding:3px 5px;
        }
        .company-block:last-child { border-left:none; }
        .company-block .cb-title {
            font-weight:900;
            font-size:9.5px;
            border-bottom:1px solid #ccc;
            padding-bottom:2px;
            margin-bottom:2px;
        }
        .company-block .cb-row {
            display:flex;
            gap:4px;
            margin-bottom:1px;
            line-height:1.4;
        }
        .company-block .cb-lbl { font-weight:700; white-space:nowrap; }

        /* QR block */
        .qr-block {
            width:85px;
            border-left:1px solid #000;
            display:flex;
            flex-direction:column;
            align-items:center;
            justify-content:center;
            padding:3px;
            gap:2px;
            font-size:8px;
            text-align:center;
        }
        .qr-block img { width:70px; height:70px; }

        /* doc number block */
        .docnum-block {
            flex:1.2;
            border-left:1px solid #000;
            padding:4px 6px;
            display:flex;
            flex-direction:column;
            justify-content:center;
            gap:3px;
        }
        .docnum-block .dn-label { font-size:8px; color:#555; }
        .docnum-block .dn-value { font-size:16px; font-weight:900; color:#c00; }
        .docnum-block .dn-sub { font-size:8.5px; }

        /* ====== MAIN TABLE ====== */
        .main-tbl { width:100%; border-collapse:collapse; }
        .main-tbl td, .main-tbl th {
            border:1px solid #000;
            padding:3px 5px;
            vertical-align:middle;
            font-size:9.5px;
        }
        .main-tbl .lbl {
            font-weight:800;
            background:#f5f5f5;
            white-space:nowrap;
            width:90px;
            font-size:9px;
        }
        .main-tbl .val { font-weight:600; }
        .main-tbl .val-bold { font-weight:900; font-size:11px; }
        .main-tbl .sec-hdr {
            background:#000;
            color:#fff;
            text-align:center;
            font-weight:800;
            font-size:10px;
            padding:3px;
        }

        /* ====== DATES ROW ====== */
        .dates-tbl { width:100%; border-collapse:collapse; }
        .dates-tbl td {
            border:1px solid #000;
            padding:3px 5px;
            font-size:9px;
            text-align:center;
        }
        .dates-tbl .d-hdr { background:#000; color:#fff; font-weight:800; }
        .dates-tbl .d-lbl { font-weight:700; background:#f5f5f5; }
        .dates-tbl .d-val { font-weight:600; }

        /* ====== COUNTRIES ====== */
        .countries-box { border:1px solid #000; border-top:none; }
        .countries-hdr {
            background:#000; color:#fff;
            text-align:center; font-weight:800;
            padding:3px; font-size:10px;
            border-bottom:1px solid #000;
        }
        .countries-row {
            display:flex;
            flex-wrap:wrap;
            padding:4px 6px;
            gap:4px 10px;
        }
        .country-item {
            display:flex;
            align-items:center;
            gap:3px;
            font-size:9.5px;
            font-weight:600;
        }
        .country-check {
            width:12px; height:12px;
            border:1px solid #000;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            font-size:10px;
            font-weight:900;
        }
        .country-check.checked { background:#000; color:#fff; }

        /* ====== LEGAL / FINANCIAL ====== */
        .legal-box {
            border:1px solid #000;
            border-top:none;
            font-size:8.5px;
            line-height:1.55;
            padding:4px 6px;
        }
        .legal-box .legal-hdr {
            font-weight:900; font-size:9.5px;
            border-bottom:1px solid #000;
            padding-bottom:2px;
            margin-bottom:3px;
        }

        .fin-row { display:flex; border:1px solid #000; border-top:none; }
        .fin-cell {
            flex:1;
            border-left:1px solid #000;
            padding:3px 5px;
            font-size:9px;
        }
        .fin-cell:last-child { border-left:none; }
        .fin-cell .fc-lbl { font-weight:800; font-size:8.5px; color:#555; }
        .fin-cell .fc-val { font-weight:700; font-size:10.5px; }
        .fin-cell.total-cell { background:#000; color:#fff; }
        .fin-cell.total-cell .fc-lbl { color:#ccc; }
        .fin-cell.total-cell .fc-val { font-size:12px; font-weight:900; }

        /* ====== ISSUE ROW ====== */
        .issue-row {
            border:1px solid #000;
            border-top:none;
            display:flex;
            align-items:stretch;
        }
        .issue-cell {
            flex:1;
            border-left:1px solid #000;
            padding:4px 5px;
            font-size:9px;
            text-align:center;
        }
        .issue-cell:last-child { border-left:none; }
        .issue-cell .ic-lbl { font-weight:800; color:#000; }
        .issue-cell .ic-val { font-weight:600; margin-top:2px; }

        /* ====== STAMP / SIG ====== */
        .stamp-row {
            border:1px solid #000;
            border-top:none;
            display:flex;
        }
        .stamp-cell {
            flex:1;
            border-left:1px solid #000;
            padding:4px 6px;
            min-height:40px;
            font-size:9px;
        }
        .stamp-cell:last-child { border-left:none; }
        .stamp-cell .sc-lbl { font-weight:800; }

        /* ====== NOTE ====== */
        .note-row {
            border:1px solid #000;
            border-top:none;
            padding:4px 6px;
            font-size:9px;
            background:#fffde7;
        }
        .note-row strong { color:#c00; }

        /* ====== WORDS TOTAL ====== */
        .words-row {
            border:1px solid #000;
            border-top:none;
            padding:3px 6px;
            font-size:9.5px;
            text-align:center;
        }

        @media print {
            body { margin:0; padding:0; }
            @page { margin:6mm 8mm; }
        }
    </style>
</head>
<body>
<div class="card">

{{-- ====== HEADER ====== --}}
<div class="hdr">

    {{-- يمين: شعار الاتحاد + شعار المدار --}}
    <div class="hdr-right">
        {{-- شعار الاتحاد العربي للتأمين (SVG) --}}
        <svg width="46" height="46" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
            <circle cx="50" cy="50" r="47" fill="none" stroke="#1a4a8a" stroke-width="3"/>
            <circle cx="50" cy="50" r="36" fill="none" stroke="#1a4a8a" stroke-width="1.5"/>
            <!-- Olive left -->
            <path d="M14,50 C18,32 32,24 44,26 C36,36 28,44 28,50Z" fill="#2d7a2d"/>
            <path d="M14,50 C18,68 32,76 44,74 C36,64 28,56 28,50Z" fill="#2d7a2d"/>
            <!-- Olive right -->
            <path d="M86,50 C82,32 68,24 56,26 C64,36 72,44 72,50Z" fill="#2d7a2d"/>
            <path d="M86,50 C82,68 68,76 56,74 C64,64 72,56 72,50Z" fill="#2d7a2d"/>
            <!-- Globe -->
            <ellipse cx="50" cy="50" rx="16" ry="25" fill="none" stroke="#1a4a8a" stroke-width="1.5"/>
            <line x1="24" y1="50" x2="76" y2="50" stroke="#1a4a8a" stroke-width="1.2"/>
            <line x1="28" y1="36" x2="72" y2="36" stroke="#1a4a8a" stroke-width="0.8"/>
            <line x1="28" y1="64" x2="72" y2="64" stroke="#1a4a8a" stroke-width="0.8"/>
            <!-- Car -->
            <rect x="36" y="45" width="28" height="10" rx="2" fill="#1a4a8a"/>
            <rect x="40" y="38" width="20" height="9" rx="2" fill="#1a4a8a"/>
            <circle cx="41" cy="56" r="3.5" fill="#fff" stroke="#1a4a8a" stroke-width="1.5"/>
            <circle cx="59" cy="56" r="3.5" fill="#fff" stroke="#1a4a8a" stroke-width="1.5"/>
        </svg>
        {{-- شعار المدار --}}
        <img src="/img/logo.png" alt="المدار"
             onerror="this.style.display='none'"/>
        <div style="font-size:6.5px;text-align:center;font-weight:700;color:#1a4a8a;line-height:1.2;">المدار الليبي<br>للتأمين</div>
    </div>

    {{-- وسط: العنوان --}}
    <div class="hdr-center">
        <div class="hdr-title1">بطاقة التأمين العربية الموحدة</div>
        <div class="hdr-title2">عن سير السيارات (المركبات) عبر البلاد العربية</div>
        <div class="hdr-title3">للمركبات الليبية</div>
        <div style="font-size:9px;color:#555;margin-top:2px;direction:ltr;">Arab Unified Insurance Card — For Vehicles Traveling Across Arab Countries</div>
    </div>

    {{-- يسار: علم ليبيا (SVG) --}}
    <div class="hdr-left">
        <svg width="68" height="80" viewBox="0 0 68 80" xmlns="http://www.w3.org/2000/svg">
            <!-- إطار --}}
            <rect x="1" y="1" width="66" height="78" rx="2" fill="none" stroke="#000" stroke-width="1.5"/>
            <!-- الأسود --}}
            <rect x="1" y="1" width="66" height="26" rx="2" fill="#000"/>
            <!-- الأحمر --}}
            <rect x="1" y="27" width="66" height="13" fill="#ef0000"/>
            <!-- الأخضر --}}
            <rect x="1" y="40" width="66" height="39" fill="#239e45"/>
            <!-- الهلال والنجمة (أبيض) --}}
            <circle cx="34" cy="40" r="11" fill="none" stroke="#fff" stroke-width="8"/>
            <circle cx="37" cy="40" r="11" fill="#ef0000"/>
            <!-- نجمة --}}
            <polygon points="34,25 35.8,30.5 41.5,30.5 37,33.8 38.8,39.3 34,36 29.2,39.3 31,33.8 26.5,30.5 32.2,30.5" fill="#fff"/>
            <!-- حدود الشريط الأحمر --}}
            <line x1="1" y1="27" x2="67" y2="27" stroke="#000" stroke-width="0.5"/>
            <line x1="1" y1="40" x2="67" y2="40" stroke="#000" stroke-width="0.5"/>
        </svg>
    </div>
</div>

{{-- ====== COMPANY + QR + DOC NUMBER ROW ====== --}}
<div style="display:flex; border-bottom:1px solid #000;">

    {{-- بيانات الشركة والمكتب --}}
    <div style="flex:2; border-left:1px solid #000; padding:4px 5px; font-size:8.5px; line-height:1.6;">
        <div style="font-weight:900; font-size:10px; border-bottom:1px solid #000; padding-bottom:2px; margin-bottom:3px;">
            الشركة المصدرة للبطاقة: <span style="color:#c00;">شركة المدار الليبي للتأمين</span>
        </div>
        <div><span style="font-weight:700;">المكتب المحلي / الوكيل:</span> {{ $printData['agency_name'] ?? 'الإدارة العامة' }}</div>
        <div><span style="font-weight:700;">كود الوكيل:</span> {{ $printData['agency_code'] ?? 'ML0001' }}</div>
        <div><span style="font-weight:700;">معد الوثيقة:</span> {{ $printData['agent_name'] ?? 'الإدارة' }}</div>
        <div><span style="font-weight:700;">العنوان:</span> طرابلس - ليبيا</div>
        <div><span style="font-weight:700;">هاتف:</span> 021-3614278 &nbsp; <span style="font-weight:700;">فاكس:</span> 021-3614279</div>
        <div><span style="font-weight:700;">البريد الإلكتروني:</span> info@mli.ly</div>
    </div>

    {{-- QR Code --}}
    <div class="qr-block">
        <div id="qrcode" style="width:70px;height:70px;"></div>
        <div style="font-size:7.5px;font-weight:700;margin-top:2px;">مسح للتحقق</div>
    </div>

    {{-- رقم الوثيقة --}}
    <div class="docnum-block">
        <div class="dn-label">رقم الوثيقة / Policy No.</div>
        <div class="dn-value">{{ $document->document_number }}</div>
        <div class="dn-sub"><span style="font-weight:700;">تاريخ الإصدار:</span> {{ \Carbon\Carbon::parse($document->issue_date)->format('d/m/Y') }}</div>
        <div class="dn-sub"><span style="font-weight:700;">وقت الإصدار:</span> {{ \Carbon\Carbon::parse($document->issue_date)->format('H:i:s') }}</div>
        @if($document->external_policy_number ?? null)
        <div class="dn-sub" style="color:#c00;font-weight:800;">رقم بطاقة الاتحاد: {{ $document->external_policy_number }}</div>
        @endif
    </div>
</div>

{{-- ====== MAIN DATA TABLE ====== --}}
<table class="main-tbl">
    {{-- اسم المؤمن له --}}
    <tr>
        <td class="lbl">اسم المؤمن له</td>
        <td class="val" colspan="3" style="font-weight:800;font-size:11px;">{{ $document->insured_name ?? '-' }}</td>
        <td class="lbl" style="width:60px;">الهاتف</td>
        <td class="val" style="width:110px;">{{ $document->phone ?? '-' }}</td>
    </tr>
    {{-- العنوان --}}
    <tr>
        <td class="lbl">العنوان</td>
        <td class="val" colspan="5">{{ $document->insured_address ?? '-' }} &nbsp;|&nbsp; واتساب: {{ $document->whatsapp_number ?? '-' }}</td>
    </tr>
    {{-- بيانات المركبة --}}
    <tr>
        <td class="lbl">نوع المركبة</td>
        <td class="val" colspan="2">{{ $document->vehicleType ? ($document->vehicleType->brand . ($document->vehicleType->category ? ' / ' . $document->vehicleType->category : '')) : '-' }}</td>
        <td class="lbl">جنسية المركبة / الشاصي</td>
        <td class="val" colspan="2">{{ $document->vehicle_nationality ?? 'ليبية' }} &nbsp;/&nbsp; {{ $document->chassis_number ?? '-' }}</td>
    </tr>
    <tr>
        <td class="lbl">سنة الصنع والشاصي</td>
        <td class="val" colspan="2">{{ $document->year ?? '-' }}</td>
        <td class="lbl">رقم اللوحة المعدنية</td>
        <td class="val val-bold" colspan="2" style="color:#c00;">{{ $document->plate_number ?? '-' }}</td>
    </tr>
    <tr>
        <td class="lbl">البلد المزار</td>
        <td class="val" colspan="2" style="font-weight:900;font-size:12px;color:#000;">{{ $document->visited_country ?? '-' }}</td>
        <td class="lbl">رقم مرخص السواق</td>
        <td class="val" colspan="2">{{ $document->item_type ?? '-' }}</td>
    </tr>
</table>

{{-- ====== VALIDITY DATES ====== --}}
<table class="dates-tbl">
    <tr>
        <td class="d-hdr" colspan="6" style="text-align:right; padding:3px 5px;">مدة التأمين من الساعة 12:00 منتصف النهار يوم</td>
    </tr>
    <tr>
        <td class="d-lbl" style="width:80px;">الإقلاع / من</td>
        <td class="d-val" style="width:35px;">من</td>
        <td class="d-val" style="font-weight:800; color:#000; font-size:11px;">{{ \Carbon\Carbon::parse($document->start_date)->format('d/m/Y') }}</td>
        <td class="d-lbl" style="width:80px;">الوصول / إلى</td>
        <td class="d-val" style="width:35px;">إلى</td>
        <td class="d-val" style="font-weight:800; color:#000; font-size:11px;">{{ \Carbon\Carbon::parse($document->end_date)->format('d/m/Y') }}</td>
    </tr>
    <tr>
        <td class="d-lbl" colspan="2">المدة</td>
        <td class="d-val" style="font-weight:900; font-size:12px; color:#c00;">{{ $document->number_of_days }} يوم</td>
        <td class="d-lbl" colspan="2">الساعة</td>
        <td class="d-val">12:00 منتصف النهار</td>
    </tr>
</table>

{{-- ====== COUNTRIES ====== --}}
@php
    $allCountries = ['البحرين','تونس','سوريا','اليمن','العراق','ليبيا','مصر','الأردن','المغرب','الكويت','قطر','الإمارات','الجزائر','السعودية'];
    $visitedCountry = $document->visited_country ?? '';
@endphp
<div class="countries-box">
    <div class="countries-hdr">البلاد التي يسري فيها البطاقة</div>
    <div class="countries-row">
        @foreach($allCountries as $c)
        <div class="country-item">
            <div class="country-check {{ (mb_stripos($visitedCountry, $c) !== false || $c === 'ليبيا') ? 'checked' : '' }}">
                {{ (mb_stripos($visitedCountry, $c) !== false || $c === 'ليبيا') ? '✓' : '' }}
            </div>
            {{ $c }}
        </div>
        @endforeach
    </div>
</div>

{{-- ====== LEGAL TEXT ====== --}}
<div class="legal-box">
    <div class="legal-hdr">قانون المكتب المحلي الذي يرجع فيه هذا الإهلاء في حالة حوادث أو غيره:</div>
    <div>
        قانون المسؤولية المدنية الناشئة عن حوادث المركبات الآلية رقم 28 لسنة 1971م والقرارات المعدلة والمكملة له والتي تطبقها شركة المدار الليبي للتأمين طبقًا للوائح وقرارات هيئة الرقابة على أعمال التأمين في ليبيا.
        يلتزم المؤمن بموجب هذه الوثيقة بتغطية المسؤولية المدنية الناشئة عن الوفاة أو أية إصابة بدنية تلحق بأي شخص من حوادث المركبات الآلية المثبتة بياناتها في هذه الوثيقة وذلك عن مدة سريانها.
        يلتزم المؤمن بدفع التعويض عن الأضرار المادية والمعنوية وديًا أو قضائيًا وذلك بقيمة محددة لا تتجاوز الحد الأقصى المنصوص عليه بقرار اللجنة الشعبية العامة رقم (213 لسنة 2003).
    </div>
    <div style="margin-top:3px; font-weight:700;">
        بيانات الاتصال:
        &nbsp; بوكس النصح: 1002 &nbsp;|&nbsp; هاتف: +218213614278 &nbsp;|&nbsp; فاكس: +218213614279 &nbsp;|&nbsp; bua@insurancefel.ly
        &nbsp;|&nbsp; الشركة المضيفة إدارة حوادث جديدة: +218213604597 &nbsp;|&nbsp; bua.algerie@gmail.com
    </div>
</div>

{{-- ====== FINANCIAL ROW ====== --}}
<div class="fin-row" style="border-top:1px solid #000;">
    <div class="fin-cell">
        <div class="fc-lbl">القسط اليومي / Daily</div>
        <div class="fc-val">{{ number_format($document->daily_premium ?? 0, 3) }} د.ل</div>
    </div>
    <div class="fin-cell">
        <div class="fc-lbl">القسط الإجمالي / Premium</div>
        <div class="fc-val">{{ number_format($document->premium ?? 0, 3) }} د.ل</div>
    </div>
    <div class="fin-cell">
        <div class="fc-lbl">الضريبة / Tax</div>
        <div class="fc-val">{{ number_format($document->tax ?? 0, 3) }} د.ل</div>
    </div>
    <div class="fin-cell">
        <div class="fc-lbl">رسوم الإشراف</div>
        <div class="fc-val">{{ number_format($document->supervision_fees ?? 0, 3) }} د.ل</div>
    </div>
    <div class="fin-cell">
        <div class="fc-lbl">مصاريف الإصدار</div>
        <div class="fc-val">{{ number_format($document->issue_fees ?? 0, 3) }} د.ل</div>
    </div>
    <div class="fin-cell">
        <div class="fc-lbl">الدمغة / Stamp</div>
        <div class="fc-val">{{ number_format($document->stamp ?? 0, 3) }} د.ل</div>
    </div>
    <div class="fin-cell total-cell">
        <div class="fc-lbl">الإجمالي / TOTAL</div>
        <div class="fc-val">{{ number_format($document->total ?? 0, 3) }} د.ل</div>
    </div>
</div>

{{-- ====== TOTAL IN WORDS ====== --}}
<div class="words-row">
    <strong>إجمالي القسط والرسوم بالحروف:</strong> {{ $printData['total_in_words'] ?? '' }}
</div>

{{-- ====== ISSUE INFO ====== --}}
<div class="issue-row">
    <div class="issue-cell">
        <div class="ic-lbl">تاريخ الإصدار</div>
        <div class="ic-val">{{ \Carbon\Carbon::parse($document->issue_date)->format('d/m/Y') }}</div>
    </div>
    <div class="issue-cell">
        <div class="ic-lbl">وقت الإصدار</div>
        <div class="ic-val">{{ \Carbon\Carbon::parse($document->issue_date)->format('H:i:s') }}</div>
    </div>
    <div class="issue-cell">
        <div class="ic-lbl">الموقف</div>
        <div class="ic-val">طرابلس</div>
    </div>
    <div class="issue-cell">
        <div class="ic-lbl">من شهر</div>
        <div class="ic-val">{{ \Carbon\Carbon::parse($document->issue_date)->format('m') }}</div>
    </div>
    <div class="issue-cell">
        <div class="ic-lbl">سنة</div>
        <div class="ic-val">{{ \Carbon\Carbon::parse($document->issue_date)->format('Y') }}</div>
    </div>
    <div class="issue-cell">
        <div class="ic-lbl">قوام الفئة والرسوم</div>
        <div class="ic-val">{{ number_format($document->total ?? 0, 3) }} د.ل</div>
    </div>
</div>

{{-- ====== STAMP / SIGNATURE ====== --}}
<div class="stamp-row">
    <div class="stamp-cell">
        <div class="sc-lbl">توقيع المؤمن له / Insured Signature</div>
        <div style="height:32px;"></div>
    </div>
    <div class="stamp-cell">
        <div class="sc-lbl">قوام الفئة والرسوم التي يتقاضاها المكتب المحلي بما فيها ضريبة الدمغة على الرسوم</div>
        <div style="font-weight:900; font-size:11px; margin-top:4px; color:#c00;">{{ number_format($document->total ?? 0, 3) }} {{ $printData['total_in_words'] ?? '' }}</div>
    </div>
    <div class="stamp-cell">
        <div class="sc-lbl">توقيع وختم الوكيل / Agent Stamp</div>
        <div style="height:32px;"></div>
    </div>
</div>

{{-- ====== NOTE ====== --}}
<div class="note-row">
    <strong>هام:</strong> أي كتابة أو تعديل في هذه الصفحة يبطل البطاقة ويلغيها. &nbsp;|&nbsp;
    للتأكد من صحة الوثيقة: <strong>www.mli.ly</strong> &nbsp;|&nbsp; بريد: info@mli.ly
</div>

</div><!-- end card -->

@php $qrDataJson = json_encode($printData['qr_data'] ?? ['doc' => $document->document_number, 'company' => 'شركة المدار الليبي للتأمين']); @endphp
<script>
(function() {
    const qrText = JSON.stringify({!! $qrDataJson !!});
    const url = 'https://api.qrserver.com/v1/create-qr-code/?size=70x70&data=' + encodeURIComponent(qrText);
    const el = document.getElementById('qrcode');
    if (el) el.innerHTML = '<img src="' + url + '" style="width:70px;height:70px;">';
})();
window.onload = function() { setTimeout(function(){ window.print(); }, 600); };
</script>
</body>
</html>
