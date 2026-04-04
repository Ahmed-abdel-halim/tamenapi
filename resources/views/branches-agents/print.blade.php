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
            margin: 15mm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Tajawal', 'Arial', 'Tahoma', sans-serif;
            font-size: 13px;
            color: #000;
            background: #fff;
            padding: 0;
            line-height: 1.5;
        }
        
        .contract-container {
            max-width: 100%;
            margin: 0 auto;
            background: #fff;
            padding: 15px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #000;
        }
        
        .qr-code {
            width: 90px;
            height: 90px;
            background: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 8px;
            text-align: center;
            flex-shrink: 0;
        }
        
        .company-info {
            text-align: center;
            flex: 1;
            padding: 0 15px;
        }
        
        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #000;
            margin-bottom: 3px;
        }
        
        .contract-title {
            font-size: 16px;
            color: #000;
            font-weight: 600;
        }
        
        .logo {
            width: 110px;
            height: 110px;
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
        
        .section {
            margin-bottom: 18px;
        }
        
        .section-title {
            font-size: 15px;
            font-weight: bold;
            color: #000;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 12px;
        }
        
        .info-item {
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }
        
        .info-label {
            font-weight: bold;
            min-width: 110px;
            color: #000;
            font-size: 13px;
        }
        
        .info-value {
            flex: 1;
            color: #000;
            border-bottom: 1px dotted #9ca3af;
            padding-bottom: 2px;
            min-height: 18px;
            font-size: 13px;
        }
        
        .table-container {
            margin-top: 12px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 13px;
        }
        
        table th {
            background: #fff;
            color: #000;
            padding: 6px 8px;
            text-align: right;
            font-weight: bold;
            font-size: 12px;
            border: 1px solid #000;
        }
        
        table td {
            padding: 6px 8px;
            border: 1px solid #000;
            text-align: right;
            font-size: 13px;
        }
        
        table tr:nth-child(even) {
            background: #f9fafb;
        }
        
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 35px;
            margin-bottom: 15px;
            gap: 15px;
        }
        
        .signature-box {
            flex: 1;
            text-align: center;
        }
        
        .signature-label {
            font-weight: bold;
            margin-bottom: 25px;
            color: #000;
            font-size: 13px;
        }
        
        .signature-line {
            border-top: 1px dotted #9ca3af;
            margin-top: 5px;
            padding-top: 5px;
            min-height: 40px;
        }
        
        .seal-box {
            border: 2px dashed #9ca3af;
            padding: 15px;
            text-align: center;
            min-height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .seal-label {
            font-weight: bold;
            color: #000;
            font-size: 13px;
        }
        
        .footer {
            display: flex;
            justify-content: space-between;
            margin-top: 25px;
            padding-top: 8px;
            border-top: 1px solid #e5e7eb;
            font-size: 11px;
            color: #6b7280;
        }
        
        .notes-section {
            background: #f9fafb;
            padding: 8px 12px;
            border-right: 3px solid #000;
            margin-top: 12px;
            font-size: 13px;
            line-height: 1.6;
        }
        
        @media print {
            body {
                padding: 0;
                margin: 0;
            }
            
            .contract-container {
                max-width: 100%;
                padding: 0;
            }
            
            .no-print {
                display: none;
            }
            
            @page {
                margin: 15mm;
            }
        }
    </style>
</head>
<body>
    <div class="contract-container">
        <!-- Header -->
        <div class="header">
            <div class="logo">
                <img src="/img/logo.png" alt="شعار الشركة" onerror="this.style.display='none'; this.parentElement.innerHTML='<div style=\'width:70px;height:70px;background:#000;color:#fff;display:flex;align-items:center;justify-content:center;font-size:8px;text-align:center;\'>LOGO</div>';" />
            </div>
            <div class="company-info">
                <div class="company-name">شركة المدار الليبي للتأمين</div>
                <div class="contract-title">عقد الوكيل</div>
            </div>
            <div class="qr-code" id="qrcode"></div>
        </div>

        <!-- Agent Information -->
        <div class="section">
            <div class="section-title">معلومات الوكيل</div>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">كود الوكيل:</span>
                    <span class="info-value">{{ $branchAgent->code }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">اسم الوكالة:</span>
                    <span class="info-value">{{ $branchAgent->agency_name }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">اسم الوكيل:</span>
                    <span class="info-value">{{ $branchAgent->agent_name }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">رقم الهاتف:</span>
                    <span class="info-value">{{ $branchAgent->phone ?? '-' }}</span>
                </div>
                @if($branchAgent->agency_number)
                <div class="info-item">
                    <span class="info-label">رقم الترخيص:</span>
                    <span class="info-value">{{ $branchAgent->agency_number }}</span>
                </div>
                @endif
                @if($branchAgent->stamp_number)
                <div class="info-item">
                    <span class="info-label">رقم الختم:</span>
                    <span class="info-value">{{ $branchAgent->stamp_number }}</span>
                </div>
                @endif
            </div>
        </div>

        <!-- Contract Dates -->
        <div class="section">
            <div class="section-title">تواريخ العقد</div>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">بداية العقد:</span>
                    <span class="info-value">{{ \Carbon\Carbon::parse($branchAgent->contract_date)->format('d/m/Y') }}</span>
                </div>
                @if($branchAgent->contract_end_date)
                <div class="info-item">
                    <span class="info-label">نهاية العقد:</span>
                    <span class="info-value">{{ \Carbon\Carbon::parse($branchAgent->contract_end_date)->format('d/m/Y') }}</span>
                </div>
                @endif
                @if($branchAgent->contract_duration)
                <div class="info-item">
                    <span class="info-label">مدة العقد:</span>
                    <span class="info-value">{{ $branchAgent->contract_duration }}</span>
                </div>
                @endif
            </div>
        </div>

        <!-- Notes -->
        @if($branchAgent->notes)
        <div class="section">
            <div class="section-title">الملاحظات</div>
            <div class="notes-section">
                {{ $branchAgent->notes }}
            </div>
        </div>
        @endif

        <!-- Fixed Agent Assets -->
        @if($branchAgent->fixed_custodies && count($branchAgent->fixed_custodies) > 0)
        <div class="section">
            <div class="section-title">عهدة الوكيل الثابتة</div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>البيان</th>
                            <th>العدد</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($branchAgent->fixed_custodies as $custody)
                        <tr>
                            <td>{{ $custody['description'] ?? '-' }}</td>
                            <td>{{ number_format($custody['quantity'] ?? 0, 3) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Consumed Custodies -->
        @if($branchAgent->consumed_custodies && count($branchAgent->consumed_custodies) > 0)
        <div class="section">
            <div class="section-title">عهد مستهلكة</div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>البيان</th>
                            <th>العدد</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($branchAgent->consumed_custodies as $custody)
                        <tr>
                            <td>{{ $custody['description'] ?? '-' }}</td>
                            <td>{{ number_format($custody['quantity'] ?? 0, 3) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Authorized Documents -->
        @if($branchAgent->activity && (str_contains($branchAgent->activity, 'تامين') || str_contains($branchAgent->activity, 'تأمين')))
        <div class="section">
            <div class="section-title">الوثائق المصرح بها</div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>الوثيقة</th>
                            <th>النسبة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>تأمين إجباري سيارات</td>
                            <td>30.00%</td>
                        </tr>
                        <tr>
                            <td>تأمين سيارة جمرك</td>
                            <td>30.00%</td>
                        </tr>
                        <tr>
                            <td>تامين سيارات اجنبيه</td>
                            <td>30.00%</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Signatures -->
        <div class="signatures">
            <div class="signature-box">
                <div class="signature-label">توقيع الموظف</div>
                <div class="signature-line"></div>
            </div>
            <div class="signature-box">
                <div class="signature-label">توقيع الوكيل</div>
                <div class="signature-line"></div>
            </div>
            <div class="signature-box">
                <div class="seal-box">
                    <div class="seal-label">الختم</div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div>1</div>
            <div>{{ \Carbon\Carbon::now()->format('d-m-Y | H:i:s') }}</div>
        </div>
    </div>

    <script>
        // إنشاء QR code يحتوي على بيانات الوكيل
        window.onload = function() {
            const qrData = {
                code: '{{ $branchAgent->code }}',
                agency_name: '{{ $branchAgent->agency_name }}',
                agent_name: '{{ $branchAgent->agent_name }}',
                contract_date: '{{ \Carbon\Carbon::parse($branchAgent->contract_date)->format('Y-m-d') }}'
            };
            
            const qrText = JSON.stringify(qrData);
            
            // استخدام API لإنشاء QR code
            const qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=90x90&data=' + encodeURIComponent(qrText);
            const qrContainer = document.getElementById('qrcode');
            qrContainer.innerHTML = '<img src="' + qrApiUrl + '" alt="QR Code" style="width: 90px; height: 90px; display: block;" />';
        };
    </script>
</body>
</html>
