<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>بطاقة تأمين دولي - {{ $document->document_number }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        @page { size: A4 portrait; margin: 7mm 8mm; }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Tajawal','Arial',sans-serif; font-size:10px; color:#000; background:#fff; direction:rtl; }
        .card { width:100%; border:2px solid #000; }
        table { border-collapse:collapse; width:100%; }
        td, th { border:1px solid #000; padding:3px 5px; vertical-align:middle; font-size:9.5px; }
        .lbl { font-weight:800; background:#f5f5f5; white-space:nowrap; }
        .val { font-weight:600; }
        .sec-hdr td { background:#000; color:#fff; text-align:center; font-weight:800; font-size:10px; }
        .row-flex { display:flex; }
        .cell { flex:1; border:1px solid #000; padding:3px 5px; font-size:9px; }
    </style>
</head>
<body>
<div class="card">

{{-- ===== HEADER ===== --}}
<table>
    <tr>
        <td style="width:95px; text-align:center; border-bottom:2px solid #000; border-left:1px solid #000;">
            {{-- شعار الاتحاد العربي --}}
            <svg width="52" height="52" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                <circle cx="50" cy="50" r="47" fill="none" stroke="#1a4a8a" stroke-width="3"/>
                <circle cx="50" cy="50" r="36" fill="none" stroke="#1a4a8a" stroke-width="1.5"/>
                <path d="M14,50 C18,32 32,24 44,26 C36,36 28,44 28,50Z" fill="#2d7a2d"/>
                <path d="M14,50 C18,68 32,76 44,74 C36,64 28,56 28,50Z" fill="#2d7a2d"/>
                <path d="M86,50 C82,32 68,24 56,26 C64,36 72,44 72,50Z" fill="#2d7a2d"/>
                <path d="M86,50 C82,68 68,76 56,74 C64,64 72,56 72,50Z" fill="#2d7a2d"/>
                <ellipse cx="50" cy="50" rx="16" ry="25" fill="none" stroke="#1a4a8a" stroke-width="1.5"/>
                <line x1="24" y1="50" x2="76" y2="50" stroke="#1a4a8a" stroke-width="1.2"/>
                <line x1="28" y1="36" x2="72" y2="36" stroke="#1a4a8a" stroke-width="0.8"/>
                <line x1="28" y1="64" x2="72" y2="64" stroke="#1a4a8a" stroke-width="0.8"/>
                <rect x="36" y="45" width="28" height="10" rx="2" fill="#1a4a8a"/>
                <rect x="40" y="38" width="20" height="9" rx="2" fill="#1a4a8a"/>
                <circle cx="41" cy="56" r="3.5" fill="#fff" stroke="#1a4a8a" stroke-width="1.5"/>
                <circle cx="59" cy="56" r="3.5" fill="#fff" stroke="#1a4a8a" stroke-width="1.5"/>
            </svg>
            <br>
            <img src="/img/logo.png" alt="المدار" style="width:42px;height:42px;object-fit:contain;margin-top:3px;" onerror="this.style.display='none'">
            <div style="font-size:6.5px;font-weight:700;color:#1a4a8a;margin-top:2px;line-height:1.2;">المدار الليبي<br>للتأمين</div>
        </td>
        <td style="text-align:center; border-bottom:2px solid #000; padding:8px 4px;">
            <div style="font-size:18px;font-weight:900;line-height:1.2;">بطاقة التأمين العربية الموحدة</div>
            <div style="font-size:12px;font-weight:700;margin-top:3px;">عن سير السيارات (المركبات) عبر البلاد العربية</div>
            <div style="font-size:14px;font-weight:900;color:#c00;margin-top:4px;">للمركبات الليبية</div>
            <div style="font-size:8.5px;color:#555;margin-top:2px;direction:ltr;">Arab Unified Insurance Card — For Vehicles Traveling Across Arab Countries</div>
        </td>
        <td style="width:85px; text-align:center; border-bottom:2px solid #000; border-right:1px solid #000;">
            {{-- علم ليبيا --}}
            <svg width="65" height="75" viewBox="0 0 65 75" xmlns="http://www.w3.org/2000/svg">
                <rect x="1" y="1" width="63" height="73" rx="2" fill="none" stroke="#000" stroke-width="1.5"/>
                <rect x="1" y="1" width="63" height="24" fill="#000"/>
                <rect x="1" y="25" width="63" height="12" fill="#ef0000"/>
                <rect x="1" y="37" width="63" height="37" fill="#239e45"/>
                <circle cx="32" cy="37" r="10" fill="none" stroke="#fff" stroke-width="7"/>
                <circle cx="35" cy="37" r="10" fill="#ef0000"/>
                <polygon points="32,23 33.8,28.5 39.5,28.5 35,31.8 36.8,37.3 32,34 27.2,37.3 29,31.8 24.5,28.5 30.2,28.5" fill="#fff"/>
                <line x1="1" y1="25" x2="64" y2="25" stroke="#000" stroke-width="0.5"/>
                <line x1="1" y1="37" x2="64" y2="37" stroke="#000" stroke-width="0.5"/>
            </svg>
        </td>
    </tr>
</table>

{{-- ===== COMPANY + QR + DOC NUMBER ===== --}}
<table>
    <tr>
        <td style="width:42%; border-left:1px solid #000; padding:4px 6px; vertical-align:top;">
            <div style="font-weight:900;font-size:10px;border-bottom:1px solid #ccc;padding-bottom:2px;margin-bottom:3px;">
                الشركة المصدرة للبطاقة: <span style="color:#c00;">شركة المدار الليبي للتأمين</span>
            </div>
            <div style="line-height:1.7; font-size:8.5px;">
                <div><b>المكتب / الوكيل:</b> {{ $printData['agency_name'] ?? 'الإدارة العامة' }}</div>
                <div><b>كود الوكيل:</b> {{ $printData['agency_code'] ?? 'ML0001' }}</div>
                <div><b>معد الوثيقة:</b> {{ $printData['agent_name'] ?? 'الإدارة' }}</div>
                <div><b>العنوان:</b> طرابلس - ليبيا</div>
                <div><b>هاتف:</b> 021-3614278 &nbsp; <b>فاكس:</b> 021-3614279</div>
                <div><b>البريد:</b> info@mli.ly</div>
            </div>
        </td>
        <td style="width:14%; text-align:center; vertical-align:middle; border-left:1px solid #000; padding:3px;">
            <div id="qrcode" style="width:70px;height:70px;margin:0 auto;"></div>
            <div style="font-size:7px;font-weight:700;margin-top:2px;">مسح للتحقق</div>
        </td>
        <td style="vertical-align:top; padding:4px 6px;">
            <div style="font-size:8.5px;color:#555;">رقم الوثيقة / Policy No.</div>
            <div style="font-size:18px;font-weight:900;color:#c00;line-height:1.1;">{{ $document->document_number }}</div>
            <div style="font-size:8.5px;margin-top:3px;"><b>تاريخ الإصدار:</b> {{ \Carbon\Carbon::parse($document->issue_date)->format('d/m/Y') }}</div>
            <div style="font-size:8.5px;"><b>وقت الإصدار:</b> {{ \Carbon\Carbon::parse($document->issue_date)->format('H:i:s') }}</div>
            @if($document->external_policy_number)
            <div style="font-size:8.5px;color:#c00;font-weight:800;margin-top:2px;"><b>رقم بطاقة الاتحاد:</b> {{ $document->external_policy_number }}</div>
            @endif
        </td>
    </tr>
</table>

{{-- ===== MAIN DATA TABLE ===== --}}
<table>
    <tr>
        <td class="lbl" style="width:90px;">اسم المؤمن له</td>
        <td class="val" colspan="3" style="font-weight:800;font-size:11px;">{{ $document->insured_name ?? '-' }}</td>
        <td class="lbl" style="width:55px;">الهاتف</td>
        <td class="val" style="width:110px;">{{ $document->phone ?? '-' }}</td>
    </tr>
    <tr>
        <td class="lbl">العنوان</td>
        <td class="val" colspan="5">{{ $document->insured_address ?? '-' }} &nbsp;|&nbsp; واتساب: {{ $document->whatsapp_number ?? '-' }}</td>
    </tr>
    <tr>
        <td class="lbl">نوع المركبة</td>
        <td class="val" colspan="2">
            @if($document->vehicleType)
                {{ $document->vehicleType->brand }}{{ $document->vehicleType->category ? ' / ' . $document->vehicleType->category : '' }}
            @else
                -
            @endif
        </td>
        <td class="lbl">جنسية المركبة / الشاصي</td>
        <td class="val" colspan="2">{{ $document->vehicle_nationality ?? 'ليبية' }} / {{ $document->chassis_number ?? '-' }}</td>
    </tr>
    <tr>
        <td class="lbl">سنة الصنع</td>
        <td class="val" colspan="2">{{ $document->year ?? '-' }}</td>
        <td class="lbl">رقم اللوحة المعدنية</td>
        <td class="val" colspan="2" style="font-weight:900;font-size:12px;color:#c00;">{{ $document->plate_number ?? '-' }}</td>
    </tr>
    <tr>
        <td class="lbl">البلد المزار</td>
        <td class="val" colspan="2" style="font-weight:900;font-size:12px;">{{ $document->visited_country ?? '-' }}</td>
        <td class="lbl">بند التأمين</td>
        <td class="val" colspan="2">{{ $document->item_type ?? '-' }}</td>
    </tr>
</table>

{{-- ===== VALIDITY DATES ===== --}}
<table>
    <tr class="sec-hdr">
        <td colspan="6">مدة التأمين من الساعة 12:00 منتصف النهار يوم</td>
    </tr>
    <tr>
        <td class="lbl" style="width:80px;">من (الإقلاع)</td>
        <td class="val" style="width:30px;text-align:center;">من</td>
        <td class="val" style="font-weight:900;font-size:11px;color:#000;">{{ \Carbon\Carbon::parse($document->start_date)->format('d/m/Y') }}</td>
        <td class="lbl" style="width:80px;">إلى (الوصول)</td>
        <td class="val" style="width:30px;text-align:center;">إلى</td>
        <td class="val" style="font-weight:900;font-size:11px;color:#000;">{{ \Carbon\Carbon::parse($document->end_date)->format('d/m/Y') }}</td>
    </tr>
    <tr>
        <td class="lbl" colspan="2">المدة</td>
        <td class="val" style="font-weight:900;font-size:12px;color:#c00;">{{ $document->number_of_days }} يوم</td>
        <td class="lbl" colspan="2">الساعة</td>
        <td class="val">12:00 منتصف النهار</td>
    </tr>
</table>

{{-- ===== COUNTRIES ===== --}}
@php
$allCountries = ['البحرين','تونس','سوريا','اليمن','العراق','ليبيا','مصر','الأردن','المغرب','الكويت','قطر','الإمارات','الجزائر','السعودية'];
$visitedStr = strtolower($document->visited_country ?? '');
@endphp
<table>
    <tr class="sec-hdr"><td colspan="14">البلاد التي يسري فيها البطاقة</td></tr>
    <tr>
        @foreach($allCountries as $c)
        @php $checked = (mb_strtolower($c) === $visitedStr || $c === 'ليبيا'); @endphp
        <td style="text-align:center;padding:3px;font-size:8.5px;font-weight:{{ $checked ? '900' : '600' }};background:{{ $checked ? '#000' : '#fff' }};color:{{ $checked ? '#fff' : '#000' }};">
            {{ $c }}
        </td>
        @endforeach
    </tr>
</table>

{{-- ===== LEGAL TEXT ===== --}}
<table>
    <tr class="sec-hdr"><td>قانون المكتب المحلي الذي يرجع فيه هذا الإهلاء في حالة الحوادث</td></tr>
    <tr>
        <td style="font-size:8.5px;line-height:1.6;padding:4px 6px;">
            قانون المسؤولية المدنية الناشئة عن حوادث المركبات رقم 28 لسنة 1971 والقرارات المعدلة له. يلتزم المؤمن بموجب هذه الوثيقة بتغطية المسؤولية المدنية الناشئة عن الوفاة أو الإصابة البدنية التي تلحق بأي شخص من حوادث المركبة المثبتة بياناتها وذلك خلال مدة سريانها. يلتزم المؤمن بدفع التعويض عن الأضرار المادية والمعنوية وديًا أو قضائيًا بقيمة لا تتجاوز الحد الأقصى وفق قرار اللجنة الشعبية العامة رقم (213 لسنة 2003).
            <br>
            <b>بيانات الاتصال:</b> بوكس: 1002 &nbsp;|&nbsp; هاتف: 021-3614278 &nbsp;|&nbsp; فاكس: 021-3614279 &nbsp;|&nbsp; info@mli.ly
        </td>
    </tr>
</table>

{{-- ===== FINANCIAL ROW ===== --}}
<table>
    <tr class="sec-hdr">
        <td>القسط اليومي</td>
        <td>القسط الإجمالي</td>
        <td>الضريبة</td>
        <td>رسوم الإشراف</td>
        <td>مصاريف الإصدار</td>
        <td>الدمغة</td>
        <td style="background:#c00;">الإجمالي الكلي</td>
    </tr>
    <tr>
        <td class="val" style="text-align:center;">{{ number_format($document->daily_premium ?? 0, 3) }} د.ل</td>
        <td class="val" style="text-align:center;">{{ number_format($document->premium ?? 0, 3) }} د.ل</td>
        <td class="val" style="text-align:center;">{{ number_format($document->tax ?? 0, 3) }} د.ل</td>
        <td class="val" style="text-align:center;">{{ number_format($document->supervision_fees ?? 0, 3) }} د.ل</td>
        <td class="val" style="text-align:center;">{{ number_format($document->issue_fees ?? 0, 3) }} د.ل</td>
        <td class="val" style="text-align:center;">{{ number_format($document->stamp ?? 0, 3) }} د.ل</td>
        <td style="text-align:center;font-weight:900;font-size:12px;color:#c00;">{{ number_format($document->total ?? 0, 3) }} د.ل</td>
    </tr>
</table>

{{-- ===== TOTAL IN WORDS ===== --}}
<table>
    <tr>
        <td style="text-align:center;font-size:10px;padding:4px;">
            <b>إجمالي القسط والرسوم بالحروف:</b> {{ $printData['total_in_words'] ?? '' }}
        </td>
    </tr>
</table>

{{-- ===== ISSUE INFO ===== --}}
<table>
    <tr class="sec-hdr">
        <td>تاريخ الإصدار</td>
        <td>وقت الإصدار</td>
        <td>الموقف</td>
        <td>الشهر</td>
        <td>السنة</td>
        <td>قوام الفئة والرسوم</td>
    </tr>
    <tr>
        <td class="val" style="text-align:center;">{{ \Carbon\Carbon::parse($document->issue_date)->format('d/m/Y') }}</td>
        <td class="val" style="text-align:center;">{{ \Carbon\Carbon::parse($document->issue_date)->format('H:i:s') }}</td>
        <td class="val" style="text-align:center;">طرابلس</td>
        <td class="val" style="text-align:center;">{{ \Carbon\Carbon::parse($document->issue_date)->format('m') }}</td>
        <td class="val" style="text-align:center;">{{ \Carbon\Carbon::parse($document->issue_date)->format('Y') }}</td>
        <td class="val" style="text-align:center;font-weight:900;color:#c00;">{{ number_format($document->total ?? 0, 3) }} د.ل</td>
    </tr>
</table>

{{-- ===== STAMP / SIGNATURE ===== --}}
<table>
    <tr>
        <td style="width:33%;min-height:45px;padding:4px 6px;vertical-align:top;">
            <div style="font-weight:800;font-size:9px;">توقيع المؤمن له / Insured Signature</div>
            <div style="height:35px;"></div>
        </td>
        <td style="width:34%;padding:4px 6px;vertical-align:top;">
            <div style="font-weight:800;font-size:8.5px;">قوام الفئة والرسوم التي يتقاضاها المكتب بما فيها ضريبة الدمغة</div>
            <div style="font-weight:900;font-size:11px;color:#c00;margin-top:4px;">{{ number_format($document->total ?? 0, 3) }} دينار ليبي</div>
            <div style="font-size:9px;margin-top:2px;">{{ $printData['total_in_words'] ?? '' }}</div>
        </td>
        <td style="width:33%;padding:4px 6px;vertical-align:top;">
            <div style="font-weight:800;font-size:9px;">توقيع وختم الوكيل / Agent Stamp</div>
            <div style="height:35px;"></div>
        </td>
    </tr>
</table>

{{-- ===== NOTE ===== --}}
<table>
    <tr>
        <td style="background:#fffde7;padding:4px 6px;font-size:9px;">
            <b style="color:#c00;">هام:</b> أي كتابة أو تعديل في هذه الصفحة يبطل البطاقة ويلغيها. &nbsp;|&nbsp; للتأكد من صحة الوثيقة: <b>www.mli.ly</b>
        </td>
    </tr>
</table>

</div>

<script>
(function() {
    var qrText = {{ json_encode(json_encode($printData['qr_data'] ?? ['doc' => $document->document_number])) }};
    var url = 'https://api.qrserver.com/v1/create-qr-code/?size=70x70&data=' + encodeURIComponent(qrText);
    var el = document.getElementById('qrcode');
    if (el) { el.innerHTML = '<img src="' + url + '" style="width:70px;height:70px;">'; }
})();
window.onload = function() { setTimeout(function(){ window.print(); }, 800); };
</script>
</body>
</html>
