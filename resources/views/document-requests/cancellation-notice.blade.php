<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إشعار إلغاء وثيقة تأمين - {{ $request->request_code ?? $request->document_number }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        @page {
            size: A4;
            margin: 10mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Tajawal', 'Arial', sans-serif;
            font-size: 11px;
            color: #111827;
            background: #f9fafb;
            line-height: 1.4;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            padding: 15px;
        }

        .notice-container {
            width: 100%;
            max-width: 210mm;
            background: #fff;
            border: 2px solid #374151;
            padding: 18px;
            position: relative;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        /* Top Header */
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #1f2937;
            padding-bottom: 12px;
            margin-bottom: 15px;
        }

        .logo-container {
            width: 170px;
            text-align: right;
        }

        .logo-img {
            max-height: 70px;
            object-fit: contain;
        }

        .header-titles {
            flex: 1;
            text-align: center;
            padding: 0 10px;
        }

        .company-name {
            font-size: 14px;
            font-weight: 800;
            color: #1f2937;
            margin-bottom: 4px;
        }

        .notice-title {
            font-size: 24px;
            font-weight: 900;
            color: #b91c1c;
            background: #fee2e2;
            display: inline-block;
            padding: 6px 30px;
            border-radius: 8px;
            border: 1.5px solid #ef4444;
            letter-spacing: 0.5px;
        }

        .qr-container {
            width: 170px;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            justify-content: center;
        }

        #qrcode {
            display: inline-block;
            padding: 4px;
            border: 1px solid #d1d5db;
            background: #fff;
            border-radius: 4px;
        }

        /* Notice Banner */
        .notice-banner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 8px 14px;
            margin-bottom: 14px;
            font-weight: 700;
        }

        .code-badge {
            font-size: 16px;
            font-weight: 900;
            color: #1e3a8a;
            direction: ltr;
            display: inline-block;
            background: #dbeafe;
            padding: 3px 12px;
            border-radius: 6px;
            border: 1px solid #93c5fd;
        }

        .status-badge {
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 800;
        }

        .status-accepted {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #f87171;
        }

        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
            border: 1px solid #fcd34d;
        }

        .status-rejected {
            background-color: #f3f4f6;
            color: #4b5563;
            border: 1px solid #d1d5db;
        }

        /* Sections */
        .section {
            margin-bottom: 14px;
        }

        .section-header {
            background: #1e3a8a;
            color: #ffffff;
            font-weight: 800;
            padding: 6px 10px;
            font-size: 12px;
            border-radius: 4px 4px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #d1d5db;
        }

        .data-table td {
            padding: 7px 10px;
            border: 1px solid #e5e7eb;
            font-size: 11px;
        }

        .data-table td.label {
            font-weight: 800;
            background-color: #f9fafb;
            color: #374151;
            width: 20%;
        }

        .data-table td.val {
            font-weight: 600;
            color: #111827;
            width: 30%;
        }

        /* Legal Box */
        .legal-box {
            background: #fffbeb;
            border: 1.5px solid #f59e0b;
            border-radius: 6px;
            padding: 10px 14px;
            margin-bottom: 14px;
        }

        .legal-box-title {
            color: #92400e;
            font-weight: 900;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 5px;
        }

        .legal-box-content {
            color: #78350f;
            font-size: 10.5px;
            line-height: 1.5;
            text-align: justify;
            font-weight: 600;
        }

        /* Signatures */
        .signatures-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 12px;
            margin-top: 15px;
            border-top: 1.5px dashed #9ca3af;
            padding-top: 12px;
        }

        .signature-card {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 10px;
            text-align: center;
            background: #fbfbfb;
        }

        .signature-card-title {
            font-weight: 800;
            font-size: 11px;
            color: #1f2937;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 4px;
            margin-bottom: 8px;
        }

        .signature-space {
            height: 45px;
        }

        .signature-name {
            font-size: 10px;
            color: #4b5563;
            font-weight: 700;
        }

        /* Footer watermark */
        .footer-note {
            margin-top: 12px;
            text-align: center;
            font-size: 9.5px;
            color: #6b7280;
            border-top: 1px solid #f3f4f6;
            padding-top: 6px;
        }

        .no-print-bar {
            position: fixed;
            top: 10px;
            left: 10px;
            display: flex;
            gap: 10px;
            z-index: 1000;
        }

        .print-btn {
            background: #1e3a8a;
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-family: 'Tajawal', sans-serif;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0,0,0,0.15);
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .notice-container {
                box-shadow: none;
                border: 2px solid #000;
                padding: 12px;
            }
            .no-print-bar {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    <div class="no-print-bar">
        <button class="print-btn" onclick="window.print()">🖨️ طباعة الإشعار</button>
    </div>

    <div class="notice-container">
        <!-- Header -->
        <div class="header-section">
            <div class="logo-container">
                <img src="{{ asset('img/logo.png') }}" alt="المدار الليبي للتأمين" class="logo-img" onerror="this.onerror=null;this.src='/img/logo.png';">
            </div>

            <div class="header-titles">
                <div class="company-name">شركة المدار الليبي للتأمين ش.م.ل</div>
                <div class="notice-title">إشعار إلغاء وثيقة تأمين</div>
            </div>

            <div class="qr-container">
                <div id="qrcode"></div>
                <div style="font-size: 9px; color: #6b7280; margin-top: 3px;">التحقق الإلكتروني</div>
            </div>
        </div>

        <!-- Banner with Code and Status -->
        <div class="notice-banner">
            <div>
                <span>رقم طلب الإلغاء: </span>
                <span class="code-badge">{{ $request->request_code ?? 'AL-' . str_pad($request->id, 6, '0', STR_PAD_LEFT) }}</span>
            </div>
            <div>
                <span>تاريخ التقديم: </span>
                <span>{{ $request->created_at ? $request->created_at->format('Y-m-d H:i') : now()->format('Y-m-d H:i') }}</span>
            </div>
            <div>
                <span>حالة الطلب: </span>
                @if($request->status === 'accepted')
                    <span class="status-badge status-accepted">ملغية رسمياً</span>
                @elseif($request->status === 'rejected')
                    <span class="status-badge status-rejected">طلب مرفوض</span>
                @else
                    <span class="status-badge status-pending">قيد مراجعة الإدارة</span>
                @endif
            </div>
        </div>

        <!-- Document Details -->
        <div class="section">
            <div class="section-header">
                <span>1. بيانات الوثيقة المراد إلغاؤها</span>
                <span>Document Details</span>
            </div>
            <table class="data-table">
                <tr>
                    <td class="label">رقم الوثيقة:</td>
                    <td class="val" style="font-weight: 900; color: #b91c1c; direction: ltr; text-align: right;">{{ $request->document_number }}</td>
                    <td class="label">نوع التأمين:</td>
                    <td class="val">{{ $request->document_type_label ?? $request->document_type }}</td>
                </tr>
                <tr>
                    <td class="label">اسم المؤمن له:</td>
                    <td class="val" style="font-weight: 800;">{{ $request->insured_name ?: 'غير محدد' }}</td>
                    <td class="label">كود الوثيقة في المنظومة:</td>
                    <td class="val">#{{ $request->document_id ?? '-' }}</td>
                </tr>
            </table>
        </div>

        <!-- Agent & Submitter Details -->
        <div class="section">
            <div class="section-header">
                <span>2. بيانات الوكالة ومقدم طلب الإلغاء</span>
                <span>Agency & Applicant</span>
            </div>
            <table class="data-table">
                <tr>
                    <td class="label">اسم الوكالة / الفرع:</td>
                    <td class="val">{{ $request->branchAgent->agency_name ?? 'الفرع الرئيسي' }}</td>
                    <td class="label">رقم قيد / كود الوكيل:</td>
                    <td class="val">{{ $request->branchAgent->agency_number ?? $request->branchAgent->code ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">اسم الموظف / الوكيل:</td>
                    <td class="val" style="font-weight: 800;">{{ $request->applicant_name ?? ($request->user->name ?? '-') }}</td>
                    <td class="label">البريد / هاتف الوكالة:</td>
                    <td class="val">{{ $request->branchAgent->phone ?? ($request->user->email ?? '-') }}</td>
                </tr>
            </table>
        </div>

        <!-- Cancellation Reason -->
        <div class="section">
            <div class="section-header">
                <span>3. أسباب ومسوغات الإلغاء</span>
                <span>Cancellation Reason</span>
            </div>
            <table class="data-table">
                <tr>
                    <td class="label">السبب الرئيسي للإلغاء:</td>
                    <td class="val" colspan="3" style="font-weight: 800; color: #b91c1c;">
                        {{ $request->cancellation_reason ?: 'طلب إلغاء مقدم من الوكيل' }}
                    </td>
                </tr>
                @if($request->cancellation_reason_other)
                <tr>
                    <td class="label">تفاصيل السبب الآخر:</td>
                    <td class="val" colspan="3">{{ $request->cancellation_reason_other }}</td>
                </tr>
                @endif
                @if($request->notes)
                <tr>
                    <td class="label">ملاحظات مقدم الطلب:</td>
                    <td class="val" colspan="3">{{ $request->notes }}</td>
                </tr>
                @endif
            </table>
        </div>

        <!-- Legal Disclaimer & Liability -->
        <div class="legal-box">
            <div class="legal-box-title">
                <span>⚠️ إقرار قانوني وإخلاء مسؤولية مشدد:</span>
            </div>
            <div class="legal-box-content">
                بناءً على طلب الإلغاء المقدم أعلاه، يقر ويؤكد الوكيل / الموظف وممثل جهة الإصدار بصحة كافة المسوغات والبيانات الواردة، ويقر بتحمله كامل المسؤولية المدنية والجزائية والمالية عن تسليم أصل الوثيقة للعميل قبل الإلغاء أو عدم استرداد النسخ الأصلية. وتخلي <strong>شركة المدار الليبي للتأمين</strong> مسؤوليتها التأمينية والمالية والقانونية تجاه المؤمن له أو أي طرف ثالث عن أي حادث أو ضرر أو مطالبة تقع أو يُدعى وقوعها لهذه الوثيقة الملغاة بعد سريان الإلغاء، وتعتبر هذه الوثيقة باطلة ولاغية في كافة السجلات الرسمية والتأمينية ولا يُعتد بها إطلاقاً.
            </div>
        </div>

        <!-- Management Review (if reviewed) -->
        @if($request->reviewed_at || $request->status !== 'pending')
        <div class="section">
            <div class="section-header" style="background: #374151;">
                <span>4. قرار إدارة العمليات والاعتماد</span>
                <span>Management Action</span>
            </div>
            <table class="data-table">
                <tr>
                    <td class="label">حالة القرار:</td>
                    <td class="val">
                        @if($request->status === 'accepted')
                            <strong style="color: #15803d;">تمت الموافقة واعتماد الإلغاء</strong>
                        @elseif($request->status === 'rejected')
                            <strong style="color: #b91c1c;">تم الرفض والوثيقة سارية</strong>
                        @else
                            {{ $request->status }}
                        @endif
                    </td>
                    <td class="label">الموظف / المعتمد:</td>
                    <td class="val">{{ $request->reviewer->name ?? 'إدارة التأمين' }}</td>
                </tr>
                <tr>
                    <td class="label">تاريخ المراجعة:</td>
                    <td class="val">{{ $request->reviewed_at ? \Carbon\Carbon::parse($request->reviewed_at)->format('Y-m-d H:i') : '-' }}</td>
                    <td class="label">ملاحظات الإدارة:</td>
                    <td class="val">{{ $request->admin_message ?: 'لا توجد ملاحظات' }}</td>
                </tr>
            </table>
        </div>
        @endif

        <!-- Signatures & Stamps -->
        <div class="signatures-grid">
            <div class="signature-card">
                <div class="signature-card-title">مقدم الطلب / الوكيل المسؤول</div>
                <div class="signature-name">{{ $request->applicant_name ?? ($request->user->name ?? 'الوكيل المعتمد') }}</div>
                <div class="signature-space"></div>
                <div style="font-size: 9px; color: #6b7280;">التوقيع مع الإقرار بالمسؤولية الكاملة</div>
            </div>

            <div class="signature-card">
                <div class="signature-card-title">إدارة الإصدار والمراجعة الفنية</div>
                <div class="signature-name">{{ $request->reviewer->name ?? 'قسم العمليات التأمينية' }}</div>
                <div class="signature-space"></div>
                <div style="font-size: 9px; color: #6b7280;">التوقيع والاعتماد</div>
            </div>

            <div class="signature-card">
                <div class="signature-card-title">ختم الشركة والاعتماد العام</div>
                <div class="signature-space" style="height: 55px;"></div>
                <div style="font-size: 9px; color: #6b7280;">ختم شركة المدار الليبي للتأمين</div>
            </div>
        </div>

        <div class="footer-note">
            تم استخراج هذا الإشعار من المنظومة الإلكترونية لشركة المدار الليبي للتأمين | رقم التحقق المرجعي: {{ $request->request_code ?? $request->id }} | {{ now()->format('Y/m/d H:i:s') }}
        </div>
    </div>

    <script>
        // Generate QR Code containing verification summary
        var qrData = "شركة المدار الليبي للتأمين\nإشعار إلغاء وثيقة\nكود الإلغاء: {{ $request->request_code }}\nرقم الوثيقة: {{ $request->document_number }}\nالمؤمن له: {{ $request->insured_name }}\nالوكالة: {{ $request->branchAgent->agency_name ?? 'الرئيسي' }}\nالحالة: {{ $request->status === 'accepted' ? 'ملغية' : ($request->status === 'rejected' ? 'مرفوض' : 'قيد المراجعة') }}";
        
        new QRCode(document.getElementById("qrcode"), {
            text: qrData,
            width: 75,
            height: 75,
            colorDark : "#111827",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.M
        });

        window.onload = function () {
            // Optional auto print if ?autoprint=1
            var urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('autoprint') === '1') {
                setTimeout(function () {
                    window.print();
                }, 600);
            }
        };
    </script>
</body>

</html>
