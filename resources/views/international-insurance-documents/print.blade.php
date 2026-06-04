<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>بطاقة تأمين دولي - {{ $document->document_number }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        @page { size: A4 portrait; margin: 7mm 9mm; }

        * { margin:0; padding:0; box-sizing:border-box; }

        body {
            font-family: 'Tajawal', 'Arial', sans-serif;
            font-size: 10px;
            color: #000;
            background: #fff;
            direction: rtl;
        }

        /* ===== PRINT BUTTON BAR ===== */
        .print-bar {
            text-align: center;
            padding: 8px;
            background: #1d4ed8;
            margin-bottom: 10px;
            border-radius: 6px;
        }
        .print-bar button {
            background: #fff;
            color: #1d4ed8;
            border: none;
            padding: 8px 28px;
            font-size: 14px;
            font-weight: 900;
            border-radius: 4px;
            cursor: pointer;
            font-family: 'Tajawal', Arial, sans-serif;
        }
        @media print {
            .print-bar { display: none !important; }
        }

        /* ===== CARD ===== */
        .card {
            width: 100%;
            border: 2px solid #000;
            font-family: 'Tajawal', 'Arial', sans-serif;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            font-family: 'Tajawal', 'Arial', sans-serif;
        }

        td, th {
            border: 1px solid #000;
            padding: 3px 5px;
            vertical-align: middle;
            font-size: 9.5px;
        }

        .lbl {
            font-weight: 800;
            background: #f2f2f2;
            white-space: nowrap;
        }

        .hdr-row {
            background: #000;
            color: #fff;
            text-align: center;
            font-weight: 800;
            font-size: 10px;
            padding: 3px 5px;
        }
    </style>
</head>
<body>

{{-- زر الطباعة --}}
<div class="print-bar">
    <button onclick="window.print()">🖨️ &nbsp; اطبع الوثيقة &nbsp; Print</button>
</div>

<div class="card">

{{-- ======= HEADER: شعار الاتحاد | العنوان | علم ليبيا ======= --}}
<table>
<tr>
    {{-- شعار الاتحاد + شعار المدار --}}
    <td style="width:100px; border:1px solid #000; text-align:center; padding:5px 3px; vertical-align:middle;">
        <svg width="50" height="50" viewBox="0 0 100 100">
            <circle cx="50" cy="50" r="46" fill="none" stroke="#1a4a8a" stroke-width="3.5"/>
            <circle cx="50" cy="50" r="35" fill="none" stroke="#1a4a8a" stroke-width="1.5"/>
            <path d="M14,50 C18,30 33,22 44,25 C35,36 27,44 27,50Z" fill="#2d7a2d"/>
            <path d="M14,50 C18,70 33,78 44,75 C35,64 27,56 27,50Z" fill="#2d7a2d"/>
            <path d="M86,50 C82,30 67,22 56,25 C65,36 73,44 73,50Z" fill="#2d7a2d"/>
            <path d="M86,50 C82,70 67,78 56,75 C65,64 73,56 73,50Z" fill="#2d7a2d"/>
            <ellipse cx="50" cy="50" rx="15" ry="25" fill="none" stroke="#1a4a8a" stroke-width="1.5"/>
            <line x1="24" y1="50" x2="76" y2="50" stroke="#1a4a8a" stroke-width="1.2"/>
            <line x1="28" y1="35" x2="72" y2="35" stroke="#1a4a8a" stroke-width="0.8"/>
            <line x1="28" y1="65" x2="72" y2="65" stroke="#1a4a8a" stroke-width="0.8"/>
            <rect x="35" y="44" width="30" height="12" rx="3" fill="#1a4a8a"/>
            <rect x="39" y="37" width="22" height="9" rx="2" fill="#1a4a8a"/>
            <circle cx="41" cy="57" r="4" fill="#fff" stroke="#1a4a8a" stroke-width="1.5"/>
            <circle cx="59" cy="57" r="4" fill="#fff" stroke="#1a4a8a" stroke-width="1.5"/>
        </svg>
        <br>
        <img src="/img/logo.png" alt="" style="width:45px;height:45px;object-fit:contain;margin-top:3px;" onerror="this.style.display='none'">
        <div style="font-size:6px;font-weight:800;color:#1a4a8a;line-height:1.3;margin-top:2px;">المدار الليبي للتأمين</div>
    </td>

    {{-- العنوان --}}
    <td style="border:1px solid #000; text-align:center; padding:8px 4px; vertical-align:middle;">
        <div style="font-size:20px;font-weight:900;line-height:1.2;">بطاقة التأمين العربية الموحدة</div>
        <div style="font-size:13px;font-weight:700;margin-top:4px;">عن سير السيارات (المركبات) عبر البلاد العربية</div>
        <div style="font-size:15px;font-weight:900;color:#cc0000;margin-top:4px;">للمركبات الليبية</div>
    </td>

    {{-- علم ليبيا --}}
    <td style="width:90px; border:1px solid #000; text-align:center; padding:5px; vertical-align:middle;">
        <svg width="72" height="82" viewBox="0 0 72 82">
            <rect x="1" y="1" width="70" height="80" rx="2" fill="none" stroke="#000" stroke-width="1.5"/>
            <rect x="1" y="1" width="70" height="27" fill="#000"/>
            <rect x="1" y="28" width="70" height="13" fill="#cc0000"/>
            <rect x="1" y="41" width="70" height="40" fill="#239e45"/>
            <circle cx="36" cy="41" r="11" fill="none" stroke="#fff" stroke-width="8"/>
            <circle cx="40" cy="41" r="11" fill="#cc0000"/>
            <polygon points="36,26 37.8,31.7 43.8,31.7 39.1,35.1 40.9,40.8 36,37.4 31.1,40.8 32.9,35.1 28.2,31.7 34.2,31.7" fill="#fff"/>
            <line x1="1" y1="28" x2="71" y2="28" stroke="#000" stroke-width="0.5"/>
            <line x1="1" y1="41" x2="71" y2="41" stroke="#000" stroke-width="0.5"/>
        </svg>
    </td>
</tr>
</table>

{{-- ======= COMPANY INFO + QR + DOC NUMBER ======= --}}
<table>
<tr>
    {{-- بيانات الشركة --}}
    <td style="width:44%; border:1px solid #000; padding:4px 6px; vertical-align:top; font-size:8.5px; line-height:1.7;">
        <div style="font-weight:900;font-size:10px;border-bottom:1px solid #ccc;padding-bottom:2px;margin-bottom:3px;">
            الشركة المصدرة للبطاقة: <span style="color:#cc0000;">شركة المدار الليبي للتأمين</span>
        </div>
        <div><b>المكتب / الوكيل: </b>{{ $printData['agency_name'] ?? 'الإدارة العامة' }}</div>
        <div><b>معد الوثيقة: </b>{{ $printData['agent_name'] ?? 'الإدارة' }}</div>
        <div><b>العنوان: </b>طرابلس - ليبيا</div>
        <div><b>صندوق بريد: </b>1002 &nbsp;&nbsp; <b>هاتف: </b>0213614278</div>
        <div><b>فاكس: </b>0213614279 &nbsp;&nbsp; <b>البريد: </b>info@mli.ly</div>
    </td>
    {{-- QR --}}
    <td style="width:15%; border:1px solid #000; text-align:center; vertical-align:middle; padding:4px;">
        <div id="qrcode" style="width:72px;height:72px;margin:0 auto;"></div>
        <div style="font-size:7px;font-weight:700;margin-top:2px;">مسح للتحقق</div>
    </td>
    {{-- رقم الوثيقة --}}
    <td style="border:1px solid #000; padding:5px 8px; vertical-align:top;">
        <div style="font-size:8px;color:#555;">رقم الوثيقة / Policy Number</div>
        <div style="font-size:20px;font-weight:900;color:#cc0000;line-height:1.1;">{{ $document->document_number }}</div>
        <div style="font-size:8.5px;margin-top:3px;"><b>تاريخ الإصدار: </b>{{ \Carbon\Carbon::parse($document->issue_date)->format('d/m/Y') }}</div>
        <div style="font-size:8.5px;"><b>الوقت: </b>{{ \Carbon\Carbon::parse($document->issue_date)->format('H:i:s') }}</div>
        @if($document->external_policy_number)
        <div style="font-size:8.5px;color:#cc0000;font-weight:800;margin-top:2px;"><b>بطاقة الاتحاد: </b>{{ $document->external_policy_number }}</div>
        @endif
    </td>
</tr>
</table>

{{-- ======= INSURED DATA ======= --}}
<table>
    <tr>
        <td class="lbl" style="width:90px;">اسم المؤمن له</td>
        <td colspan="3" style="font-weight:800;font-size:11px;">{{ $document->insured_name ?? '-' }}</td>
        <td class="lbl" style="width:55px;">الهاتف</td>
        <td style="width:110px;">{{ $document->phone ?? '-' }}</td>
    </tr>
    <tr>
        <td class="lbl">العنوان</td>
        <td colspan="5">{{ $document->insured_address ?? '-' }} &nbsp;|&nbsp; واتساب: {{ $document->whatsapp_number ?? '-' }}</td>
    </tr>
    <tr>
        <td class="lbl">نوع المركبة</td>
        <td colspan="2">
            @if($document->vehicleType){{ $document->vehicleType->brand }}{{ $document->vehicleType->category ? ' / '.$document->vehicleType->category : '' }}@else-@endif
        </td>
        <td class="lbl">جنسية المركبة / الشاصي</td>
        <td colspan="2">{{ $document->vehicle_nationality ?? 'ليبية' }} &nbsp;/&nbsp; {{ $document->chassis_number ?? '-' }}</td>
    </tr>
    <tr>
        <td class="lbl">سنة الصنع</td>
        <td colspan="2">{{ $document->year ?? '-' }}</td>
        <td class="lbl">رقم اللوحة المعدنية</td>
        <td colspan="2" style="font-weight:900;font-size:13px;color:#cc0000;">{{ $document->plate_number ?? '-' }}</td>
    </tr>
    <tr>
        <td class="lbl">البلد المزار</td>
        <td colspan="2" style="font-weight:900;font-size:12px;">{{ $document->visited_country ?? '-' }}</td>
        <td class="lbl">بند / نوع التأمين</td>
        <td colspan="2">{{ $document->item_type ?? '-' }}</td>
    </tr>
</table>

{{-- ======= VALIDITY DATES ======= --}}
<table>
    <tr>
        <td class="hdr-row" colspan="6">مدة التأمين من الساعة 12:00 منتصف النهار يوم</td>
    </tr>
    <tr>
        <td class="lbl" style="width:80px;">من (الإقلاع)</td>
        <td style="width:25px;text-align:center;">من</td>
        <td style="font-weight:900;font-size:12px;">{{ \Carbon\Carbon::parse($document->start_date)->format('d/m/Y') }}</td>
        <td class="lbl" style="width:80px;">إلى (الوصول)</td>
        <td style="width:25px;text-align:center;">إلى</td>
        <td style="font-weight:900;font-size:12px;">{{ \Carbon\Carbon::parse($document->end_date)->format('d/m/Y') }}</td>
    </tr>
    <tr>
        <td class="lbl" colspan="2">المدة / Duration</td>
        <td style="font-weight:900;font-size:13px;color:#cc0000;">{{ $document->number_of_days }} يوم / Day</td>
        <td class="lbl" colspan="2">الساعة / Time</td>
        <td>12:00 منتصف النهار / Noon</td>
    </tr>
</table>

{{-- ======= COUNTRIES ======= --}}
@php
$allCountries = ['البحرين','تونس','سوريا','اليمن','العراق','ليبيا','مصر','الأردن','المغرب','الكويت','قطر','الإمارات','الجزائر','السعودية'];
$visitedStr = mb_strtolower($document->visited_country ?? '');
@endphp
<table>
    <tr>
        <td class="hdr-row" colspan="{{ count($allCountries) }}">البلاد التي يسري فيها البطاقة</td>
    </tr>
    <tr>
        @foreach($allCountries as $country)
        @php $isVisited = (mb_strtolower($country) === $visitedStr || $country === 'ليبيا'); @endphp
        <td style="text-align:center; padding:4px 2px; font-size:8.5px; font-weight:{{ $isVisited ? '900' : '600' }}; background:{{ $isVisited ? '#000' : '#fff' }}; color:{{ $isVisited ? '#fff' : '#000' }};">
            {{ $country }}
        </td>
        @endforeach
    </tr>
</table>

{{-- ======= LEGAL TEXT ======= --}}
<table>
    <tr>
        <td class="hdr-row">قانون المكتب المحلي الذي يرجع فيه هذا الإهلاء في حالة الحوادث</td>
    </tr>
    <tr>
        <td style="font-size:8.5px; line-height:1.6; padding:4px 7px;">
            قانون المسؤولية المدنية الناشئة عن حوادث المركبات الآلية في ليبيا رقم 28 لسنة 1971م والقرارات المعدلة والمكملة له. يلتزم المؤمن بتغطية المسؤولية المدنية عن الوفاة أو الإصابة البدنية الناشئة عن حوادث المركبة خلال مدة سريان الوثيقة. يلتزم المؤمن بدفع التعويض عن الأضرار المادية والمعنوية وديًا أو قضائيًا بما لا يتجاوز الحد الأقصى وفق قرار اللجنة الشعبية رقم (213 لسنة 2003).<br>
            <b>بيانات الاتصال لشركة المدار:</b> ص.ب 1002 طرابلس &nbsp;|&nbsp; هاتف: 0213614278 &nbsp;|&nbsp; فاكس: 0213614279 &nbsp;|&nbsp; info@mli.ly &nbsp;|&nbsp; www.mli.ly
        </td>
    </tr>
</table>

{{-- ======= FINANCIAL ROW ======= --}}
<table>
    <tr>
        <td class="hdr-row">القسط اليومي</td>
        <td class="hdr-row">القسط الإجمالي</td>
        <td class="hdr-row">الضريبة</td>
        <td class="hdr-row">رسوم الإشراف</td>
        <td class="hdr-row">مصاريف الإصدار</td>
        <td class="hdr-row">الدمغة</td>
        <td class="hdr-row" style="background:#cc0000;">الإجمالي الكلي</td>
    </tr>
    <tr>
        <td style="text-align:center;font-weight:700;">{{ number_format($document->daily_premium ?? 0, 3) }} د.ل</td>
        <td style="text-align:center;font-weight:700;">{{ number_format($document->premium ?? 0, 3) }} د.ل</td>
        <td style="text-align:center;font-weight:700;">{{ number_format($document->tax ?? 0, 3) }} د.ل</td>
        <td style="text-align:center;font-weight:700;">{{ number_format($document->supervision_fees ?? 0, 3) }} د.ل</td>
        <td style="text-align:center;font-weight:700;">{{ number_format($document->issue_fees ?? 0, 3) }} د.ل</td>
        <td style="text-align:center;font-weight:700;">{{ number_format($document->stamp ?? 0, 3) }} د.ل</td>
        <td style="text-align:center;font-weight:900;font-size:13px;color:#cc0000;">{{ number_format($document->total ?? 0, 3) }} د.ل</td>
    </tr>
</table>

{{-- ======= TOTAL IN WORDS ======= --}}
<table>
    <tr>
        <td style="text-align:center; padding:4px; font-size:10px;">
            <b>إجمالي القسط والرسوم بالحروف ( الإجمالي ) :</b> {{ $printData['total_in_words'] ?? '' }}
        </td>
    </tr>
</table>

{{-- ======= ISSUE ROW ======= --}}
<table>
    <tr>
        <td class="hdr-row">تاريخ الإصدار</td>
        <td class="hdr-row">وقت الإصدار</td>
        <td class="hdr-row">الموقف</td>
        <td class="hdr-row">من شهر</td>
        <td class="hdr-row">سنة</td>
        <td class="hdr-row">قوام الفئة والرسوم</td>
    </tr>
    <tr>
        <td style="text-align:center;font-weight:700;">{{ \Carbon\Carbon::parse($document->issue_date)->format('d/m/Y') }}</td>
        <td style="text-align:center;font-weight:700;">{{ \Carbon\Carbon::parse($document->issue_date)->format('H:i') }}</td>
        <td style="text-align:center;font-weight:700;">طرابلس</td>
        <td style="text-align:center;font-weight:700;">{{ \Carbon\Carbon::parse($document->issue_date)->format('m') }}</td>
        <td style="text-align:center;font-weight:700;">{{ \Carbon\Carbon::parse($document->issue_date)->format('Y') }}</td>
        <td style="text-align:center;font-weight:900;color:#cc0000;font-size:12px;">{{ number_format($document->total ?? 0, 3) }} د.ل</td>
    </tr>
</table>

{{-- ======= STAMP / SIGNATURE ======= --}}
<table>
    <tr>
        <td style="width:33%; min-height:50px; padding:5px 6px; vertical-align:top;">
            <div style="font-weight:800;font-size:9px;">توقيع المؤمن له / Insured Signature</div>
            <div style="height:38px;"></div>
        </td>
        <td style="width:34%; padding:5px 6px; vertical-align:top;">
            <div style="font-weight:800;font-size:8.5px;">قوام الفئة والرسوم التي يتقاضاها المكتب المحلي بما فيها ضريبة الدمغة على الرسوم</div>
            <div style="font-weight:900;font-size:12px;color:#cc0000;margin-top:5px;">{{ number_format($document->total ?? 0, 3) }} دينار ليبي</div>
            <div style="font-size:9px;margin-top:2px;">{{ $printData['total_in_words'] ?? '' }}</div>
        </td>
        <td style="width:33%; padding:5px 6px; vertical-align:top;">
            <div style="font-weight:800;font-size:9px;">توقيع وختم الوكيل / Agent Stamp</div>
            <div style="height:38px;"></div>
        </td>
    </tr>
</table>

{{-- ======= NOTE ======= --}}
<table>
    <tr>
        <td style="background:#fffde7; padding:4px 7px; font-size:9px;">
            <b style="color:#cc0000;">هام: </b>أي كتابة أو تعديل في هذه الصفحة يُبطل البطاقة ويُلغيها. للتأكد من صحة الوثيقة: <b>www.mli.ly</b> &nbsp;|&nbsp; info@mli.ly
        </td>
    </tr>
</table>

</div>{{-- end .card --}}

@php
    $qrData = $printData['qr_data'] ?? ['doc' => $document->document_number, 'company' => 'شركة المدار الليبي للتأمين'];
    $qrText = json_encode($qrData, JSON_UNESCAPED_UNICODE);
@endphp
<script>
    (function() {
        var qrText = @json($qrText);
        var url = 'https://api.qrserver.com/v1/create-qr-code/?size=72x72&data=' + encodeURIComponent(qrText);
        var el = document.getElementById('qrcode');
        if (el) {
            var img = document.createElement('img');
            img.src = url;
            img.style.width = '72px';
            img.style.height = '72px';
            el.appendChild(img);
        }
    })();
</script>
</body>
</html>
