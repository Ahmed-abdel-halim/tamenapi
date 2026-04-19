<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>وثيقة تأمين مسافرين - {{ $document->insurance_number }}</title>
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
                <div class="company-name">شركة المدار الليبي للتأمين Al Madar Libyan Insurance </div>
                <div class="document-title">
                    @if(str_contains($document->insurance_type, 'زائرين'))
                        Travel insurance document وثيقة تأمين المسافرين
                    @else
                        وثيقة {{ $document->insurance_type }}
                        @if(str_contains($document->insurance_type, 'مسافرين'))
                            &nbsp;Travel insurance document
                        @endif
                    @endif
                </div>
             
                <div class="legal-text">
                هذه الوثيقة خاضعة لأحكام القانون رقم (3) لسنة 2005م The document complies with Law No. (3) of the year 2005 <br>
                @if(str_contains($document->insurance_type, 'زائرين'))
                   خاص بزائرين دولة ليبيـا  For visitors of the State of Libya 
                @else
                    خاص بالسفر الى ( أوروبا - كندا - أمريكا- استراليا ) This document is valid for travel to ( Europe - Canada - America - Australia )
                @endif
                </div>
            </div>
            <div class="qr-code" id="qrcode"></div>
        </div>

        @php
            $mainPassenger = $document->passengers->where('is_main_passenger', true)->first();
            $familyMembers = $document->passengers->where('is_main_passenger', false);
        @endphp

        <!-- Document Details -->
        <div class="section">
            <table class="two-column-table">
                <tr style="vertical-align:top;">
                    <td colspan="15" style="width:480px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>بيـانــــات&nbsp;الوثيـقــــــة &nbsp;Document&nbsp;data</nobr>
                    </td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>تـــاريــــخ&nbsp;الإصـــدار &nbsp;Issue&nbsp;date</nobr>
                    </td>
                    <td colspan="4" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ \Carbon\Carbon::parse($document->issue_date)->format('d/m/Y h:i A') }}</nobr>
                    </td>
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>رقـــــم&nbsp;الوثيـقــــــــــة &nbsp;Document&nbsp;number</nobr>
                    </td>
                    <td colspan="7" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $document->insurance_number }}</nobr>
                    </td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>إلى&nbsp;(12:00)&nbsp;ظهرا It starts noon</nobr>
                    </td>
                    <td colspan="4" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $printData['end_date'] }}</nobr>
                    </td>
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>مـن&nbsp;(12:00)&nbsp;ظهرا End of noon</nobr>
                    </td>
                    <td colspan="7" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $printData['start_date'] }}</nobr>
                    </td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="6" style="width:239px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>مــدة&nbsp;التأمـــين &nbsp;Duration&nbsp;of&nbsp;insurance</nobr>
                    </td>
                    <td colspan="9" style="width:239px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
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
                <tr style="vertical-align:top;">
                    <td colspan="6" style="width:239px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>المنطقة الجغرافية &nbsp;Geographic&nbsp;area</nobr>
                    </td>
                    <td colspan="9" style="width:239px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        {{ $document->geographic_area }}
                    </td>
                </tr>
                @endif
                @if($document->residence_type)
                <tr style="vertical-align:top;">
                    <td colspan="6" style="width:239px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>نوع الإقامة &nbsp;Residence&nbsp;type</nobr>
                    </td>
                    <td colspan="9" style="width:239px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        {{ $document->residence_type }}
                    </td>
                </tr>
                @endif
                @if(!is_null($document->residence_duration))
                <tr style="vertical-align:top;">
                    <td colspan="6" style="width:239px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>مدة الإقامة &nbsp;Residence&nbsp;duration</nobr>
                    </td>
                    <td colspan="9" style="width:239px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        {{ $document->residence_duration }} يوم
                    </td>
                </tr>
                @endif
            </table>
        </div>

        <!-- Passenger Details -->
        <div class="section">
            <table class="two-column-table">
                <tr style="vertical-align:top;">
                    <td colspan="15" style="width:480px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>بيانات المؤمن &nbsp;Insured&nbsp;data</nobr>
                    </td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>الاسم بالعربي &nbsp;Name&nbsp;in&nbsp;Arabic</nobr>

                </td>
                    <td colspan="7" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $mainPassenger->name_ar ?? '-' }}</nobr>
                    </td>
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                    <nobr>رقـــــم&nbsp;الهــــــــــــــــاتـــف &nbsp;Phone&nbsp;number</nobr>

                    </td>
                    <td colspan="4" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $mainPassenger->phone ?? '-' }}</nobr>
                    </td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                    <nobr>الاسم بالإنجليزي &nbsp;Name&nbsp;in&nbsp;English</nobr>

                    </td>
                    <td colspan="7" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $mainPassenger->name_en ?? '-' }}</nobr>
                    </td>
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                    <nobr>رقم الجواز &nbsp;Passport&nbsp;number</nobr>

                </td>
                    <td colspan="4" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $mainPassenger->passport_number ?? '-' }}</nobr>
                    </td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>الجنس &nbsp;Gender</nobr>
                    </td>
                    <td colspan="7" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>
                            @if($mainPassenger->gender == 'ذكر')
                                ذكر Male
                            @elseif($mainPassenger->gender == 'أنثى')
                                أنثى Female
                            @else
                                {{ $mainPassenger->gender ?? '-' }}
                            @endif
                        </nobr>
                    </td>
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>العنوان &nbsp;Address</nobr>
                    </td>
                    <td colspan="4" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $mainPassenger->address ?? '-' }}</nobr>
                    </td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>الجنسية &nbsp;Nationality</nobr>
                    </td>
                    <td colspan="7" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $mainPassenger->nationality ?? '-' }}</nobr>
                    </td>
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>تاريخ الميلاد &nbsp;Birth&nbsp;date</nobr>
                    </td>
                    <td colspan="4" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $mainPassenger->birth_date ? \Carbon\Carbon::parse($mainPassenger->birth_date)->format('d/m/Y') : '-' }}</nobr>
                    </td>
                </tr>
                
                @if($familyMembers->count() > 0)
                <tr style="vertical-align:top;">
                    <td colspan="15" style="width:480px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>أفراد العائلة &nbsp;Family&nbsp;members</nobr>
                    </td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>الصلة &nbsp;Relationship</nobr>
                    </td>
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>الاسم AR &nbsp;Name&nbsp;in&nbsp;Arabic</nobr>
                    </td>
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>الاسم EN &nbsp;Name&nbsp;in&nbsp;English</nobr>
                    </td>
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>النوع &nbsp;Gender</nobr>
                    </td>
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>تاريخ الميلاد &nbsp;Birth&nbsp;date</nobr>
                    </td>
                    <td colspan="1" style="width:40px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>العمر &nbsp;Age</nobr>
                    </td>
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>رقم الجواز &nbsp;Passport&nbsp;number</nobr>
                    </td>
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>القسط</nobr>
                    </td>
                </tr>
                @php
                    // استخدام القيمة المحفوظة في قاعدة البيانات لقسط أفراد العائلة
                    // تقسيم القيمة الإجمالية على عدد أفراد العائلة للحصول على القسط لكل فرد
                    $totalFamilyPremium = $document->family_members_premium ?? 0;
                    $memberPremium = $familyMembers->count() > 0 ? ($totalFamilyPremium / $familyMembers->count()) : 0;
                @endphp
                @foreach($familyMembers as $member)
                <tr style="vertical-align:top;">
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $member->relationship ?? '-' }}</nobr>
                    </td>
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $member->name_ar ?? '-' }}</nobr>
                    </td>
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $member->name_en ?? '-' }}</nobr>
                    </td>
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>
                            @if($member->gender == 'ذكر')
                                ذكر Male
                            @elseif($member->gender == 'أنثى')
                                أنثى Female
                            @else
                                {{ $member->gender ?? '-' }}
                            @endif
                        </nobr>
                    </td>
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $member->birth_date ? \Carbon\Carbon::parse($member->birth_date)->format('d/m/Y') : '-' }}</nobr>
                    </td>
                    <td colspan="1" style="width:40px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $member->age ?? '-' }}</nobr>
                    </td>
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $member->passport_number ?? '-' }}</nobr>
                    </td>
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ number_format($memberPremium, 3) }}</nobr>
                    </td>
                </tr>
                @endforeach
                <tr style="vertical-align:top;">
                    <td colspan="13" style="width:400px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>مجموع قسط أفراد العائلة</nobr>
                    </td>
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ number_format($totalFamilyPremium, 3) }}</nobr>
                    </td>
                </tr>
                @endif
                
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
                        <nobr>{{ $printData['agent_name'] ?? 'الإدارة' }}</nobr>
                    </td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="3" style="width:125px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>الضريبــــــــــــــــــــــــة</nobr>
                    </td>
                    <td colspan="3" style="width:112px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>0.500</nobr>
                    </td>
                    <td colspan="5" style="width:121px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>وقت&nbsp;الاعداد</nobr>
                    </td>
                    <td colspan="4" style="width:116px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ \Carbon\Carbon::parse($document->issue_date)->format('d/m/y H:i:s') }}</nobr>
                    </td>
                </tr>
                @if($document->family_members_premium > 0)
                <tr style="vertical-align:top;">
                    <td colspan="3" style="width:125px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>قسط أفراد العائلة</nobr>
                    </td>
                    <td colspan="3" style="width:112px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ number_format($document->family_members_premium, 3) }}</nobr>
                    </td>
                    <td colspan="9" style="width:239px;height:12px;"></td>
                </tr>
                @endif
                <tr style="vertical-align:top;">
                    <td colspan="3" style="width:125px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>الدمـغــــــــــــــــــــــــة</nobr>
                    </td>
                    <td colspan="3" style="width:112px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ number_format($document->stamp, 3) }}</nobr>
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
                        <nobr>{{ number_format($document->issue_fees, 3) }}</nobr>
                    </td>
                    <td colspan="9" style="width:239px;height:12px;"></td>
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
                <tr style="vertical-align:top;">
                    <td colspan="3" style="width:125px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>الملاحظة&nbsp;Note</nobr>
                    </td>
                    <td colspan="12" style="width:353px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>لا يتم تغطية أي حالة طارئة إلا عن طريق المعيد ( دار الصحة)</nobr>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Terms and Conditions -->
        <div class="section">
            <table class="two-column-table" style="width: 100%; border-collapse: collapse; margin-bottom: 8px; font-size: 8px;">
                <tr style="vertical-align:top;">
                    <td colspan="15" style="width:481px;height:auto;line-height:12px;direction:rtl;text-align:right;vertical-align:top;font-size:8px;font-weight:normal;padding:4px;">
                        @if(str_contains($document->insurance_type, 'زائرين'))
                            <img src="/img/00.png" alt="شروط عامة" style="width: 100%; height: auto; max-width: 100%; display: block;" onerror="this.style.display='none';" />
                        @else
                            <img src="/img/1111.jpg" alt="شروط عامة" style="width: 100%; height: auto; max-width: 100%; display: block;" onerror="this.style.display='none';" />
                        @endif
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
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>

