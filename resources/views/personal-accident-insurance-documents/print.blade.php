<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>وثيقة تأمين الحوادث الشخصية - {{ $document->insurance_number }}</title>
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
                <div class="document-title" style="font-size: 15px; font-weight: 700; color: #000; margin-bottom: 6px;">وثيقة تأمين الحوادث الشخصية Personal Accident Insurance Document</div>
            </div>
            <div class="qr-code" id="qrcode"></div>
        </div>

        <!-- Document Details -->
        <div class="section">
            <table class="two-column-table">
                <tr style="vertical-align:top;">
                    <td colspan="15" style="width:480px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>بيـانــــات الوثيـقــــــة Document Data</nobr>
                    </td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>رقـــــم الوثيـقــــــــــة Document Number</nobr>
                    </td>
                    <td colspan="4" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $printData['insurance_number'] }}</nobr>
                    </td>
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>تـــاريــــخ الإصـــدار Issue Date</nobr>
                    </td>
                    <td colspan="7" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $printData['issue_date'] }}</nobr>
                    </td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>مـن (12:00) ظهرا It starts noon</nobr>
                    </td>
                    <td colspan="4" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $printData['start_date'] }}</nobr>
                    </td>
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>إلى (12:00) ظهرا End of noon</nobr>
                    </td>
                    <td colspan="7" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $printData['end_date'] }}</nobr>
                    </td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="6" style="width:239px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>مــدة التأمـــين Document duration</nobr>
                    </td>
                    <td colspan="9" style="width:239px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        {{ $printData['duration'] }}
                    </td>
                </tr>
            </table>
        </div>

        <!-- Insured Details -->
        <div class="section">
            <table class="two-column-table">
                <tr style="vertical-align:top;">
                    <td colspan="15" style="width:480px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>بيانات المؤمن Insured data</nobr>
                    </td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>اسم المؤمن له Insured name</nobr>
                    </td>
                    <td colspan="7" style="width:237px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $printData['name'] }}</nobr>
                    </td>
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>هاتف/واتساب Phone/WA</nobr>
                    </td>
                    <td colspan="4" style="width:85px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $printData['phone'] }} / {{ $document->whatsapp_number ?? '-' }}</nobr>
                    </td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>رقم الجواز Passport number</nobr>
                    </td>
                    <td colspan="7" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $printData['id_proof'] }}</nobr>
                    </td>
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>اسم الموكل للمطالبات Claim authorized name</nobr>
                    </td>
                    <td colspan="4" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $printData['claim_authorized_name'] }}</nobr>
                    </td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>العنوان Address</nobr>
                    </td>
                    <td colspan="7" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $printData['address'] }}</nobr>
                    </td>
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>الجنس Gender</nobr>
                    </td>
                    <td colspan="4" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $printData['gender'] }}</nobr>
                    </td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>تاريخ الميلاد Date of birth</nobr>
                    </td>
                    <td colspan="7" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $printData['birth_date'] }}</nobr>
                    </td>
                    <td colspan="2" style="width:79px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>الجنسية Nationality</nobr>
                    </td>
                    <td colspan="4" style="width:158px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $printData['nationality'] }}</nobr>
                    </td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="6" style="width:239px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>المهنة Profession</nobr>
                    </td>
                    <td colspan="9" style="width:239px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $printData['profession'] }}</nobr>
                    </td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="6" style="width:239px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>احتساب القسط - البيانات المالية</nobr>
                    </td>
                    <td colspan="9" style="width:239px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;font-weight:bold;">
                        <nobr>الشركة الصــادرة - معد الوثيقة</nobr>
                    </td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="3" style="width:125px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>تفاصيل الرسوم</nobr>
                    </td>
                    <td colspan="3" style="width:112px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>&nbsp;القيــمة</nobr>
                    </td>
                    <td colspan="3" style="width:90px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>&nbsp;اسم الوكيل</nobr>
                    </td>
                    <td colspan="4" style="width:78px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;font-weight:bold;">
                        <nobr>&nbsp;رقم الوكالة</nobr>
                    </td>
                    <td colspan="2" style="width:67px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>اسم الموظف</nobr>
                    </td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="3" style="width:125px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>قيمة القسط المقرر</nobr>
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
                        <nobr>وقت الاعداد</nobr>
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
                        <nobr>التوقــيــع والخـــتـــم:</nobr>
                    </td>
                    <td colspan="4" style="width:116px;height:12px;"></td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="3" style="width:125px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>مصاريف الاصـدار</nobr>
                    </td>
                    <td colspan="3" style="width:112px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $printData['issue_fees'] }}</nobr>
                    </td>
                    <td colspan="9" style="width:239px;height:12px;"></td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="3" style="width:125px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>رســـوم الاشــــــــراف</nobr>
                    </td>
                    <td colspan="3" style="width:112px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $printData['supervision_fees'] }}</nobr>
                    </td>
                    <td colspan="9" style="width:239px;height:12px;"></td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="3" style="width:125px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>الاجمـــ رقــم ــــالي</nobr>
                    </td>
                    <td colspan="3" style="width:112px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>{{ $printData['total'] }}</nobr>
                    </td>
                    <td colspan="9" style="width:239px;height:12px;"></td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="3" style="width:125px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>الاجمــ حروف ـالي</nobr>
                    </td>
                    <td colspan="12" style="width:353px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        {{ $printData['total_in_words'] }}
                    </td>
                </tr>
                <tr style="vertical-align:top;">
                    <td colspan="3" style="width:125px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>الملاحظة Note</nobr>
                    </td>
                    <td colspan="12" style="width:353px;height:12px;line-height:11px;direction:rtl;text-align:center;vertical-align:middle;">
                        <nobr>لا يتم تغطية أي حالة طارئة إلا عن طريق المعيد ( دار الصحة)</nobr>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Terms and Conditions -->
        <div class="section">
        <table class="two-column-table" style="width: 100%; border-collapse: collapse; margin-bottom: 8px; font-size:10px;">
        <tr style="vertical-align:top;">
        <td colspan="15" style="width:481px;height:auto;line-height:13px;direction:rtl;text-align:center;vertical-align:top;font-size:12px;font-weight:normal;padding:6px;">
        تغطي هذه الوثيقة الحوادث الجسمانية التي يتعرض لها المؤمن عليهم بموجب هذه الوثيقة ويشكل مفاجئ وعرض عنيفة وخارجة وظاهره ومستقلة عن أي سبب آخر ينشأ عنها وحدها وفي خلال ثلاثة أشهر من تاريخ وقوع الحادث أو الوفاة.<br>
        كما هو موضح شروط واستثناءات الوثيقة.<br>
        This document covers bodily accidents that the insured are subjected to under this policy, which are sudden, violent, external, apparent, and independent of any other cause, occurring alone and within three months from the date of the accident or death.<br>
        The terms and exclusions of the policy are also outlined.<br><br>
        <strong>Enjoy comprehensive coverage with the following benefits      تمتع بالتغطيات الشاملة مع المزايا التالية :</strong><br>
        <strong>24 hour coverage worldwide.24 ساعة في جميع أنحاء العالم تغطية على مدار.</strong><br>
        تغطية حالات الوفاة العرضية والعجز الكلي الدائم والعجز الجزئي الدائم والعجز الكلي المؤقت وإعادة رفات الموتى إلى الوطن والنفقات الطبية .<br>
        Coverage for accidental death, permanent total disability, permanent partial disability, temporary total disability, repatriation of deceased remains, and medical expenses.<br><br>
        في حالة الوفاة بسبب حادث أو العجز الكلي الدائم يُدفع 100% من مبلغ التأمين.<br>
        In the event of death due to an accident or permanent total disability, 100% of the insurance amount will be paid.<br><br>
        في حالة العجز الجزئي الدائم، يتم دفع نسبة معينة من مبلغ التأمين وفقاً لحجم الإعاقة واعتماداً على أي جزء من الجسم الذي تعرّض إلى إصابات تسببت بعاهات دائمة .<br>
        In the case of permanent partial disability, a certain percentage of the insurance amount is paid according to the extent of the disability and depending on which part of the body sustained injuries that caused permanent impairments.<br><br>
        إذا تعرّض الشخص المؤمن عليه إلى إصابة تعطل قدراته، ونتيجة لذلك، لم يتمكّن من استئناف مهامه لفترة محددة من الزمن، يتم تعويضه وفقاً للرواتب الأسبوعية للمؤمن عليه .<br>
        If the insured person suffers an injury that incapacitates them, and as a result, they are unable to resume their duties for a specified period of time, they will be compensated according to the insured person's weekly wages.<br><br>
        يمكن تسديد نفقات إعادة رفات الموتى إذا تطلب الشخص المؤمن عليه إعادة الرفات إلى بلده الأم، وذلك نتيجة لحوادث الوفاة العرضية .<br>
        Expenses for the repatriation of the deceased's remains can be covered if the insured person requires the remains to be returned to their homeland, due to accidental death.<br><br>
        يمكن تسديد النفقات الطبية المدفوعة إذا تطلب دخول الشخص المؤمن عليه المستشفى أو العلاج الطبي نتيجة وقوع حادث .<br>
        Medical expenses can be reimbursed if the insured person needs hospitalization or medical treatment as a result of an accident.
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

