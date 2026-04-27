<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>وثيقة تأمين نقل النقدية - {{ $document->policy_number }}</title>
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
            وثيقة تأمين نقل النقدية <br>
            Cash in Transit Insurance Policy
        </div>

        <table>
            <tr>
                <th>رقم الوثيقة Document number</th>
                <td>{{ $document->policy_number }}</td>
            </tr>
            <tr>
                <th>المؤمن له / الواتساب</th>
                <td>{{ $document->insured_name }} / {{ $document->whatsapp_number ?? '-' }}</td>
            </tr>
            <tr>
                <th>تاريخ الإصدار Issue date</th>
                <td>{{ $document->created_at->format('Y/m/d') }}</td>
            </tr>
        </table>

        <h3>بيانات الرحلة والحدود</h3>
        <table>
            <tr>
                <th>مسار النقل</th>
                <td>من: {{ $document->transit_from ?? '-' }} إلى: {{ $document->transit_to ?? '-' }}</td>
            </tr>
            <tr>
                <th>حد النقلة الواحدة</th>
                <td>{{ number_format($document->limit_per_transit, 3) }} د.ل</td>
            </tr>
            <tr>
                <th>إجمالي التداول السنوي</th>
                <td>{{ $document->annual_turnover ? number_format($document->annual_turnover, 3) . ' د.ل' : '-' }}</td>
            </tr>
        </table>

        <h3>بيانات القسط وفترة التغطية</h3>
        <table>
            <tr>
                <th>بداية التأمين</th>
                <td>{{ $document->start_date }}</td>
            </tr>
            <tr>
                <th>نهاية التأمين</th>
                <td>{{ $document->end_date }}</td>
            </tr>
            <tr>
                <th>القسط الإجمالي</th>
                <td>{{ number_format($document->premium_amount, 3) }} د.ل</td>
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
