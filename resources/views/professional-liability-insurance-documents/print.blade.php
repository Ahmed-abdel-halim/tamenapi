@php
    $customInsuranceCond = \App\Models\InsuranceCondition::where('insurance_type', 'liability')->first();
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>وثيقة تأمين المسؤولية المهنية (الطبية) - {{ $document->insurance_number }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        @page {
            size: A4 portrait;
            margin: 6mm 8mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        html, body {
            height: 100%;
            background: #fff;
        }

        body {
            font-family: 'Tajawal', 'Arial', 'Tahoma', sans-serif;
            font-size: 12px;
            color: #0f172a;
            line-height: 1.4;
        }

        .document-container {
            width: 100%;
            min-height: 278mm;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: #fff;
            padding: 4px 6px;
        }

        /* ─── Header ─── */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 10px;
            border-bottom: 3px double #0f172a;
            margin-bottom: 12px;
        }

        .logo {
            width: 90px;
            height: 90px;
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

        .company-info {
            text-align: center;
            flex: 1;
            padding: 0 15px;
        }

        .company-name {
            font-size: 20px;
            font-weight: 900;
            color: #0f172a;
            margin-bottom: 2px;
        }

        .document-title {
            font-size: 17px;
            font-weight: 800;
            color: #1e40af;
            margin-bottom: 4px;
        }

        .legal-text {
            font-size: 10.5px;
            font-weight: 700;
            color: #334155;
            background: #f8fafc;
            padding: 4px 10px;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
            display: inline-block;
        }

        .qr-code {
            width: 90px;
            height: 90px;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #cbd5e1;
            flex-shrink: 0;
            border-radius: 6px;
        }

        /* ─── Table Sections ─── */
        .section-header {
            background: #1e293b;
            color: #ffffff;
            font-weight: 800;
            font-size: 13px;
            padding: 6px 12px;
            border-radius: 4px 4px 0 0;
            margin-top: 10px;
        }

        .doc-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .doc-table th, .doc-table td {
            border: 1px solid #64748b;
            padding: 7px 10px;
            font-size: 12px;
            vertical-align: middle;
        }

        .doc-table th {
            background: #f1f5f9;
            font-weight: 800;
            color: #0f172a;
            text-align: center;
        }

        .doc-table td.label {
            background: #f8fafc;
            font-weight: 800;
            color: #334155;
            text-align: right;
            width: 16%;
        }

        .doc-table td.value {
            font-weight: 700;
            color: #0f172a;
            text-align: right;
        }

        .total-box {
            background: #eff6ff;
            border: 2px solid #2563eb;
            padding: 8px 12px;
            font-size: 13.5px;
            font-weight: 800;
            color: #1e40af;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 6px;
            margin-bottom: 10px;
        }

        .terms-box {
            border: 1px solid #94a3b8;
            border-radius: 6px;
            padding: 10px 12px;
            background: #fafafa;
            margin-bottom: 10px;
            font-size: 11px;
            line-height: 1.55;
            color: #1e293b;
        }

        .terms-title {
            font-weight: 800;
            font-size: 12.5px;
            color: #0f172a;
            margin-bottom: 6px;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 4px;
            text-align: center;
        }

        .footer-signatures {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding-top: 15px;
            margin-top: 10px;
            border-top: 1px dashed #cbd5e1;
        }

        .sig-block {
            text-align: center;
            width: 45%;
        }

        .sig-line {
            margin-top: 35px;
            border-top: 1px solid #0f172a;
            padding-top: 4px;
            font-weight: 800;
            font-size: 12px;
            color: #0f172a;
        }

        .footer-note {
            text-align: center;
            font-size: 10px;
            color: #64748b;
            margin-top: 8px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="document-container">
        <div>
            <!-- Header -->
            <div class="header">
                <div class="logo">
                    <img src="/img/logo.png" alt="المدار الليبي للتأمين" onerror="this.src='/img/logo3.png'" />
                </div>
                <div class="company-info">
                    <div class="company-name">شركة المدار الليبي للتأمين</div>
                    <div class="document-title">وثيقة تأمين المسؤولية المهنية (الطبية)</div>
                    <div class="legal-text">الوثيقة متوافقة مع أحكام القانون رقم (17) لسنة 1986 م بشأن المسؤولية الطبية</div>
                </div>
                <div class="qr-code" id="qrcode"></div>
            </div>

            <!-- Section 1: بيانات الوثيقة -->
            <div class="section-header">بيانات الوثيقة Document Data</div>
            <table class="doc-table">
                <tr>
                    <td class="label">رقم الوثيقة:</td>
                    <td class="value" style="font-size: 14px; font-weight: 900; color: #1e40af;">{{ $printData['insurance_number'] }}</td>
                    <td class="label">تاريخ الإصدار:</td>
                    <td class="value">{{ $printData['issue_date'] }}</td>
                </tr>
                <tr>
                    <td class="label">تاريخ الابتداء:</td>
                    <td class="value">{{ $printData['start_date'] }} (12:00 ظهرًا)</td>
                    <td class="label">تاريخ الانتهاء:</td>
                    <td class="value">{{ $printData['end_date'] }} (12:00 ظهرًا)</td>
                </tr>
                <tr>
                    <td class="label">مدة التأمين:</td>
                    <td class="value" colspan="3">{{ $printData['duration'] }}</td>
                </tr>
            </table>

            <!-- Section 2: بيانات المؤمن له -->
            <div class="section-header">بيانات المؤمن له Insured Data</div>
            <table class="doc-table">
                <tr>
                    <td class="label">اسم المؤمن له:</td>
                    <td class="value" colspan="3" style="font-size: 13.5px; font-weight: 800;">{{ $printData['insured_name'] }}</td>
                </tr>
                <tr>
                    <td class="label">رقم الهاتف:</td>
                    <td class="value">{{ $printData['phone'] }}</td>
                    <td class="label">مكان العمل:</td>
                    <td class="value">{{ $printData['workplace'] }}</td>
                </tr>
                <tr>
                    <td class="label">الصفة بالعقد:</td>
                    <td class="value">{{ $printData['contract_relation'] }}</td>
                    <td class="label">الجنس:</td>
                    <td class="value">{{ $printData['gender'] }}</td>
                </tr>
                <tr>
                    <td class="label">الحالة الاجتماعية:</td>
                    <td class="value">{{ $printData['marital_status'] }}</td>
                    <td class="label">الجنسية:</td>
                    <td class="value">{{ $printData['nationality'] }}</td>
                </tr>
                <tr>
                    <td class="label">تاريخ الميلاد:</td>
                    <td class="value">{{ $printData['birth_date'] }}</td>
                    <td class="label">المهنة / التخصص:</td>
                    <td class="value" style="font-weight: 800; color: #0369a1;">{{ $printData['profession'] }}</td>
                </tr>
            </table>

            <!-- Section 3: بيان الحساب والرسوم المالية -->
            <div class="section-header">تفاصيل القسط والرسوم المالية Financial Details</div>
            <table class="doc-table">
                <tr>
                    <td class="label">القسط الصافي:</td>
                    <td class="value">{{ $printData['premium'] }} د.ل</td>
                    <td class="label">الفرع / الوكالة:</td>
                    <td class="value">{{ $printData['agency_name'] }} (كود: {{ $printData['agency_code'] }})</td>
                </tr>
                <tr>
                    <td class="label">الضريبة:</td>
                    <td class="value">{{ $printData['tax'] }} د.ل</td>
                    <td class="label">الموظف المحرر:</td>
                    <td class="value">{{ $printData['agent_name'] }}</td>
                </tr>
                <tr>
                    <td class="label">رسم الدمغة:</td>
                    <td class="value">{{ $printData['stamp'] }} د.ل</td>
                    <td class="label">تاريخ / وقت التحرير:</td>
                    <td class="value">{{ $printData['prepared_at'] }}</td>
                </tr>
                <tr>
                    <td class="label">مصاريف الإصدار:</td>
                    <td class="value">{{ $printData['issue_fees'] }} د.ل</td>
                    <td class="label" rowspan="2" style="vertical-align: middle;">ملاحظات:</td>
                    <td class="value" rowspan="2" style="vertical-align: middle;">لا تمثل هذه الوثيقة إيصال مالي إلا بعد سداد المبلغ الموضح.</td>
                </tr>
                <tr>
                    <td class="label">رسوم الإشراف:</td>
                    <td class="value">{{ $printData['supervision_fees'] }} د.ل</td>
                </tr>
            </table>

            <div class="total-box">
                <div>إجمالي المبلغ المستحق (Total Premium): <span style="font-size: 16px; color: #dc2626; font-weight: 900; margin-right: 8px;">{{ $printData['total'] }} د.ل</span></div>
                <div style="font-size: 12px; color: #334155; font-weight: 700;">المبلغ تفقيطاً: {{ $printData['total_in_words'] }}</div>
            </div>

            <!-- Section 4: شروط وإقرارات الوثيقة -->
            <div class="terms-box">
                <div class="terms-title">شروط وإقرارات الوثيقة (تأمين المسؤولية الطبية)</div>
                <div style="margin-bottom: 6px; text-align: justify;">
                    تُصدر الإدارة وثائق تأمين المسؤولية الطبية وفق أحكام القانون رقم (17) لسنة 1986م، والذي يمنح العناصر الطبية والطبية المساعدة الطمأنينة في ممارسة مهامهم حيث توفر التغطية التأمينية عن الأخطاء الطبية الصادرة عنهم.
                </div>
                <div style="margin-bottom: 6px; text-align: justify;">
                    تغطي هذه الوثيقة المسؤولية المدنية الناجمة عن الوفاة أو أي إصابة بدنية أو أي ضرر مادي أو معنوي لأي شخص بسبب خطأ ناتج عن ممارسة المهن الطبية والمهن المرتبطة بها.
                </div>
                @if($customInsuranceCond && !empty(trim($customInsuranceCond->conditions)))
                    <div style="white-space: pre-line; margin-top: 8px; padding-top: 8px; border-top: 1px dashed #cbd5e1; font-weight: 600; color: #0f172a;">
                        {!! nl2br(e($customInsuranceCond->conditions)) !!}
                    </div>
                @endif
            </div>
        </div>

        <!-- Footer & Signatures -->
        <div>
            <div class="footer-signatures">
                <div class="sig-block">
                    <div class="sig-line">توقيع وإقرار المؤمن له (الطبيب / الممارس)</div>
                </div>
                <div class="sig-block">
                    <div class="sig-line">توقيع وختم شركة المدار الليبي للتأمين</div>
                </div>
            </div>

            <div class="footer-note">
                إدارة التأمينات العامة &mdash; شركة المدار الليبي للتأمين &mdash; هاتف الخدمة: 0910094100
            </div>
        </div>
    </div>

    @php
        $qrDataJson = json_encode($printData['qr_data']);
    @endphp
    <script>
        (function() {
            const qrData = {!! $qrDataJson !!};
            const qrText = JSON.stringify(qrData);
            const qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=90x90&data=' + encodeURIComponent(qrText);
            const qrContainer = document.getElementById('qrcode');
            if (qrContainer) {
                qrContainer.innerHTML = '<img src="' + qrApiUrl + '" alt="QR Code" style="width: 90px; height: 90px; display: block;" />';
            }
        })();

        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 400);
        };
    </script>
</body>
</html>