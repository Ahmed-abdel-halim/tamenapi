<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عقد الوكيل - {{ $branchAgent->agency_name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        @page {
            size: A4;
            margin: 8mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Tajawal', 'Arial', sans-serif;
            font-size: 10px;
            color: #000;
            background: #fff;
            line-height: 1.3;
            height: 100vh;
        }

        .contract-page {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            border: 2px solid #000;
            padding: 12px;
            position: relative;
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .logo-container {
            width: 150px; /* Reduced width to give more space to title */
            text-align: right;
        }

        .logo-img {
            max-height: 70px;
        }

        .qr-container {
            width: 150px; /* Reduced width to give more space to title */
            display: flex;
            justify-content: flex-end;
        }

        #qrcode {
            display: inline-block;
        }

        #qrcode img {
            max-width: 60px;
            border: 1px solid #eee;
            padding: 2px;
        }

        .header-titles {
            flex: 1;
            text-align: center;
            padding: 0 10px;
        }

        .contract-title {
            font-size: 28px; /* Increased size */
            font-weight: 900;
            color: #4b5563;
            background: #f3f4f6;
            display: inline-block;
            padding: 8px 40px; /* Increased padding */
            border-radius: 12px;
            border: 1.5px solid #d1d5db;
            white-space: nowrap; /* Force one line */
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .meta-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-weight: 800;
            font-size: 11.5px;
        }

        .section-header {
            background: #f3f4f6;
            font-weight: 900;
            text-align: center;
            padding: 4px;
            border: 1px solid #000;
            font-size: 12px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .data-table td {
            border: 1px solid #000;
            padding: 3px 6px;
            vertical-align: middle;
            text-align: right;
        }

        .label {
            font-weight: 800;
            background: #f9fafb;
            width: 18%;
        }

        .value {
            width: 32%;
        }

        .terms-container {
            flex: 1;
            border: 1px solid #000;
            padding: 8px 15px;
            font-size: 10px;
            text-align: justify;
        }

        .terms-title {
            font-weight: 900;
            margin-bottom: 6px;
            text-decoration: underline;
            font-size: 11px;
        }

        .term-item {
            margin-bottom: 3px;
        }

        .signatures-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 10px;
            border-top: 2px solid #000;
            padding-top: 8px;
        }

        .signature-box {
            text-align: center;
        }

        .signature-title {
            font-weight: 800;
            font-size: 12px;
            margin-bottom: 10px;
            text-decoration: underline;
        }

        .signature-line {
            margin-top: 10px;
            font-weight: 700;
        }

        @media print {
            body { margin: 0; padding: 0; }
            .contract-page { height: 280mm; }
        }
    </style>
</head>

<body>
    <div class="contract-page">
        <!-- Header -->
        <div class="header-section">
            <!-- Logo on the Right -->
            <div class="logo-container">
                <img src="{{ asset('img/logo.png') }}" alt="المدار الليبي للتأمين" class="logo-img" onerror="this.src='/img/logo.png';">
            </div>
            
            <div class="header-titles">
                <h1 class="contract-title">عقد وكيل تقديم خدمات تأمين</h1>
            </div>

            <!-- QR Code on the Left -->
            <div class="qr-container">
                <div id="qrcode"></div>
            </div>
        </div>

        <!-- Meta -->
        <div class="meta-info">
            <div>رقم العقد: <span style="direction: ltr; display: inline-block;">{{ $branchAgent->agency_number ?? $branchAgent->code }}</span></div>
            <div>تاريخ الإصدار: {{ \Carbon\Carbon::parse($branchAgent->contract_date)->format('Y/m/d') }}</div>
        </div>

        <!-- Parties -->
        <div class="section-header">بيانات أطراف التعاقد</div>
        <table class="data-table">
            <tr>
                <td class="label">الطرف الأول (الموكل):</td>
                <td class="value">المدار الليبي للتأمين</td>
                <td class="label">الطرف الثاني (الوكيل):</td>
                <td class="value">{{ $branchAgent->agent_name }}</td>
            </tr>
            <tr>
                <td class="label">رقم القيد التجاري:</td>
                <td class="value">123456789</td>
                <td class="label">اسم الوكالة:</td>
                <td class="value">{{ $branchAgent->agency_name }}</td>
            </tr>
            <tr>
                <td class="label">العنوان الوطني:</td>
                <td class="value">ليبيا - طرابلس</td>
                <td class="label">المدينة / الفرع:</td>
                <td class="value">{{ $branchAgent->city }}</td>
            </tr>
            <tr>
                <td class="label">الرقم الوطني للوكيل:</td>
                <td class="value">{{ $branchAgent->national_id ?? '-' }}</td>
                <td class="label">رقم الهاتف:</td>
                <td class="value" style="direction: ltr; text-align: right;">{{ $branchAgent->phone ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">مدة العقد:</td>
                <td class="value">{{ $branchAgent->contract_duration ?? 'سنة واحدة' }}</td>
                <td class="label">تاريخ انتهاء العقد:</td>
                <td class="value">{{ $branchAgent->contract_end_date ? \Carbon\Carbon::parse($branchAgent->contract_end_date)->format('Y/m/d') : '-' }}</td>
            </tr>
        </table>

        <!-- Terms -->
        <div class="terms-container">
            <div class="terms-title">البنود والشروط العامة للتعاقد:</div>
            
            @if($branchAgent->contract_conditions)
                <div style="white-space: pre-wrap; line-height: 1.5;">{!! nl2br(e($branchAgent->contract_conditions)) !!}</div>
            @else
                <div class="term-item"><strong>تمهيد:</strong> بناءً على رغبة الطرفين في التعاون المشترك، فقد اتفقا على أن يعمل الطرف الثاني وكيلاً معتمداً للطرف الأول.</div>
                <div class="term-item">1. يتعهد الطرف الثاني بالعمل لحساب ولصالح الطرف الأول وبإشرافه المباشر في إصدار وثائق التأمين المعتمدة.</div>
                <div class="term-item">2. يلتزم الطرف الثاني بالتقيد بالأسعار واللوائح الفنية والمالية الصادرة عن الشركة (الطرف الأول).</div>
                <div class="term-item">3. تصرف العمولات للطرف الثاني بناءً على صافي الأقساط المحصلة والمراجعة مالياً وفنياً.</div>
                <div class="term-item">4. يلتزم الطرف الثاني باستخدام منظومة الشركة الإلكترونية حصراً في عمليات الإصدار والتحصيل.</div>
                <div class="term-item">5. مدة هذا العقد ({{ $branchAgent->contract_duration ?? 'سنة واحدة' }})، تبدأ من تاريخ توقيعه وتجدد بموافقة الطرفين.</div>
                <div class="term-item">6. يحق للطرف الأول فسخ العقد فوراً في حال ثبوت مخالفة الطرف الثاني لأي من بنود العقد أو اللوائح المنظمة.</div>
                <div class="term-item">7. يقر الطرف الثاني بمسؤوليته الكاملة عن صحة البيانات المدخلة في الوثائق الصادرة من طرفه.</div>
                <div class="term-item">8. يخضع هذا العقد لأحكام القانون الليبي، وفي حال النزاع يختص القضاء الليبي بالفصل فيه.</div>
            @endif
        </div>

        <!-- Signatures -->
        <div class="signatures-section">
            <div class="signature-box">
                <div class="signature-title">الطرف الأول (شركة المدار الليبي للتأمين)</div>
                <div style="margin-top: 5px;">الاسم: لطفيه رحومه</div>
                <div>الصفة: مدير الأفرع والوكلاء</div>
                <div class="signature-line">التوقيع والختم: ................................</div>
            </div>
            <div class="signature-box">
                <div class="signature-title">الطرف الثاني (الوكيل المعتمد)</div>
                <div style="margin-top: 5px;">الاسم: {{ $branchAgent->agent_name }}</div>
                <div>الصفة: وكيل خدمات تأمين</div>
                <div class="signature-line">التوقيع والختم: ................................</div>
            </div>
        </div>
    </div>

    <script>
        // Generate QR Code
        new QRCode(document.getElementById("qrcode"), {
            text: window.location.href,
            width: 80,
            height: 80,
            colorDark : "#000000",
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