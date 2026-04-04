<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>وثيقة تأمين الوافدين للمقيمين - {{ $document->insurance_number }}</title>
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
            padding: 2px 6px;
            border: 1px solid #000;
            text-align: right;
            font-size: 11px;
            color: #000;
            vertical-align: middle;
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
        <div class="header">
            <div class="logo">
                <img src="/img/logo.png" alt="شعار الشركة" onerror="this.style.display='none'; this.parentElement.innerHTML='<div style=&quot;width:85px;height:85px;background:#000;color:#fff;display:flex;align-items:center;justify-content:center;font-size:8px;text-align:center;&quot;>LOGO</div>';" />
            </div>
            <div class="company-info">
                <div class="company-name">شركة المدار الليبي للتأمين Al Madar Libyan Insurance </div>
                <div class="document-title">وثيقة تأمين الوافدين للمقيمين Expatriate Insurance Document </div>
                <div class="legal-text"> الوثيقه مطابقه للقانون رقم(17) للعام 1986 م The document complies with Law No. (17) of the year 1986 </div>
            </div>
            <div class="qr-code" id="qrcode"></div>
        </div>

        @php
            $mainPassenger = $document->passengers->where('is_main_passenger', true)->first();
            $familyMembers = $document->passengers->where('is_main_passenger', false);
        @endphp

        <div class="section">
            <table class="two-column-table">
                <tr>
                    <td colspan="15" style="text-align:center;"><nobr>بيانات الوثيقة Document Data</nobr></td>
                </tr>
                <tr>
                    <td colspan="2" style="text-align:center;"><nobr>تاريخ الإصدار Issue Date</nobr></td>
                    <td colspan="4" style="text-align:center;"><nobr>{{ \Carbon\Carbon::parse($document->issue_date)->format('d/m/Y h:i A') }}</nobr></td>
                    <td colspan="2" style="text-align:center;"><nobr>رقم الوثيقة Document Number</nobr></td>
                    <td colspan="7" style="text-align:center;"><nobr>{{ $document->insurance_number }}</nobr></td>
                </tr>
                <tr>
                    <td colspan="2" style="text-align:center;"><nobr>إلى (12:00) ظهرا It starts noon</nobr></td>
                    <td colspan="4" style="text-align:center;"><nobr>{{ $printData['end_date'] }}</nobr></td>
                    <td colspan="2" style="text-align:center;"><nobr>من (12:00) ظهرا End of noon</nobr></td>
                    <td colspan="7" style="text-align:center;"><nobr>{{ $printData['start_date'] }}</nobr></td>
                </tr>
                <tr>
                    <td colspan="6" style="text-align:center;"><nobr>مدة التأمين Document duration</nobr></td>
                    <td colspan="9" style="text-align:center;">
                        @php
                            $durationDays = 0;
                            switch($document->duration) {
                                case 'خمس أيام': $durationDays = 5; break;
                                case 'أسبوع (7 أيام)': $durationDays = 7; break;
                                case 'عشرة أيام': $durationDays = 10; break;
                                case 'أسبوعين (14 يوم)': $durationDays = 14; break;
                                case 'ثلاثة أسابيع (21 يوم)': $durationDays = 21; break;
                                case 'شهر (30 يوم)': $durationDays = 30; break;
                                case 'شهرين (60 يوم)': $durationDays = 60; break;
                                case 'ثلاثة أشهر (90 يوم)': $durationDays = 90; break;
                                case 'ستة أشهر (180 يوم)': $durationDays = 180; break;
                                case 'سنة (365 يوم)': $durationDays = 365; break;
                                case 'سنتين (730 يوم)': $durationDays = 730; break;
                            }
                        @endphp
                        {{ $durationDays > 0 ? $durationDays . ' يوم' : ($document->duration ?? '-') }}
                    </td>
                </tr>
                @if($document->geographic_area)
                <tr>
                    <td colspan="6" style="text-align:center;"><nobr>المنطقة الجغرافية Geographic area</nobr></td>
                    <td colspan="9" style="text-align:center;"><nobr>{{ $document->geographic_area }}</nobr></td>
                </tr>
                @endif
                @if($document->residence_type)
                <tr>
                    <td colspan="6" style="text-align:center;"><nobr>نوع الإقامة Residence type</nobr></td>
                    <td colspan="9" style="text-align:center;"><nobr>{{ $document->residence_type }}</nobr></td>
                </tr>
                @endif
                @if(!is_null($document->residence_duration))
                <tr>
                    <td colspan="6" style="text-align:center;"><nobr>مدة الإقامة Residence duration</nobr></td>
                    <td colspan="9" style="text-align:center;"><nobr>{{ $document->residence_duration }} يوم</nobr></td>
                </tr>
                @endif
            </table>
        </div>

        <div class="section">
            <table class="two-column-table">
                <tr>
                    <td colspan="15" style="text-align:center;"><nobr>بيانات المؤمن Insured data</nobr></td>
                </tr>
                <tr>
                    <td colspan="2" style="text-align:center;"><nobr>الاسم بالعربي Name in Arabic</nobr></td>
                    <td colspan="7" style="text-align:center;"><nobr>{{ $mainPassenger->name_ar ?? '-' }}</nobr></td>
                    <td colspan="2" style="text-align:center;"><nobr>رقم الهاتف Phone number</nobr></td>
                    <td colspan="4" style="text-align:center;"><nobr>{{ $mainPassenger->phone ?? '-' }}</nobr></td>
                </tr>
                <tr>
                    <td colspan="2" style="text-align:center;"><nobr>الاسم بالإنجليزي Name in English</nobr></td>
                    <td colspan="7" style="text-align:center;"><nobr>{{ $mainPassenger->name_en ?? '-' }}</nobr></td>
                    <td colspan="2" style="text-align:center;"><nobr>رقم الجواز Passport number</nobr></td>
                    <td colspan="4" style="text-align:center;"><nobr>{{ $mainPassenger->passport_number ?? '-' }}</nobr></td>
                </tr>
                <tr>
                    <td colspan="2" style="text-align:center;"><nobr>الجنس Gender</nobr></td>
                    <td colspan="7" style="text-align:center;"><nobr>
                        @if($mainPassenger?->gender == 'ذكر')
                            ذكر Male
                        @elseif($mainPassenger?->gender == 'أنثى')
                            أنثى Female
                        @else
                            {{ $mainPassenger->gender ?? '-' }}
                        @endif
                    </nobr></td>
                    <td colspan="2" style="text-align:center;"><nobr>العنوان Address</nobr></td>
                    <td colspan="4" style="text-align:center;"><nobr>{{ $mainPassenger->address ?? '-' }}</nobr></td>
                </tr>
                <tr>
                    <td colspan="2" style="text-align:center;"><nobr>الجنسية Nationality</nobr></td>
                    <td colspan="7" style="text-align:center;"><nobr>{{ $mainPassenger->nationality ?? '-' }}</nobr></td>
                    <td colspan="2" style="text-align:center;"><nobr>تاريخ الميلاد Date of birth</nobr></td>
                    <td colspan="4" style="text-align:center;"><nobr>{{ $mainPassenger?->birth_date ? \Carbon\Carbon::parse($mainPassenger->birth_date)->format('d/m/Y') : '-' }}</nobr></td>
                </tr>
                @if($familyMembers->count() > 0)
                <tr>
                    <td colspan="15" style="text-align:center;"><nobr>أفراد العائلة Family members</nobr></td>
                </tr>
                <tr>
                    <td colspan="2" style="text-align:center;"><nobr>الصلة Relationship</nobr></td>
                    <td colspan="2" style="text-align:center;"><nobr>الاسم AR Name in Arabic</nobr></td>
                    <td colspan="2" style="text-align:center;"><nobr>الاسم EN Name in English</nobr></td>
                    <td colspan="2" style="text-align:center;"><nobr>النوع Gender</nobr></td>
                    <td colspan="2" style="text-align:center;"><nobr>تاريخ الميلاد Date of birth</nobr></td>
                    <td colspan="1" style="text-align:center;"><nobr>العمر Age</nobr></td>
                    <td colspan="2" style="text-align:center;"><nobr>رقم الجواز Passport number</nobr></td>
                    <td colspan="2" style="text-align:center;"><nobr>القسط</nobr></td>
                </tr>
                @php
                    $totalFamilyPremium = $document->family_members_premium ?? 0;
                    $memberPremium = $familyMembers->count() > 0 ? ($totalFamilyPremium / $familyMembers->count()) : 0;
                @endphp
                @foreach($familyMembers as $member)
                <tr>
                    <td colspan="2" style="text-align:center;"><nobr>{{ $member->relationship ?? '-' }}</nobr></td>
                    <td colspan="2" style="text-align:center;"><nobr>{{ $member->name_ar ?? '-' }}</nobr></td>
                    <td colspan="2" style="text-align:center;"><nobr>{{ $member->name_en ?? '-' }}</nobr></td>
                    <td colspan="2" style="text-align:center;"><nobr>
                        @if($member->gender == 'ذكر')
                            ذكر Male
                        @elseif($member->gender == 'أنثى')
                            أنثى Female
                        @else
                            {{ $member->gender ?? '-' }}
                        @endif
                    </nobr></td>
                    <td colspan="2" style="text-align:center;"><nobr>{{ $member->birth_date ? \Carbon\Carbon::parse($member->birth_date)->format('d/m/Y') : '-' }}</nobr></td>
                    <td colspan="1" style="text-align:center;"><nobr>{{ $member->age ?? '-' }}</nobr></td>
                    <td colspan="2" style="text-align:center;"><nobr>{{ $member->passport_number ?? '-' }}</nobr></td>
                    <td colspan="2" style="text-align:center;"><nobr>{{ number_format($memberPremium, 3) }}</nobr></td>
                </tr>
                @endforeach
                <tr>
                    <td colspan="13" style="text-align:center;"><nobr>مجموع قسط أفراد العائلة</nobr></td>
                    <td colspan="2" style="text-align:center;"><nobr>{{ number_format($totalFamilyPremium, 3) }}</nobr></td>
                </tr>
                @endif
            </table>
        </div>

        <div class="section">
            <table class="two-column-table">
                <tr>
                    <td colspan="6" style="text-align:center;"><nobr>احتساب القسط - البيانات المالية</nobr></td>
                    <td colspan="9" style="text-align:center;font-weight:bold;"><nobr>الشركة الصادرة - معد الوثيقة</nobr></td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align:center;"><nobr>قيمة القسط المقرر</nobr></td>
                    <td colspan="3" style="text-align:center;"><nobr>{{ number_format($document->premium, 3) }}</nobr></td>
                    <td colspan="3" style="text-align:center;"><nobr>{{ $printData['agency_name'] ?? 'المدار الليبي للتأمين' }}</nobr></td>
                    <td colspan="4" style="font-weight:bold;text-align:center;"><nobr>{{ $printData['agency_code'] ?? 'ML0001' }}</nobr></td>
                    <td colspan="2" style="text-align:center;"><nobr>{{ $printData['agent_name'] ?? 'محمد علي' }}</nobr></td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align:center;"><nobr>الضريبــــــــــــــــــــــــة</nobr></td>
                    <td colspan="3" style="text-align:center;"><nobr>{{ number_format($document->tax, 3) }}</nobr></td>
                    <td colspan="5" style="text-align:center;"><nobr>وقت الاعداد</nobr></td>
                    <td colspan="4" style="text-align:center;"><nobr>{{ \Carbon\Carbon::parse($document->issue_date)->format('d/m/y H:i:s') }}</nobr></td>
                </tr>
                @if($document->family_members_premium > 0)
                <tr>
                    <td colspan="3" style="text-align:center;"><nobr>قسط أفراد العائلة</nobr></td>
                    <td colspan="3" style="text-align:center;"><nobr>{{ number_format($document->family_members_premium, 3) }}</nobr></td>
                    <td colspan="9"></td>
                </tr>
                @endif
                <tr>
                    <td colspan="3" style="text-align:center;"><nobr>الدمـغــــــــــــــــــــــــة</nobr></td>
                    <td colspan="3" style="text-align:center;"><nobr>{{ number_format($document->stamp, 3) }}</nobr></td>
                    <td colspan="5" style="text-align:center;"><nobr>التوقــيــع والخـــتـــم:</nobr></td>
                    <td colspan="4" style="text-align:center;"></td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align:center;"><nobr>مصاريف الاصـدار</nobr></td>
                    <td colspan="3" style="text-align:center;"><nobr>{{ number_format($document->issue_fees, 3) }}</nobr></td>
                    <td colspan="9" style="text-align:center;"></td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align:center;"><nobr>رســوم الاشــــــــراف</nobr></td>
                    <td colspan="3" style="text-align:center;"><nobr>{{ number_format($document->supervision_fees, 3) }}</nobr></td>
                    <td colspan="9" style="text-align:center;"></td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align:center;"><nobr>الاجمـــ رقــم ـــالي</nobr></td>
                    <td colspan="3" style="text-align:center;"><nobr>{{ number_format($document->total, 3) }}</nobr></td>
                    <td colspan="9"></td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align:center;"><nobr>الاجمــ حروف ـالي</nobr></td>
                    <td colspan="12" style="text-align:center;"><nobr>{{ $printData['total_in_words'] }}</nobr></td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align:center;"><nobr>الملاحظة Note</nobr></td>
                    <td colspan="12" style="text-align:center;"><nobr>لا يتم تغطية أي حالة طارئة إلا عن طريق المعيد ( دار الصحة)</nobr></td>
                </tr>
            </table>
        </div>

        <div class="section">
            <table class="two-column-table" style="width: 100%; border-collapse: collapse; margin-bottom: 8px; font-size: 9px;">
            
                <tr>
                    <td colspan="15" style="line-height:13px;direction:rtl;text-align:center;vertical-align:top;font-size:12px;font-weight:normal;padding:6px;">
                        (( وثيقة مدمجة وتحتوي على تغطيات المسؤولية المهنية والحوادث الشخصية لحماية العمالة الوافدة ))<br>
                        ((An integrated document that includes professional liability and personal accident coverage to protect migrant workers))<br><br>
                        <strong>Professional (Engineering) Liability  المسؤولية المهنية (هندسية)</strong><br>
                        وتغطي هذه الوثيقة المسؤولية القانونية لأصحاب المهن الهندسية والتي تخص مشاريع الإنشاء وتكون سبب مباشر في الأضرار المادية والجسمانية للطرف الثالث نتيجة الإهمال او الخطأ أو التقصير(حسب مقتضى الحال) من قبل المؤمن عليه خلال فترة سريان هذه البوليصة وفق شروط واستثناءات هذه الوثيقة ووفق الإجراءات القانونية النافذة .<br>
                        This document covers the legal liability of engineering professionals regarding construction projects and any direct material or bodily damages to a third party resulting from negligence, error, or omission (as applicable) by the insured during the validity period of this policy, in accordance with the terms and exclusions of this document and the applicable legal procedures.<br><br>
                        <strong>Personal Accident Document وثيقة الحوادث الشخصية</strong><br>
                        تغطي هذه الوثيقة الحوادث الجسمانية التي يتعرض لها المؤمن عليهم بموجب هذه الوثيقة ويشكل مفاجئ وعرض عنيفة وخارجة وظاهره ومستقلة عن أي سبب آخر ينشأ عنها وحدها وفي خلال ثلاثة أشهر من تاريخ وقوع الحادث أو الوفاة.<br>
                        كما هو موضح شروط واستثناءات الوثيقة.<br>
                        This document covers bodily accidents that the insured are subjected to under this policy, which are sudden, violent, external, apparent, and independent of any other cause, occurring alone and within three months from the date of the accident or death.<br>
                        The terms and exclusions of the policy are also outlined.
                    </td>
                </tr>
            </table>
        </div>
    </div>

    @php
        $qrDataJson = json_encode($printData['qr_data']);
    @endphp
    <script>
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

