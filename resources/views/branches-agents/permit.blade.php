<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إذن مباشرة عمل - {{ $branchAgent->agency_name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        @page {
            size: A4;
            margin: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Tajawal', 'Arial', sans-serif;
            font-size: 14px;
            color: #1e293b;
            background: #fff;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .permit-card {
            width: 210mm;
            height: 297mm;
            padding: 25mm 20mm;
            border: 15px double #1e3a8a;
            position: relative;
            background-color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
        }

        /* Decorative frame corner */
        .permit-card::before {
            content: "";
            position: absolute;
            top: 10px;
            left: 10px;
            right: 10px;
            bottom: 10px;
            border: 2px solid #3b82f6;
            pointer-events: none;
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 20px;
        }

        .header-right {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .logo-img {
            max-height: 75px;
            object-fit: contain;
            margin-bottom: 10px;
        }

        .agent-code-box {
            font-size: 18px;
            font-weight: 800;
            color: #1e3a8a;
            background-color: #eff6ff;
            padding: 5px 15px;
            border-radius: 8px;
            border: 1px dashed #3b82f6;
            display: inline-block;
            text-align: center;
        }

        .header-left {
            text-align: left;
            font-size: 13px;
            font-weight: 700;
            color: #475569;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .date-item span {
            font-family: monospace;
            font-size: 14px;
            color: #0f172a;
        }

        .title-section {
            text-align: center;
            margin-bottom: 35px;
        }

        .permit-title {
            font-size: 26px;
            font-weight: 900;
            color: #1e3a8a;
            display: inline-block;
            border-bottom: 3px double #1e3a8a;
            padding-bottom: 5px;
            letter-spacing: 0.5px;
        }

        .body-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-bottom: 40px;
        }

        .statement-text {
            font-size: 17px;
            text-align: justify;
            color: #334155;
            line-height: 1.8;
        }

        .highlight-text {
            font-weight: 800;
            color: #1e3a8a;
            border-bottom: 1px solid #cbd5e1;
            padding: 0 4px;
        }

        .table-title {
            font-size: 16px;
            font-weight: 800;
            color: #1e3a8a;
            margin-top: 15px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .table-title::after {
            content: "";
            flex: 1;
            height: 1px;
            background-color: #cbd5e1;
        }

        .docs-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }

        .docs-table th, .docs-table td {
            border: 1px solid #94a3b8;
            padding: 10px 15px;
            text-align: right;
        }

        .docs-table th {
            background-color: #f1f5f9;
            color: #1e293b;
            font-weight: 900;
            font-size: 14px;
            width: 85%;
        }

        .docs-table th.num-col, .docs-table td.num-col {
            text-align: center;
            width: 15%;
            font-weight: 800;
        }

        .docs-table td {
            font-size: 15px;
            font-weight: 700;
            color: #334155;
        }

        .docs-table tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .footer-section {
            border-top: 2px solid #e2e8f0;
            padding-top: 25px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .signature-box {
            text-align: center;
            width: 45%;
        }

        .signature-company {
            font-size: 15px;
            font-weight: 800;
            color: #1e3a8a;
            margin-bottom: 5px;
        }

        .signature-title {
            font-size: 15px;
            font-weight: 700;
            color: #475569;
            margin-bottom: 25px;
        }

        .signature-line {
            font-size: 12px;
            color: #94a3b8;
        }

        .qr-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: -10px;
        }

        #qrcode img {
            max-width: 70px;
            padding: 3px;
            border: 1px solid #e2e8f0;
            background: #fff;
        }

        @media print {
            body {
                padding: 0;
                background: none;
            }
            .permit-card {
                border: 15px double #1e3a8a !important;
                width: 210mm;
                height: 297mm;
                box-shadow: none;
                page-break-after: avoid;
                page-break-before: avoid;
            }
        }
    </style>
</head>

<body>
    <div class="permit-card">
        <!-- Header -->
        <div class="header-section">
            <div class="header-right">
                <img src="{{ asset('img/logo.png') }}" alt="المدار الليبي للتأمين" class="logo-img" onerror="this.onerror=null;this.src='/img/logo.png';">
                <div class="agent-code-box">
                    وكيل رقم: {{ $branchAgent->agency_number ?? $branchAgent->code }}
                </div>
            </div>
            
            <div class="qr-container">
                <div id="qrcode"></div>
            </div>

            <div class="header-left">
                <div class="date-item">تاريخ الإصدار: <span>{{ \Carbon\Carbon::parse($branchAgent->contract_date)->format('Y/m/d') }}م</span></div>
                @if($branchAgent->renewal_date)
                    <div class="date-item">تاريخ التجديد: <span>{{ \Carbon\Carbon::parse($branchAgent->renewal_date)->format('Y/m/d') }}م</span></div>
                @endif
                <div class="date-item">تاريخ الانتهاء: <span>{{ $branchAgent->contract_end_date ? \Carbon\Carbon::parse($branchAgent->contract_end_date)->format('Y/m/d') . 'م' : 'مفتوح' }}</span></div>
            </div>
        </div>

        <!-- Title -->
        <div class="title-section">
            <h1 class="permit-title">إذن مباشرة عمل خاص بالوكلاء</h1>
        </div>

        <!-- Body Statement -->
        <div class="body-section">
            <p class="statement-text">
                يؤذن للسيد/ة: <span class="highlight-text">{{ $branchAgent->agent_name }}</span>، 
                رقم الهوية/جواز سفر: <span class="highlight-text">{{ $branchAgent->identity_number ?? $branchAgent->national_id ?? '-' }}</span>،
                والممثل القانوني لـ: <span class="highlight-text">{{ $branchAgent->agency_name }}</span> الكائن مقرها بـ: <span class="highlight-text">{{ $branchAgent->address ?? $branchAgent->city }}</span>.
            </p>
            <p class="statement-text">
                بإصدار وثائق التأمين المبينة بعقد الاتفاق المبرم مع شركة المدار الليبي للتأمين، ويصرح له في إصدار وثائق التأمين المذكورة بالجدول التالي:
            </p>

            <div class="table-title">الوثائق التأمينية المصرح بها</div>
            
            <table class="docs-table">
                <thead>
                    <tr>
                        <th class="num-col">م</th>
                        <th>نوع الوثائق المصرح بإصدارها</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $docs = $branchAgent->authorized_documents ?? [];
                        if (empty($docs) && $branchAgent->user) {
                            $docs = $branchAgent->user->authorized_documents ?? [];
                        }
                    @endphp
                    
                    @if(count($docs) > 0)
                        @foreach($docs as $index => $doc)
                            <tr>
                                <td class="num-col">{{ $index + 1 }}</td>
                                <td>{{ $doc }}</td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="2" style="text-align: center; color: #94a3b8;">لم يتم تحديد وثائق مصرح بها بعد.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Signatures -->
        <div class="footer-section">
            <div class="signature-box">
                <div class="signature-company">شركة المدار الليبي للتأمين</div>
                <div class="signature-title">إدارة الوكالات والأفرع</div>
                <div class="signature-line">التوقيع والختم: ................................</div>
            </div>
            <div class="signature-box">
                <div class="signature-company">شركة المدار الليبي للتأمين</div>
                <div class="signature-title">مدير الشؤون الإدارية</div>
                <div class="signature-line">التوقيع والختم: ................................</div>
            </div>
        </div>
    </div>

    <script>
        // Generate QR Code
        new QRCode(document.getElementById("qrcode"), {
            text: window.location.origin + "/branches-agents/" + "{{ $branchAgent->id }}",
            width: 70,
            height: 70,
            colorDark : "#1e3a8a",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });

        window.onload = function () {
            setTimeout(function () {
                window.print();
            }, 800);
        };
    </script>
</body>

</html>
