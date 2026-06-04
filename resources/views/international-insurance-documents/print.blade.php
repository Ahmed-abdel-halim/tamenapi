<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>بطاقة تأمين دولي - {{ $document->document_number }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        @page { size: A4 portrait; margin: 6mm 8mm; }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Tajawal','Arial',sans-serif; font-size:9.5px; color:#000; background:#fff; direction:rtl; }
        .card { width:100%; border:2px solid #000; }
        table { border-collapse:collapse; width:100%; }
        td,th { border:1px solid #000; padding:2.5px 5px; vertical-align:middle; font-size:9px; }
        b { font-weight:800; }
        .hdr-blk { background:#000; color:#fff; text-align:center; font-weight:800; font-size:9.5px; padding:3px 5px; }
        .lbl { font-weight:800; background:#f0f0f0; white-space:nowrap; width:115px; }
        .val { font-weight:600; }
        .chk { display:inline-block; width:11px; height:11px; border:1px solid #555; vertical-align:middle; text-align:center; font-size:9px; font-weight:900; line-height:11px; margin-left:2px; }
        .chk-on { background:#000; color:#fff; }
    </style>
</head>
<body>
<div class="card">

{{-- ============ HEADER ============ --}}
<table>
<tr>
    {{-- شعار الاتحاد + شعار المدار --}}
    <td style="width:105px; text-align:center; padding:5px 3px; border:1px solid #000;">
        <svg width="52" height="52" viewBox="0 0 100 100">
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
        <img src="/img/logo.png" alt="" style="width:46px;height:46px;object-fit:contain;margin-top:3px;" onerror="this.style.display='none'">
        <div style="font-size:6px;font-weight:800;color:#1a4a8a;margin-top:2px;line-height:1.3;">المدار الليبي<br>للتأمين</div>
    </td>

    {{-- العنوان --}}
    <td style="text-align:center; padding:7px 4px; border:1px solid #000;">
        <div style="font-size:20px;font-weight:900;line-height:1.2;">بطاقة التأمين العربية الموحدة</div>
        <div style="font-size:13px;font-weight:700;margin-top:3px;">عن سير السيارات (المركبات) عبر البلاد العربية</div>
        <div style="font-size:16px;font-weight:900;color:#cc0000;margin-top:4px;">للمركبات الليبية</div>
    </td>

    {{-- علم ليبيا --}}
    <td style="width:95px; text-align:center; padding:5px; border:1px solid #000;">
        <svg width="75" height="86" viewBox="0 0 75 86">
            <rect x="1" y="1" width="73" height="84" rx="3" fill="none" stroke="#000" stroke-width="1.5"/>
            <rect x="1" y="1" width="73" height="29" fill="#000"/>
            <rect x="1" y="30" width="73" height="14" fill="#cc0000"/>
            <rect x="1" y="44" width="73" height="41" fill="#239e45"/>
            <circle cx="37" cy="44" r="12" fill="none" stroke="#fff" stroke-width="9"/>
            <circle cx="41" cy="44" r="12" fill="#cc0000"/>
            <polygon points="37,28 39,34 45,34 40,38 42,44 37,40 32,44 34,38 29,34 35,34" fill="#fff"/>
            <line x1="1" y1="30" x2="74" y2="30" stroke="#000" stroke-width="0.5"/>
            <line x1="1" y1="44" x2="74" y2="44" stroke="#000" stroke-width="0.5"/>
        </svg>
    </td>
</tr>
</table>

{{-- ============ COMPANY + QR + AGENT ============ --}}
<table>
<tr>
    {{-- بيانات الشركة المصدرة (المدار) --}}
    <td style="width:37%; border:1px solid #000; padding:4px 6px; vertical-align:top; font-size:8.5px;">
        <div style="font-weight:900;font-size:9.5px;text-align:center;border-bottom:1px solid #ccc;padding-bottom:2px;margin-bottom:3px;">الشركة المصدرة للبطاقة</div>
        <div style="line-height:1.75;">
            <div><b>الشركة: </b>المدار الليبي للتأمين</div>
            <div><b>العنوان: </b>طرابلس</div>
            <div><b>صندوق البريد: </b>1002</div>
            <div><b>الهاتف: </b>021-3614278</div>
            <div><b>الفاكس: </b>021-3614279</div>
            <div><b>البريد الإلكتروني: </b>info@mli.ly</div>
        </div>
    </td>

    {{-- QR + رقم الوثيقة --}}
    <td style="width:26%; border:1px solid #000; text-align:center; vertical-align:middle; padding:4px;">
        <div id="qrcode" style="width:72px;height:72px;margin:0 auto 3px;"></div>
        <div style="font-size:16px;font-weight:900;color:#cc0000;line-height:1.1;">{{ $document->document_number }}</div>
        <div style="font-size:7.5px;"><b>تاريخ الإصدار: </b>{{ \Carbon\Carbon::parse($document->issue_date)->format('d/m/Y') }}</div>
        <div style="font-size:7.5px;"><b>الوقت: </b>{{ \Carbon\Carbon::parse($document->issue_date)->format('H:i:s') }}</div>
        @if($document->external_policy_number)
        <div style="font-size:7.5px;color:#cc0000;font-weight:800;"><b>LIFO: </b>{{ $document->external_policy_number }}</div>
        @endif
    </td>

    {{-- بيانات المكتب المحلي / الوكيل --}}
    <td style="border:1px solid #000; padding:4px 6px; vertical-align:top; font-size:8.5px;">
        <div style="font-weight:900;font-size:9.5px;text-align:center;border-bottom:1px solid #ccc;padding-bottom:2px;margin-bottom:3px;">المكتب الموحد المحلي</div>
        <div style="line-height:1.75;">
            <div><b>اسم المكتب: </b>{{ $printData['agency_name'] ?? 'الإدارة العامة' }}</div>
            <div><b>كود الوكيل: </b>{{ $printData['agency_code'] ?? 'ML0001' }}</div>
            <div><b>معد الوثيقة: </b>{{ $printData['agent_name'] ?? 'الإدارة' }}</div>
            <div><b>الهاتف: </b>021-3614278</div>
            <div><b>البريد: </b>info@mli.ly</div>
        </div>
    </td>
</tr>
</table>

{{-- ============ INSURED DATA ============ --}}
<table>
<tr>
    <td class="lbl" style="width:110px;">اسم المؤمن له</td>
    <td class="val" style="font-weight:800;font-size:11px;">{{ $document->insured_name ?? '-' }}</td>
    <td class="lbl" style="width:70px;">الهاتف</td>
    <td class="val" style="width:120px;">{{ $document->phone ?? '-' }}</td>
</tr>
<tr>
    <td class="lbl">العنوان</td>
    <td class="val" colspan="3">{{ $document->insured_address ?? '-' }} &nbsp;|&nbsp; واتساب: {{ $document->whatsapp_number ?? '-' }}</td>
</tr>
</table>

{{-- ============ VEHICLE DATA (2 columns) ============ --}}
<table>
<tr>
    {{-- العمود الأيسر --}}
    <td class="lbl" style="width:110px;">نوع المركبة</td>
    <td class="val" style="width:140px;">
        @if($document->vehicleType){{ $document->vehicleType->brand }}@else -- @endif
    </td>
    <td class="lbl" style="width:110px;">جنسية المركبة</td>
    <td class="val">{{ $document->vehicle_nationality ?? 'ليبية' }}</td>
</tr>
<tr>
    <td class="lbl">سنة الصنع</td>
    <td class="val">{{ $document->year ?? '-' }}</td>
    <td class="lbl">رقم الهيكل (الشاصي)</td>
    <td class="val">{{ $document->chassis_number ?? '-' }}</td>
</tr>
<tr>
    <td class="lbl">رقم اللوحة (الموتور)</td>
    <td class="val" style="font-weight:900;font-size:12px;color:#cc0000;">{{ $document->plate_number ?? '-' }}</td>
    <td class="lbl">رقم المحرك (الموتور)</td>
    <td class="val">{{ $document->plate_number ?? '-' }}</td>
</tr>
<tr>
    <td class="lbl">الغرض من الاستعمال</td>
    <td class="val">
        @if($document->vehicleType && $document->vehicleType->category){{ $document->vehicleType->category }}@else خاصة @endif
    </td>
    <td class="lbl">البلد المزار</td>
    <td class="val" style="font-weight:900;">{{ $document->visited_country ?? '-' }}</td>
</tr>
</table>

{{-- ============ VALIDITY DATES ============ --}}
@php
    $startCarbon = \Carbon\Carbon::parse($document->start_date);
    $endCarbon = \Carbon\Carbon::parse($document->end_date);
    $arabicDays = ['Sunday'=>'الأحد','Monday'=>'الاثنين','Tuesday'=>'الثلاثاء','Wednesday'=>'الأربعاء','Thursday'=>'الخميس','Friday'=>'الجمعة','Saturday'=>'السبت'];
    $arabicMonths = [1=>'يناير',2=>'فبراير',3=>'مارس',4=>'أبريل',5=>'مايو',6=>'يونيو',7=>'يوليو',8=>'أغسطس',9=>'سبتمبر',10=>'أكتوبر',11=>'نوفمبر',12=>'ديسمبر'];
    $startDay = $arabicDays[$startCarbon->format('l')] ?? $startCarbon->format('l');
    $endDay = $arabicDays[$endCarbon->format('l')] ?? $endCarbon->format('l');
    $startDateAr = $startCarbon->format('d') . '/' . ($arabicMonths[(int)$startCarbon->format('m')] ?? $startCarbon->format('m')) . '/' . $startCarbon->format('Y');
    $endDateAr = $endCarbon->format('d') . '/' . ($arabicMonths[(int)$endCarbon->format('m')] ?? $endCarbon->format('m')) . '/' . $endCarbon->format('Y');
@endphp
<table>
<tr>
    <td class="lbl" style="width:110px;">سريان التأمينيـن</td>
    <td style="font-size:9px; width:50%;">
        <b>من الساعة: </b>12:00 ظهراً &nbsp;|&nbsp; <b>يوم: </b>{{ $startDay }} &nbsp;|&nbsp; <b>الموافق: </b>{{ $startDateAr }}
    </td>
    <td style="font-size:9px;">
        <b>إلى الساعة: </b>12:00 ظهراً &nbsp;|&nbsp; <b>يوم: </b>{{ $endDay }} &nbsp;|&nbsp; <b>الموافق: </b>{{ $endDateAr }}
    </td>
</tr>
</table>

{{-- ============ COUNTRIES ============ --}}
@php
$countriesRow1 = ['عمان','العراق','سوريا','الجزائر','تونس','البحرين','الإمارات'];
$countriesRow2 = ['الأردن','اليمن','مصر','ليبيا','لبنان','الكويت','قطر'];
$visitedRaw = $document->visited_country ?? '';
$visitedLow = mb_strtolower($visitedRaw);
@endphp
<table>
<tr><td class="hdr-blk" colspan="7">البلاد التي تسري فيها البطاقة</td></tr>
<tr>
    @foreach($countriesRow1 as $c)
    @php
        $cLow = mb_strtolower($c);
        $on = (mb_strpos($visitedLow, $cLow) !== false)
           || (mb_strpos($cLow, $visitedLow) !== false && mb_strlen($visitedLow) > 2);
    @endphp
    <td style="text-align:center;padding:3px 1px;font-size:8.5px;white-space:nowrap;">
        <span class="chk {{ $on ? 'chk-on' : '' }}">{!! $on ? '&#10003;' : '&nbsp;' !!}</span>&nbsp;{{ $c }}
    </td>
    @endforeach
</tr>
<tr>
    @foreach($countriesRow2 as $c)
    @php
        $cLow = mb_strtolower($c);
        $on = (mb_strpos($visitedLow, $cLow) !== false)
           || (mb_strpos($cLow, $visitedLow) !== false && mb_strlen($visitedLow) > 2);
    @endphp
    <td style="text-align:center;padding:3px 1px;font-size:8.5px;white-space:nowrap;">
        <span class="chk {{ $on ? 'chk-on' : '' }}">{!! $on ? '&#10003;' : '&nbsp;' !!}</span>&nbsp;{{ $c }}
    </td>
    @endforeach
</tr>
</table>

{{-- ============ LEGAL / BUREAU INFO ============ --}}
<table>
<tr>
    <td class="hdr-blk" style="width:60px;">البلد</td>
    <td class="hdr-blk">بيان مختصر عن نوعية التغطيات طبقاً لقوانين التأمين الإلزامي في البلاد العربية</td>
</tr>
<tr>
    <td style="text-align:center;font-weight:800;font-size:9px;">تونس</td>
    <td style="font-size:8px;line-height:1.6;padding:3px 6px;">
        85-87 نهج فلسطين - البلفيدير 1002 تونس &nbsp;+21671841784 &nbsp;| buat@buat.com.tn &nbsp;+21671845124<br>
        <b>الأضرار الجسمانية</b> بقيمة محددة <b>والأضرار المادية</b> بقيمة غير محددة
    </td>
</tr>
<tr>
    <td style="text-align:center;font-weight:800;font-size:9px;">الجزائر</td>
    <td style="font-size:8px;line-height:1.6;padding:3px 6px;">
        إقامة شعيلي / واد حيدرة - حيدرة &nbsp;+21321604507 &nbsp;| bua.algerie@gmail.com &nbsp;+21321609295<br>
        <b>الأضرار البدنية</b> بقيمة محددة <b>والأضرار المادية</b> بقيمة غير محددة
    </td>
</tr>
</table>

{{-- ============ GENERAL TERMS ============ --}}
<table>
<tr><td class="hdr-blk">إرشادات وشروط عامة</td></tr>
<tr>
    <td style="font-size:8.5px;line-height:1.65;padding:4px 7px;">
        1. يجب على قائد (سائق) المركبة أن يكون لديه رخصة قيادة وفق قوانين البلد المراد.
        وتعتبر هذه البطاقة الوثيقة الرسمية لأغراض قانون التأمين الإلزامي.<br>
        2. تطبق هذه البطاقة أحكام القانون الثالث (الليبي) الناشئة عن الحوادث المثبتة في هذه الوثيقة ولا تضمن الأضرار التي تلحق بها إذا كانت أيا كان سببها.<br>
        3. لا تزيد المبالغ المضمونة بهذه البطاقة في كل دولة عن الحدود الدنيا المنصوص عليها في قانون التأمين الإلزامي المعمول به في ذلك البلد.<br>
        4. لا تلزم المكتب الموحد بإرسال إشعار (المسؤولية الحدودية / المنشأة الحدودية) الاتصال بالمكتب المنشور للحصول على إرشادات المرور النافع بالاتصال بالمكتب المحلي مباشرة.<br>
        5. في حالة وقوع حادث أو كسر أو تلف في الدولة التي توجد فيها البطاقة، يتوجب على قائد المركبة الاتصال الفوري بالمكتب الموحد أو الوكيل والشركة الأعضاء.<br>
        6. لا يحق للوكيل أو المؤمن له الاعتراف بأي مسؤولية له وذلك بدون الموافقة المكتوبة من أي بلد زار.
        7. للحصول على المزيد من المعلومات يرجى مراجعة قانون التأمين الإلزامي في البلد المصدر للبطاقة / أو البلد المزار.
    </td>
</tr>
</table>

{{-- ============ FINANCIAL TOTAL ============ --}}
<table>
<tr>
    <td style="font-size:11px;font-weight:900;padding:4px 7px;">
        إجمالي القسط والرسوم ( شامل الضرائب الحكومية ) : &nbsp;
        <span style="color:#cc0000;font-size:13px;">{{ number_format($document->total ?? 0, 3) }}</span> د.ل
    </td>
</tr>
<tr>
    <td style="font-size:8.5px;padding:2px 7px;">
        تقوم الشركة المصدرة للبطاقة بمحاسبة مصلحة الضرائب على الرسوم المستحقة.
        &nbsp;&nbsp; <b>الإجمالي بالحروف: </b>{{ $printData['total_in_words'] ?? '' }}
    </td>
</tr>
</table>

{{-- ============ ISSUE INFO ============ --}}
@php
    $issueCarbon = \Carbon\Carbon::parse($document->issue_date);
    $issueDay = $arabicDays[$issueCarbon->format('l')] ?? $issueCarbon->format('l');
    $issueMonth = $arabicMonths[(int)$issueCarbon->format('m')] ?? $issueCarbon->format('m');
@endphp
<table>
<tr>
    <td style="font-size:9px;padding:4px 7px;">
        <b>تحريراً في يوم:</b> {{ $issueCarbon->format('d') }}
        &nbsp;&nbsp;
        <b>الموافق:</b> {{ $issueDay }}
        &nbsp;&nbsp;
        <b>من شهر:</b> {{ $issueMonth }}
        &nbsp;&nbsp;
        <b>سنة:</b> {{ $issueCarbon->format('Y') }}
    </td>
</tr>
</table>

{{-- ============ NOTE ============ --}}
<table>
<tr>
    <td style="padding:4px 7px;font-size:9.5px;font-weight:900;color:#cc0000;text-align:center;">
        هـام : أي كشط أو شطب أو تعديل في هذه الصفحة يبطل البطاقة وتعد لاغية.
    </td>
</tr>
</table>

</div>

@php
    $qrData = $printData['qr_data'] ?? ['doc' => $document->document_number];
    $qrText = json_encode($qrData, JSON_UNESCAPED_UNICODE);
@endphp
<script>
(function() {
    var txt = @json($qrText);
    var url = 'https://api.qrserver.com/v1/create-qr-code/?size=72x72&data=' + encodeURIComponent(txt);
    var el = document.getElementById('qrcode');
    if (el) { el.innerHTML = '<img src="' + url + '" style="width:72px;height:72px;">'; }
})();
// طباعة تلقائية
window.addEventListener('load', function() {
    setTimeout(function() {
        window.focus();
        window.print();
    }, 900);
});
</script>
</body>
</html>
