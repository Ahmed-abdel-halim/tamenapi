<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>وثيقة تأمين حماية طلاب المدارس - {{ $document->policy_number }}</title>
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
        .qr-placeholder { width: 100px; height: 100px; border: 1px solid #ccc; display: flex; align-items: center; justify-content: center; font-size: 10px; }
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
            وثيقة تأمين حماية طلاب المدارس <br>
            School Student Protection Policy
        </div>

        <table>
            <tr>
                <th>رقم الوثيقة</th>
                <td>{{ $document->policy_number }}</td>
            </tr>
            <tr>
                <th>تاريخ الإصدار</th>
                <td>{{ $document->created_at->format('Y/m/d') }}</td>
            </tr>
        </table>

        <h3>بيانات المؤمن عليه (الطالب)</h3>
        <table>
            <tr>
                <th>اسم الطالب Student name</th>
                <td>{{ $document->student_name }}</td>
            </tr>
            <tr>
                <th>المرحلة الدراسية Grade</th>
                <td>{{ $document->grade ?? '-' }}</td>
            </tr>
            <tr>
                <th>تاريخ الميلاد / الواتساب</th>
                <td>{{ $document->birth_date ?? '-' }} / {{ $document->whatsapp_number ?? '-' }}</td>
            </tr>
        </table>

        <h3>بيانات المدرسة والتغطية</h3>
        <table>
            <tr>
                <th>اسم المدرسة</th>
                <td>{{ $document->school_name }}</td>
            </tr>
            <tr>
                <th>فترة التأمين</th>
                <td>من: {{ $document->start_date }} إلى: {{ $document->end_date }}</td>
            </tr>
            <tr>
                <th>القسط الإجمالي</th>
                <td>{{ number_format($document->premium_amount, 3) }} د.ل</td>
            </tr>
        </table>

        <div class="footer">
            <div class="signature">
                <p>ختم الوكيل / الفرع</p>
                <div style="height: 60px;"></div>
                <hr>
            </div>
            <div class="signature">
                <p>توقيع الموظف المختص</p>
                <div style="height: 60px;"></div>
                <hr>
            </div>
        </div>
        
        <p style="font-size: 10px; margin-top: 20px; text-align: center;">تخضع هذه الوثيقة للشروط العامة والخاصة المنظمة لتأمين حماية الطلاب بالشركة.</p>
    </div>
    <script>window.print();</script>
</body>
</html>
