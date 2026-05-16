<?php
// ملف اختبار المسارات والوقت على السيرفر
echo "<h3>فحص مسارات السيرفر</h3>";
echo "<b>Current Directory:</b> " . __DIR__ . "<br>";

$controllerPath = realpath(__DIR__ . '/../app/Http/Controllers/ExcelImportController.php');
if ($controllerPath) {
    echo "<b>Controller Found at:</b> " . $controllerPath . "<br>";
    echo "<b>Last Modified on Server:</b> " . date("Y-m-d H:i:s", filemtime($controllerPath)) . "<br>";
    echo "<b>Current Server Time:</b> " . date("Y-m-d H:i:s") . "<br>";
    
    // فحص عينة من الكود للتأكد من وجود اللوج الطارئ
    $content = file_get_contents($controllerPath);
    if (strpos($content, 'CONFIRM PROCESS STARTED ON SERVER') !== false) {
        echo "<br><span style='color:green; font-weight:bold;'>✅ النسخة الجديدة موجودة فعلاً على القرص!</span>";
    } else {
        echo "<br><span style='color:red; font-weight:bold;'>❌ النسخة الموجودة على السيرفر قديمة! أرجوك ارفع الملف الصحيح.</span>";
    }
} else {
    echo "<br><span style='color:red;'>❌ لم يتم العثور على ملف الكنترولر في المسار المتوقع!</span>";
}
?>
