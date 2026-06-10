<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>وثيقة تأمين - {{ $document->insurance_number }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        @page {
            size: A4;
            margin: 8mm 10mm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            height: 100%;
        }
        
        body {
            font-family: 'Tajawal', 'Arial', 'Tahoma', sans-serif;
            font-size: 11px;
            color: #000;
            background: #fff;
            padding: 0;
            line-height: 1.35;
        }
        
        .document-container {
            width: 100%;
            min-height: 100vh;
            margin: 0 auto;
            background: #fff;
            padding: 4px 8px;
            display: flex;
            flex-direction: column;
        }
        
        /* ─── Header ─── */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 14px;
            padding-bottom: 10px;
            border-bottom: 2.5px solid #888;
        }
        
        .qr-code {
            width: 82px;
            height: 82px;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            border: 1px solid #ccc;
        }
        
        .company-info {
            text-align: center;
            flex: 1;
            padding: 0 12px;
        }
        
        .doc-main-title {
            font-size: 15px;
            font-weight: 800;
            color: #000;
            margin-bottom: 3px;
            letter-spacing: 0.5px;
        }
        
        .company-name {
            font-size: 14px;
            font-weight: 700;
            color: #000;
            margin-bottom: 3px;
        }
        
        .legal-text {
            font-size: 9px;
            color: #333;
            line-height: 1.35;
            margin-top: 3px;
            padding: 3px 8px;
            background: #f8f9fa;
            border-radius: 2px;
        }
        
        .logo {
            width: 82px;
            height: 82px;
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
        
        /* ─── Tables ─── */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 10px;
        }
        
        .data-table td {
            padding: 6px 8px;
            border: 1px solid #999;
            text-align: right;
            vertical-align: middle;
            color: #000;
            line-height: 1.4;
        }
        
        .data-table .label {
            font-weight: 700;
            background: #f5f5f5;
            text-align: center;
            font-size: 9.5px;
            color: #1a1a1a;
            white-space: nowrap;
        }
        
        .data-table .value {
            font-weight: 600;
            text-align: center;
            font-size: 10px;
        }
        
        .data-table .section-header-cell {
            background: #808080;
            color: #fff;
            text-align: center;
            padding: 6px 0;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        
        /* ─── Financial highlight ─── */
        .total-row td {
            background: #f5f5f5 !important;
            font-weight: 800 !important;
            font-size: 10.5px !important;
        }
        
        /* ─── Terms - fills remaining space ─── */
        .terms-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .terms-box {
            border: 1px solid #999;
            padding: 10px 12px;
            font-size: 8.5px;
            line-height: 1.6;
            color: #000;
            text-align: justify;
            flex: 1;
        }
        
        .terms-box .terms-title {
            font-weight: 800;
            font-size: 10.5px;
            text-align: center;
            margin-bottom: 8px;
            color: #000;
            padding-bottom: 2px;
            border-bottom: 1px solid #888;
        }
        
        /* ─── Footer ─── */
        .footer-note {
            text-align: center;
            font-size: 8.5px;
            color: #555;
            padding-top: 6px;
            margin-top: 6px;
            border-top: 1.5px solid #888;
        }
        
        @media print {
            body {
                padding: 0;
                margin: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .document-container {
                min-height: 100%;
                padding: 0;
            }
            
            .no-print {
                display: none;
            }
            
            @page {
                margin: 7mm 9mm;
            }
        }
    </style>
</head>
<body>
    <div class="document-container">
        <!-- ═══════════ Header ═══════════ -->
        <div class="header">
            <div class="logo">
                <img src="{{ asset('img/logo.png') }}" alt="شعار الشركة" onerror="this.src='/img/logo.png'; this.onerror=function(){this.style.display='none'; this.parentElement.innerHTML='<div style=\'width:82px;height:82px;background:#003366;color:#fff;display:flex;align-items:center;justify-content:center;font-size:8px;text-align:center;border-radius:6px;\'>LOGO</div>';};" />
            </div>
            <div class="company-info">
                <div class="doc-main-title">وثيقة التأمين الاجباري من حوادث المركبات الآلية</div>
                <div class="company-name">شركة المدار الليبي للتأمين</div>
                <div class="legal-text">
                    هذه الوثيقة صادرة وفقاً لأحكام القانون رقم ( 28 لسنة 1971م ) بشأن التأمين الاجباري من المسؤولية المدنية الناشئة من حوادث المركبات الآلية والقوانين المعدلة والقرارات.
                </div>
            </div>
            <div class="qr-code" id="qrcode"></div>
        </div>

        <!-- ═══════════ 1. بيانات الوثيقة ═══════════ -->
        <table class="data-table">
            <tr>
                <td colspan="6" class="section-header-cell">بيـانـــات الوثيـقـــة</td>
            </tr>
            <tr>
                <td class="label" style="width:15%">رقم الوثيقة</td>
                <td class="value" style="width:18%">{{ $document->insurance_number }}</td>
                <td class="label" style="width:15%">نوع التأمين</td>
                <td class="value" style="width:18%">{{ $document->insurance_type ?? 'تأمين إجباري سيارات' }}</td>
                <td class="label" style="width:15%">تاريخ الإصدار</td>
                <td class="value" style="width:19%">{{ \Carbon\Carbon::parse($document->issue_date)->format('d/m/Y h:i A') }}</td>
            </tr>
            <tr>
                <td class="label">من (12:00) ظهراً</td>
                <td class="value">{{ \Carbon\Carbon::parse($document->start_date)->format('d/m/Y') }}</td>
                <td class="label">إلى (12:00) ظهراً</td>
                <td class="value">{{ $document->end_date ? \Carbon\Carbon::parse($document->end_date)->format('d/m/Y') : '-' }}</td>
                <td class="label">مدة التأمين</td>
                <td class="value">{{ $printData['duration'] }}</td>
            </tr>
        </table>

        <!-- ═══════════ 2. بيانات المؤمن له ═══════════ -->
        <table class="data-table">
            <tr>
                <td colspan="6" class="section-header-cell">بيانـــات المؤمــن لـه</td>
            </tr>
            <tr>
                <td class="label" style="width:15%">اسم المؤمن له</td>
                <td class="value" style="width:18%">{{ $document->insured_name ?? '-' }}</td>
                <td class="label" style="width:15%">الجنسية</td>
                <td class="value" style="width:18%">{{ $document->nationality ?? '-' }}</td>
                <td class="label" style="width:15%">الرقم الوطني / جواز</td>
                <td class="value" style="width:19%">{{ $document->nid_passport ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">رقم رخصة القيادة</td>
                <td class="value">{{ $document->driving_license_number ?? '-' }}</td>
                <td class="label">هاتف / واتساب</td>
                <td class="value">{{ $document->phone ?? '-' }} / {{ $document->whatsapp_number ?? '-' }}</td>
                <td class="label">البريد الإلكتروني</td>
                <td class="value">{{ $document->email ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">العنوان</td>
                <td class="value" colspan="5">{{ $document->address ?? '-' }}</td>
            </tr>
        </table>

        <!-- ═══════════ 3. بيانات المركبة ═══════════ -->
        <table class="data-table">
            <tr>
                <td colspan="6" class="section-header-cell">بيانـــات المركبـــة</td>
            </tr>
            <tr>
                <td class="label" style="width:15%">رقم اللوحة المعدنية</td>
                <td class="value" style="width:18%">{{ $printData['plate_number'] }}</td>
                <td class="label" style="width:15%">{{ $printData['is_customs_insurance'] ? 'الميناء' : 'الجهة المقيد بها' }}</td>
                <td class="value" style="width:18%">{{ $printData['city_name'] }}</td>
                <td class="label" style="width:15%">النوع</td>
                <td class="value" style="width:19%">{{ $printData['vehicle_type'] }}</td>
            </tr>
            <tr>
                <td class="label">رقم الهيكل</td>
                <td class="value">{{ $document->chassis_number ?? '-' }}</td>
                <td class="label">اللون</td>
                <td class="value">{{ $document->color ?? '-' }}</td>
                <td class="label">سنة الصنع</td>
                <td class="value">{{ $document->year ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">نوع الوقود</td>
                <td class="value">{{ $document->fuel_type ?? '-' }}</td>
                <td class="label">بلد الصنع</td>
                <td class="value">{{ $document->manufacturing_country ?? '-' }}</td>
                <td class="label">قوة المحرك بالحصان</td>
                <td class="value">{{ $document->engine_power ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">الركاب المصرح بهم</td>
                <td class="value">{{ $document->authorized_passengers ?? '-' }}</td>
                <td class="label">الحمولة بالطن</td>
                <td class="value">{{ $printData['load_capacity'] }}</td>
                <td class="label">الغرض من الترخيص</td>
                <td class="value">{{ $document->license_purpose ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">رقم المحرك</td>
                <td class="value">{{ $document->engine_number ?? '-' }}</td>
                <td class="label">سعة المحرك (cc)</td>
                <td class="value">{{ $document->engine_cc ?? '-' }}</td>
                <td class="label">وزن المركبة</td>
                <td class="value">{{ $document->vehicle_weight ?? '-' }}</td>
            </tr>
        </table>

        <!-- ═══════════ 4. البيانات المالية + الشركة الصادرة ═══════════ -->
        <table class="data-table">
            <tr>
                <td colspan="4" class="section-header-cell" style="width:50%">احتساب القسط - البيانات المالية</td>
                <td colspan="4" class="section-header-cell" style="width:50%">الشركة الصادرة - معد الوثيقة</td>
            </tr>
            <tr>
                <td class="label" style="width:14%">تفاصيل الرسوم</td>
                <td class="label" style="width:11%">القيمة</td>
                <td class="label" style="width:14%">&nbsp;</td>
                <td class="label" style="width:11%">&nbsp;</td>
                <td class="label" style="width:11%">اسم الوكيل</td>
                <td class="value" style="width:14%">{{ $printData['agency_name'] ?? 'المدار الليبي للتأمين' }}</td>
                <td class="label" style="width:11%">رقم الوكالة</td>
                <td class="value" style="width:14%">{{ $printData['agency_code'] ?? 'ML0001' }}</td>
            </tr>
            <tr>
                <td class="label">قيمة القسط المقرر</td>
                <td class="value">{{ number_format($document->premium, 3) }}</td>
                <td class="label">الضريبة</td>
                <td class="value">{{ number_format($document->tax, 3) }}</td>
                <td class="label">اسم الموظف</td>
                <td class="value">{{ $printData['agent_name'] ?? 'الإدارة' }}</td>
                <td class="label">وقت الاعداد</td>
                <td class="value">{{ \Carbon\Carbon::parse($document->issue_date)->format('d/m/y H:i:s') }}</td>
            </tr>
            <tr>
                <td class="label">الدمغة</td>
                <td class="value">{{ number_format($document->stamp, 3) }}</td>
                <td class="label">مصاريف الإصدار</td>
                <td class="value">{{ number_format($document->issue_fees, 3) }}</td>
                <td class="label" colspan="2" rowspan="2" style="text-align:center; font-size: 10px;">التوقيع والختم:</td>
                <td class="value" colspan="2" rowspan="2">&nbsp;</td>
            </tr>
            <tr>
                <td class="label">رسوم الإشراف</td>
                <td class="value">{{ number_format($document->supervision_fees, 3) }}</td>
                <td class="label">&nbsp;</td>
                <td class="value">&nbsp;</td>
            </tr>
            <tr class="total-row">
                <td class="label">الاجمالي (رقم)</td>
                <td class="value" style="color:#000;">{{ number_format($document->total, 3) }}</td>
                <td class="label" colspan="2">الاجمالي (حروف)</td>
                <td class="value" colspan="4" style="font-size:9.5px;">{{ $printData['total_in_words'] }}</td>
            </tr>
            <tr>
                <td class="label">ملاحظة</td>
                <td class="value" colspan="7" style="font-size: 9px;">
                    @if($printData['is_customs_insurance'])
                        لا يتم التعويض في حال مزاولة المركبة الجمركية أي عمل من أعمال النقل (نقل ركاب - نقل بضائع)
                    @else
                        للتأكد من بيانات وثيقتك ادخل هنا www.mli.ly
                    @endif
                </td>
            </tr>
        </table>

        <!-- ═══════════ 5. الشروط العامة (تملأ باقي الصفحة) ═══════════ -->
        <div class="terms-wrapper">
            <div class="terms-box">
                <div class="terms-title">الشروط العامة</div>
1- يلتزم المؤمن بموجب هذه الوثيقة بتغطية المسؤولية المدنية الناشئة عن الوفاة أو أية إصابة بدنية تلحق بأي شخص من حوادث المركبات الآلية التي في ليبيا المثبتة بياناتها في هذه الوثيقة وذلك عن مدة سريانها.
ويسري هذا الالتزام لصالح الغير دون الركاب من حوادث المركبات الآلية والدراجات النارية أيا كان نوعها ولصالح الركاب أيضا دون أعمالها من حوادث المركبات الآلية التالية:
• سيارات الركوبة العامة (سيارات الأجرة). • حافلات النقل العام للركاب والمركبات المقطورة الملحقة بها. • حافلات النقل الخاص أو حافلات نقل الركاب رحلات سياحية والمركبات الملحقة بها. • سيارات الإسعاف والمستشفيات.
• سيارات نقل البضائع فيما يخص الركاب المصرح بنقلهم ويسري هذا التأمين على السيارات الخاصة والدراجات النارية لصالح الغير دون الركاب وعلى باقي أنواع المركبات الآلية لصالح الغير والركاب ما ويعتبر الشخص راكبا سواء داخل المركبة أو صاعدا إليها أو نازلا منها ولا يشمل التأمين عمال المركبة المثبتة بياناتها في هذه الوثيقة.
2- يلتزم المؤمن بدفع التعويض عن الأضرار المادية والمعنوية التي تلحق بالأشخاص من حوادث المركبة الآلية المؤمنة بموجب هذه الوثيقة وديا أو قضائيا وذلك بقيمة محددة لا تتجاوز الحد الأقصى المنصوص عليه بقرار اللجنة الشعبية العامة رقم (213 لسنة 2003) والقرارات المعدلة أو البديلة له.
3- يستحق التعويض عن الأضرار المادية والمعنوية للمصاب شخصيا في حال الإصابة الجسدية. والأب والأم والزوج والأولاد دون غيرهم في حالة الوفاة ويؤدي التعويض لهم مرة واحدة ويوزع بينهم وفقا للقواعد التي تقررها المحكمة بحسب الضرر الذي لحق بكل واحد منهم.
4- تسقط دعوى المضرور قبل المؤمن بانقضاء ثلاثة سنوات من تاريخ صدور حكم نهائي بثبوت مسؤولية المؤمن له عن الحادث أو الواقعة المسببة للضرر.
5- لا يجوز للمؤمن له تقديم أو قبول أي عرض فيما يختص بتعويض المضرور دون موافقة المؤمن كتابة ولا تعتبر أية تسوية بين المؤمن له والمضرور حجة قبل المؤمن إذا تمت دون موافقته.
6- لا يجوز للمؤمن ولا للمؤمن له أن يلغي وثيقة التأمين أثناء مدة سريانها مادام الترخيص للمركبة قائما وفي حالة إلغاء وثيقة التأمين قبل انتهاء مدة سريانها عند انتهاء الترخيص أو تقديم وثيقة تأمين جديدة بسبب تغيير بيانات المركبة أو نقل قيد ملكيتها، يجب على المؤمن أن يرد للمؤمن له جزء من باقي قسط يتناسب والمدة من فترة التأمين بشرط تقديم وثيقة للتأمين مؤشرا عليها بما يفيد إعادتها إلى المؤمن له من مكتب الترخيص المختص، وتصبح الوثيقة ملغاة من تاريخ التأشير وللمؤمن أن يستنزل مصروفات الوثيقة بما لا تتجاوز (10%) عشرة بالمائة من قيمة القسط.
7- يجب على المؤمن له أن يتخذ كافة الاحتياطات المعقولة للمحافظة على المركبة في حالة صالحة للاستعمال ويجوز للمؤمن التحقق من ذلك دون اعتراض المؤمن له: وعلى المؤمن له إخطار المؤمن والسلطات المختصة (72) ساعة من وقت علمه أو علم من ينوب عنه عن حالات فقد المركبة أو وقوع حادث منها نشأت عنه الوفاة أو الإصابة البدنية أو مطالبته بالتعويض الناشئ عن الوفاة أو الإصابة البدنية ويجب عليه أيضا أن يقدم للمؤمن جميع الخطابات والمطالبات والإنذارات وإعلانات الدعاوي بمجرد تسليمها.
8- يجوز للمؤمن أن يرجع للمؤمن له بقيمة ما يكون قد أداه من تعويض في الحالات الآتية: إذا ثبت أن التأمين قد عقد بناء على إدلاء المؤمن له ببيانات كاذبة أو إخفاء وقائع جوهرية • استعمال المركبة في غير الغرض المبين بترخيص أو قبول ركاب أو وضع حمولة أكثر من المقرر لها • إذا كان قائد المركبة غير حائز على رخصة قيادة • إذا ثبت أن قائد المركبة ارتكب الحادث وهو في غير حالته الطبيعية بسبب سكر أو تناول مخدرات • إذا ثبت أن الوفاة أو الإصابة البدنية قد نشأت عن عمل ارتكبه المؤمن له عن إرادة وعمد وسبق إصرار.
9- لا يترتب على حق الرجوع المقرر للمؤمن طبقا لأحكام القانون والشروط الواردة بهذه الوثيقة أي مساس بحق المضرور قبله.
10- لا يتحمل المؤمن أية مسؤولية تقع بطريقة مباشرة أو غير مباشرة عن الإشعاعات الذرية أو الانفجارات.
<strong>الاختصاص القضائي:</strong> من المتفق عليه أن كل ما ينشأ من منازعات بصدد هذا العقد أو بخصوص تنفيذه يكون من اختصاص المحاكم الوطنية التي يتبع لها المركز الرئيسي للشركة. وفي جميع الأحوال فإن النص العربي لهذه الوثيقة وملاحقها هو الواجب التطبيق.
            </div>
        </div>

        <!-- ═══════════ Footer ═══════════ -->
        <div class="footer-note">
            شركة المدار الليبي للتأمين &mdash; جميع الحقوق محفوظة
        </div>
    </div>

    @php
        $qrDataJson = json_encode($printData['qr_data']);
    @endphp
    <script>
        // إنشاء QR code يحتوي على بيانات الوثيقة
        (function() {
            const qrData = {!! $qrDataJson !!};
            const qrText = JSON.stringify(qrData);
            const qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=82x82&data=' + encodeURIComponent(qrText);
            const qrContainer = document.getElementById('qrcode');
            if (qrContainer) {
                qrContainer.innerHTML = '<img src="' + qrApiUrl + '" alt="QR Code" style="width: 82px; height: 82px; display: block;" />';
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
