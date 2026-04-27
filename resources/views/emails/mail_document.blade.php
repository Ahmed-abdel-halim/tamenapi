<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; padding: 20px; }
        .container { background-color: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto; border-top: 5px solid #2563eb; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #2563eb; margin: 0; }
        .content { margin-bottom: 30px; text-align: right; }
        .footer { text-align: center; font-size: 0.8em; color: #777; border-top: 1px solid #eee; padding-top: 20px; }
        .info-box { background-color: #f8fafc; padding: 15px; border-radius: 8px; border-right: 4px solid #2563eb; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>المدار الليبي للتأمين</h1>
        </div>
        <div class="content">
            <p>السادة المحترمون في <strong>{{ $document->entity->name ?? 'الجهة المعنية' }}</strong>،</p>
            <p>تحية طيبة وبعد،،</p>
            <p>نحيطكم علماً بأنه قد تم تسجيل مراسلة جديدة موجهة إليكم من قبل شركتنا.</p>
            
            <div class="info-box">
                <p><strong>موضوع الرسالة:</strong> {{ $document->subject }}</p>
                <p><strong>الرقم الإشاري:</strong> {{ $document->referential_number }}</p>
                <p><strong>تاريخ المراسلة:</strong> {{ $document->date->format('Y-m-d') }}</p>
            </div>

            @if($document->description)
                <p><strong>ملاحظات إضافية:</strong><br>{{ $document->description }}</p>
            @endif

            <p>تجدون طيه نسخة إلكترونية من المراسلة (مرفق). يرجى التكرم بالعلم بأن الأصل متاح للاستلام عبر مندوبكم من مقر شركتنا.</p>
        </div>
        <div class="footer">
            <p>هذا البريد تم إرساله تلقائياً من نظام الأرشفة الإلكتروني لشركة المدار الليبي للتأمين</p>
            <p>&copy; {{ date('Y') }} Almadar Libyan Insurance</p>
        </div>
    </div>
</body>
</html>
