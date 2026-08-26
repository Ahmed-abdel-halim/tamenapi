<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير الحوافظ الإنتاجية - {{ $branchAgent->agency_name ?? '' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        @page {
            size: A4 landscape;
            margin: 8mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            font-family: 'Tajawal', 'Arial', 'Tahoma', sans-serif;
            font-size: 11px;
            color: #0f172a;
            background: #fff;
            padding: 0;
            line-height: 1.3;
        }

        .page-container {
            width: 100%;
            padding: 0;
            border: none;
            min-height: 190mm;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .report-section {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            padding-bottom: 6px;
        }

        .logo-box {
            width: 80px;
            text-align: right;
        }

        .logo-box img {
            max-height: 55px;
            max-width: 80px;
            object-fit: contain;
        }

        .title-box {
            text-align: center;
            flex: 1;
        }

        .main-title {
            font-size: 18px;
            font-weight: 900;
            color: #0284c7;
            margin-bottom: 4px;
        }

        .pill-badge {
            display: inline-block;
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 6px;
            padding: 4px 24px;
            font-size: 14px;
            font-weight: 800;
            color: #0369a1;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .agent-info-grid {
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            margin-bottom: 8px;
            overflow: hidden;
        }

        .info-cell {
            padding: 6px 12px;
            text-align: center;
            border-left: 1px solid #cbd5e1;
            font-size: 11px;
        }

        .info-cell:last-child {
            border-left: none;
        }

        .info-label {
            font-weight: 700;
            color: #475569;
            margin-bottom: 2px;
        }

        .info-val {
            font-weight: 800;
            color: #0f172a;
            font-size: 12px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 10px;
            text-align: center;
        }

        .data-table th {
            background: #f1f5f9;
            color: #1e293b;
            font-weight: 800;
            border: 1px solid #94a3b8;
            padding: 5px 3px;
            white-space: nowrap;
        }

        .data-table td {
            border: 1px solid #cbd5e1;
            padding: 4px 3px;
            color: #0f172a;
        }

        .data-table tr:nth-child(even) {
            background: #f8fafc;
        }

        .summary-wrapper {
            display: flex;
            justify-content: center;
            margin: 6px 0 12px 0;
        }

        .summary-table {
            width: 75%;
            border-collapse: collapse;
            font-size: 10.5px;
            text-align: center;
        }

        .summary-table th {
            background: #e2e8f0;
            color: #0f172a;
            font-weight: 800;
            border: 1px solid #94a3b8;
            padding: 4px 6px;
        }

        .summary-table td {
            background: #fff;
            border: 1px solid #94a3b8;
            padding: 5px 6px;
            font-weight: 800;
            color: #0284c7;
            font-size: 11px;
        }

        .signature-wrapper {
            display: flex;
            justify-content: flex-end; /* يضع الصندوق في أقصى اليسار في اتجاه RTL */
            margin-top: 15px;
            margin-bottom: 12px;
            page-break-inside: avoid;
        }

        .signature-box {
            width: 340px;
            border: 1.5px solid #1e293b;
            border-radius: 4px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        }

        .sig-header {
            background: #e2e8f0;
            font-weight: 800;
            padding: 6px 14px;
            font-size: 12px;
            color: #0f172a;
            border-bottom: 1.5px solid #1e293b;
            text-align: right;
        }

        .sig-body {
            height: 52px;
            background: #fff;
        }

        .footer-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 6px;
            border-top: 1px solid #94a3b8;
            font-size: 10px;
            color: #64748b;
            font-weight: 700;
            margin-top: auto;
        }

        @media print {
            @page {
                size: A4 landscape;
                margin: 6mm;
            }
            html, body {
                width: 100%;
                margin: 0;
                padding: 0;
                background: #fff !important;
            }
            .page-container {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                min-height: 100% !important;
            }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="page-container">
        <div>
            @php
                $logoPath = public_path('img/logo3.png');
                $logoBase64 = '';
                if (file_exists($logoPath)) {
                    $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
                } elseif (file_exists(public_path('img/logo.png'))) {
                    $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents(public_path('img/logo.png')));
                }
            @endphp

            @forelse ($sections as $section)
                <div class="report-section">
                    <!-- Header -->
                    <div class="header-top">
                        <div class="logo-box">
                            @if ($logoBase64)
                                <img src="{{ $logoBase64 }}" alt="Logo" />
                            @else
                                <div style="font-weight:900; color:#139625; font-size:10px;">المدار الليبي<br><span style="color:#0284c7;">للتأمين</span></div>
                            @endif
                        </div>

                        <div class="title-box">
                            <h1 class="main-title">شركة المدار الليبي للتأمين</h1>
                            <div class="pill-badge">
                                تقرير الحوافظ الإنتاجية {{ $section['title'] }}
                            </div>
                        </div>

                        <div class="logo-box" style="visibility: hidden;">
                            <img src="{{ $logoBase64 }}" alt="" />
                        </div>
                    </div>

                    <!-- Agent Header Info -->
                    <div class="agent-info-grid">
                        <div class="info-cell">
                            <div class="info-label">اسم الوكيل</div>
                            <div class="info-val">{{ $branchAgent->agency_name ?? '-' }}</div>
                        </div>
                        <div class="info-cell">
                            <div class="info-label">رقم الوكالة</div>
                            <div class="info-val">{{ $branchAgent->code ?? '-' }}</div>
                        </div>
                        <div class="info-cell">
                            <div class="info-label">اسم الموظف</div>
                            <div class="info-val">{{ $branchAgent->user->name ?? ($branchAgent->agent_name ?? 'الوكيل المسؤول') }}</div>
                        </div>
                    </div>

                    <!-- Table of Documents -->
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 25px;">#</th>
                                <th style="width: 90px;">رقم الوثيقة</th>
                                <th>اسم المؤمن له</th>
                                <th style="width: 75px;">تاريخ الاصدار</th>
                                <th style="width: 70px;">رقم اللوحة</th>
                                <th style="width: 65px;">القسط الصافي</th>
                                <th style="width: 50px;">الضريبة</th>
                                <th style="width: 55px;">أ. ورقابة</th>
                                <th style="width: 50px;">الدمغة</th>
                                <th style="width: 55px;">م. الاصدار</th>
                                <th style="width: 120px;">{{ $section['detail_header'] ?? 'قوة المحرك بالحصان' }}</th>
                                <th style="width: 65px;">الاجمالي</th>
                                <th style="width: 70px;">اسم المستخدم</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($section['documents'] as $idx => $doc)
                                <tr>
                                    <td>{{ $idx + 1 }}</td>
                                    <td style="font-weight: 800; color: #0369a1;">{{ $doc['document_number'] }}</td>
                                    <td style="text-align: right; padding-right: 6px; font-weight: 600;">{{ $doc['insured_name'] }}</td>
                                    <td>{{ $doc['issue_date'] }}</td>
                                    <td>{{ $doc['plate_number'] ?? '-' }}</td>
                                    <td style="font-weight: 700;">{{ number_format($doc['premium'], 3) }}</td>
                                    <td>{{ number_format($doc['tax'], 3) }}</td>
                                    <td>{{ number_format($doc['supervision_fees'], 3) }}</td>
                                    <td>{{ number_format($doc['stamp'], 3) }}</td>
                                    <td>{{ number_format($doc['issue_fees'], 3) }}</td>
                                    <td style="font-size: 9.5px;">{{ $doc['extra_detail'] ?? '-' }}</td>
                                    <td style="font-weight: 900; color: #0284c7;">{{ number_format($doc['total'], 3) }}</td>
                                    <td style="font-size: 9.5px;">{{ $doc['user_name'] ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="13" style="padding: 15px; color: #94a3b8;">لا توجد وثائق مسجلة في هذا القسم</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <!-- Section Summary Table -->
                    <div class="summary-wrapper">
                        <table class="summary-table">
                            <thead>
                                <tr>
                                    <th>القسط الصافي</th>
                                    <th>الضريبة</th>
                                    <th>اشراف ورقابة</th>
                                    <th>الدمغة</th>
                                    <th>مصاريف الاصدار</th>
                                    <th>الاجمالي</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>{{ number_format($section['totals']['premium'], 3) }}</td>
                                    <td>{{ number_format($section['totals']['tax'], 3) }}</td>
                                    <td>{{ number_format($section['totals']['supervision_fees'], 3) }}</td>
                                    <td>{{ number_format($section['totals']['stamp'], 3) }}</td>
                                    <td>{{ number_format($section['totals']['issue_fees'], 3) }}</td>
                                    <td style="color: #15803d; font-size: 12px;">{{ number_format($section['totals']['total'], 3) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700; font-size: 14px;">
                    لا توجد وثائق مسجلة للوكيل في الفترة المحددة
                </div>
            @endforelse

            <!-- Signature Box (Left Aligned & Prominent) -->
            <div class="signature-wrapper">
                <div class="signature-box">
                    <div class="sig-header">التوقيع والختم:</div>
                    <div class="sig-body"></div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer-bar">
            <div>1/1</div>
            <div>{{ date('d/m/Y h:i A') }}</div>
        </div>
    </div>

</body>
</html>
