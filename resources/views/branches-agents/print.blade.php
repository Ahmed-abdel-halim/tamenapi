<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عقد الوكيل - {{ $branchAgent->agency_name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        @page {
            size: A4;
            margin: 5mm;
            /* Very small margins to maximize height */
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Tajawal', 'Arial', 'Tahoma', sans-serif;
            font-size: 11px;
            /* Smaller font */
            color: #000;
            background: #fff;
            padding: 0;
            line-height: 1.25;
            /* Tighter line height */
            height: 100vh;
        }

        .contract-container {
            width: 100%;
            height: 98vh; /* Force container to nearly full page height */
            margin: 0 auto;
            background: #fff;
            padding: 0; 
        }

        .contract-table {
            width: 100%;
            height: 100%; /* Stretch the table to fill the container height */
            border-collapse: collapse;
            border: 2px solid #000;
        }

        .contract-table th,
        .contract-table td {
            border: 1px solid #000;
            padding: 3px 4px;
            /* Reduced padding */
            text-align: center;
        }

        .contract-table .bold-header {
            font-weight: bold;
            font-size: 14px;
            /* Reduced from 16 */
            background-color: #f8f9fa;
        }

        .text-right {
            text-align: right !important;
        }

        .text-center {
            text-align: center !important;
        }

        .text-justify {
            text-align: justify !important;
        }

        .font-bold {
            font-weight: bold;
        }

        .footer {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            font-size: 11px;
            color: #6b7280;
        }

        @media print {
            body {
                padding: 0;
                margin: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .contract-table .bold-header {
                background-color: #f8f9fa !important;
            }
        }
    </style>
</head>

<body>
    <div class="contract-container">
        <table class="contract-table">
            <!-- Header row -->
            <tr>
                <td colspan="4" class="bold-header" style="font-size: 18px; padding: 6px;">المدار الليبي للتأمين</td>
                <td colspan="2" class="text-center" style="width: 25%; padding: 4px;">
                    <img src="/img/logo.png" alt="لوقو" style="max-height: 40px;"
                        onerror="this.onerror=null;this.parentElement.innerHTML='لوقو';">
                </td>
            </tr>

            <!-- Document meta row -->
            <tr>
                <td colspan="2" class="font-bold">المدار الليبي للتأمين - عقد وكيل تقديم خدمات تأمين</td>
                <td class="font-bold">تاريخ التعاقد</td>
                <td>{{ \Carbon\Carbon::parse($branchAgent->contract_date)->format('Y/m/d') }}</td>
                <td class="font-bold">رقم الوكالة</td>
                <td style="direction: ltr;">{{ $branchAgent->agency_number ?? $branchAgent->code }}</td>
            </tr>

            <!-- Parties Header -->
            <tr>
                <td colspan="2" class="bold-header">الطرف الثاني</td>
                <td colspan="4" class="bold-header">الطرف الأول</td>
            </tr>

            <!-- First Party Info (Right) & Second Party Info (Left) in standard RTL table flow -->
            <!-- 2 cols for Party 2 (Left logically but rendered Right as 1st/2nd in RTL) / Wait RTL goes right-to-left. First column is FAR RIGHT.
                 Image: Right half = Party 1 (الطرف الأول), Left half = Party 2 (الطرف الثاني)
                 To match this in RTL:
                 col 1-2 = right = Party 1
                 col 3-6 = left = Party 2
            -->
            <tr>
                <td class="font-bold" style="width: 15%">الاسم القانوني</td>
                <td style="width: 18.33%">المدار الليبي للتأمين</td>

                <td class="font-bold" style="width: 15%">اسم الوكيل</td>
                <td style="width: 18.33%">{{ $branchAgent->agent_name }}</td>

                <td class="font-bold" style="width: 15%">اسم الوكالة</td>
                <td style="width: 18.33%">{{ $branchAgent->agency_name }}</td>
            </tr>
            <tr>
                <td class="font-bold">رقم الترخيص</td>
                <td style="direction: ltr;">123456789</td>

                <td class="font-bold">الجنسية</td>
                <td>{{ $branchAgent->nationality ?? 'ليبيا' }}</td>

                <td class="font-bold">نوع النشاط</td>
                <td>{{ $branchAgent->activity ?? '-' }}</td>
            </tr>
            <tr>
                <td class="font-bold">العنوان</td>
                <td>ليبيا - طرابلس</td>

                <td class="font-bold">رقم الجواز</td>
                <td>{{ $branchAgent->identity_number ?? '-' }}</td>

                <td class="font-bold">العنوان</td>
                <td>{{ $branchAgent->city }}</td>
            </tr>
            <tr>
                <td class="font-bold">رقم الهاتف</td>
                <td style="direction: ltr;">0920003366</td>

                <td class="font-bold">الرقم الوطني</td>
                <td>{{ $branchAgent->national_id ?? '-' }}</td>

                <td class="font-bold">رقم هاتف الوكالة</td>
                <td style="direction: ltr;">{{ $branchAgent->phone ?? '-' }}</td>
            </tr>
            <tr>
                <td class="font-bold">البريد الإلكتروني</td>
                <td style="direction: ltr;">info@mli.ly</td>

                <td class="font-bold">رقم هاتف الوكيل</td>
                <td style="direction: ltr;">{{ $branchAgent->phone ?? '-' }}</td>

                <td class="font-bold">موقع الكتروني</td>
                <td>-</td>
            </tr>

            <!-- Terms Header -->
            <tr>
                <td colspan="6" class="bold-header" style="background-color: #f1f5f9;">شروط العقد</td>
            </tr>
            <tr>
                <td colspan="6" class="bold-header" style="background-color: #f8fafc;">تمهيد</td>
            </tr>

            <!-- Terms content -->
            <tr>
                <td class="font-bold text-center">م</td>
                <td colspan="5" class="text-right text-justify" style="padding: 4px;">
                    بناء على رغبة الطرفين في إيجاد مصلحة مشتركة وتحديد حقوق والتزامات كل طرف اتجاه الآخر ووفقا لما يقتضي
                    نظام الوكالات التسويقية وتعديلاته في دولة ليبيا حيث أبدى الطرف الثاني رغبته في الحصول على إذن تسويق
                    واصدار وثائق التأمين الخاصة بشركة المدار الليبي للتأمين.
                </td>
            </tr>
            <tr>
                <td class="font-bold text-center">1</td>
                <td colspan="5" class="text-right text-justify" style="padding: 4px;">
                    يتعهد الطرف الثاني بأن يعمل لحساب ولصالح الطرف الأول وتحت إشرافه بصفته وكيلاً عنه بإصدار وثائق
                    التأمين الإجبارية التي تقوم الشركة بإصدارها، وذلك وفقاً للقانون والنظام المعمول به والأحكام والضوابط
                    المبينة بهذا العقد.
                </td>
            </tr>
            <tr>
                <td class="font-bold text-center">2</td>
                <td colspan="5" class="text-right text-justify" style="padding: 4px;">
                    تقوم الشركة بدفع العمولة المستحقة للطرف الثاني وذلك عند نهاية كل شهر بناءً على حوافظ اصدار الوثائق
                    المحالة من <strong>الطرف الثاني إلى الطرف الأول</strong> بعد استيفاء المراجعة المالية والفنية.
                </td>
            </tr>
            <tr>
                <td class="font-bold text-center">3</td>
                <td colspan="5" class="text-right" style="padding: 4px;">
                    1- اتفق الطرفان على مدة هذا العقد
                    (<strong>{{ $branchAgent->contract_duration ?? 'سنة واحدة' }}</strong>) اعتباراً من تاريخ
                    إبرامه.<br>
                    2- يجدد العقد بحضور الطرف الثاني أو من ينوب عنه ويبرم عقد تجديد العقد في الشركة / الطرف الأول.<br>
                    3- يلغى الطرف الأول العقد مع الطرف الثاني برسالة إخطار موجهه للطرف الثاني في حال عدم التقيد في شروط
                    هذا العقد.
                </td>
            </tr>
            <tr>
                <td class="font-bold text-center">4</td>
                <td colspan="5" class="text-right" style="padding: 4px;">
                    <strong>يلتزم الطرف الثاني بشأن تنفيذ أحكام هذا العقد بما يلي:</strong><br>
                    1. مباشرة العمل خلال مدة لا تتجاوز شهر من تاريخ ابرام العقد، ويحق للشركة الغاء العقد في حالة مخالفته
                    لهذا الالتزام.<br>
                    2. العمل على إصدار وثائق التأمين عن طريق منظومة الاصدار الخاصة بالشركة فقط.<br>
                    3. عدم مخالفة اسعار الوثائق التي يصدرها وعدم التعهد بأية التزامات أو وعود بشأن الآثار المترتبة عن
                    هذه الوثائق.<br>
                    4. مراعاة الطرف الثاني مبدأ حسن النية في عمليات إصدار الوثائق المتفق على إصدارها.<br>
                    5. عدم قبول التأمين على أخطار قد تحققت فعلاً قبل إصدار وثيقة تأمين.
                </td>
            </tr>
            <tr>
                <td class="font-bold text-center">5</td>
                <td colspan="5" class="text-right text-justify" style="padding: 4px;">
                    يحق للطرف الأول فسخ العقد دون اخطار الطرف الثاني في حالة ثبوت مخالفته للوائح المالية والفنية النافذة
                    بالشركة.
                </td>
            </tr>
            <tr>
                <td class="font-bold text-center">6</td>
                <td colspan="5" class="text-right text-justify" style="padding: 4px;">
                    اتفق الطرفان بأنه يحق للطرف الثاني إنهاء العقد ويشترط الحصول على براءة ذمة من الطرف الأول.
                </td>
            </tr>
            <tr>
                <td class="font-bold text-center">7</td>
                <td colspan="5" class="text-right text-justify" style="padding: 4px;">
                    اتفق الطرفان بأن أي نزاع ينشأ بينهما يختص به القضاء الليبي بعد استنفاذ جميع محاولات التسوية الودية.
                </td>
            </tr>
            <tr>
                <td class="font-bold text-center">8</td>
                <td colspan="5" class="text-right text-justify" style="padding: 4px;">
                    اتفق الطرفان بأن المراسلات الرسمية التي يتم تبادلها بينهما توجه إلى الطرف الآخر رسالة رسمية صادره من
                    أحدهما.
                </td>
            </tr>
            <tr>
                <td class="font-bold text-center">9</td>
                <td colspan="5" class="text-right text-justify" style="padding: 4px;">
                    وقع الطرفان على هذا العقد بما يفيد اعتماده والعمل بما جاء فيه من أحكام وشروط من تاريخ إبرامه.
                </td>
            </tr>

            <!-- Signatures Row -->
            <tr>
                <td colspan="2" class="text-center" style="padding: 10px; vertical-align: top;">
                    <div class="font-bold" style="margin-bottom: 15px;">الطرف الأول</div>
                    <div style="margin-bottom: 15px;">مدير الافرع والوكلاء</div>
                    <div style="margin-bottom: 15px;">الاسم / ....لطفيه رحومه....</div>
                    <div>التوقيع /................</div>
                </td>
                <td colspan="4" class="text-center" style="padding: 10px; vertical-align: top;">
                    <div class="font-bold" style="margin-bottom: 15px;">الطرف الثاني</div>
                    <div style="margin-bottom: 15px;">ممثل الطرف الثاني</div>
                    <div style="margin-bottom: 15px;">الاسم / ....{{ $branchAgent->agent_name }}....</div>
                    <div>التوقيع /................</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Hidden div for generating QR data if ever needed again -- the QR code isn't in original image structure, but keeping it invisible doesn't hurt. -->
    <div id="qrcode" style="display: none;"></div>

    <script>
        window.onload = function () {
            setTimeout(function () {
                window.print();
            }, 500);
        };
    </script>
</body>

</html>