<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>وثيقة تأمين شحن البضائع - {{ $document->policy_number }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <style>
        @page { size: A4; margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 297mm; width: 210mm; overflow: hidden; }
        body { font-family: 'Tajawal', sans-serif; direction: rtl; text-align: right; font-size: 14px; color: #333; }
        .container { width: 210mm; height: 296.5mm; border: 2px solid #000; padding: 10mm; position: relative; overflow: hidden; margin: 0; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #000; padding-bottom: 15px; margin-bottom: 20px; }
        .company-info h1 { font-size: 22px; margin: 0; color: #000; }
        .document-title { text-align: center; background: #eee; padding: 10px; font-weight: bold; font-size: 18px; border: 1px solid #000; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table, th, td { border: 1px solid #000; }
        th, td { padding: 10px; text-align: right; }
        th { background: #f5f5f5; width: 30%; }
        .footer { margin-top: 30px; display: flex; justify-content: space-between; }
        .signature { text-align: center; width: 200px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="company-info">
                <h1>شركة المدار الليبي للتأمين</h1>
                <p>Al Madar Libyan Insurance</p>
            </div>
            <div class="logo">
                <img src="/img/logo.png" alt="Logo" style="height: 80px;" onerror="this.src='https://via.placeholder.com/80x80?text=LOGO'">
            </div>
        </div>

        <div class="document-title">
            وثيقة تأمين شحن البضائع <br>
            Cargo Insurance Policy
        </div>

        <table>
            <tr>
                <th>رقم الوثيقة</th>
                <td>{{ $document->policy_number }}</td>
            </tr>
            <tr>
                <th>اسم المؤمن له</th>
                <td>{{ $document->insured_name }}</td>
            </tr>
            <tr>
                <th>تاريخ الإصدار</th>
                <td>{{ $document->created_at->format('Y/m/d') }}</td>
            </tr>
        </table>

        <h3>بيانات الشحنة والرحلة</h3>
        <table>
            <tr>
                <th>وصف البضاعة</th>
                <td>{{ $document->cargo_description }}</td>
            </tr>
            <tr>
                <th>نوع النقل</th>
                <td>{{ $document->transport_type }}</td>
            </tr>
            <tr>
                <th>مسار الرحلة</th>
                <td>من: {{ $document->voyage_from ?? '-' }} إلى: {{ $document->voyage_to ?? '-' }}</td>
            </tr>
        </table>

        <h3>بيانات التغطية والقسط</h3>
        <table>
            <tr>
                <th>مبلغ التأمين (Sum Insured)</th>
                <td>{{ number_format($document->sum_insured, 3) }} د.ل</td>
            </tr>
            <tr>
                <th>القسط الإجمالي</th>
                <td>{{ number_format($document->premium_amount, 3) }} د.ل</td>
            </tr>
            <tr>
                <th>البيان</th>
                <td>تأمين بضائع مشحونة ضد أخطار النقل</td>
            </tr>
        </table>

        <div class="footer">
            <div class="signature">
                <p>الختم الرسمي</p>
                <div style="height: 60px;"></div>
                <hr>
            </div>
            <div class="signature">
                <p>توقيع المسؤول المختص</p>
                <div style="height: 60px;"></div>
                <hr>
            </div>
        </div>
    </div>
    <script>window.print();</script>
</body>
</html>
