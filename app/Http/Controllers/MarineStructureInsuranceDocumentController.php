<?php

namespace App\Http\Controllers;

use App\Models\MarineStructureInsuranceDocument;
use App\Models\MarineStructureEngine;
use App\Models\BranchAgent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MarineStructureInsuranceDocumentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            // الحصول على المستخدم الحالي من header أو query parameter
            $userId = $request->header('X-User-Id') ?? $request->input('user_id');
            if ($userId) {
                $userId = is_numeric($userId) ? (int) $userId : null;
                $user = $userId ? \App\Models\User::find($userId) : null;
                if (!$user) {
                    return response()->json(['message' => 'غير مصرح لك بإلغاء الوثائق'], 403);
                }
                $isAdmin = $user->is_admin ?? false;
                $authDocs = is_array($user->authorized_documents)
                    ? $user->authorized_documents
                    : (is_string($user->authorized_documents) ? json_decode($user->authorized_documents, true) : []);
                if (!is_array($authDocs)) $authDocs = [];
                $hasPermission = $isAdmin || !empty($authDocs) || $user->department_id !== null || ($user->branch_agent_id ?? null) !== null;
                if (!$hasPermission) {
                    return response()->json(['message' => 'غير مصرح لك بإلغاء الوثائق'], 403);
                }
            }
            }
            $validated = $request->validate(['cancel_reason' => 'required|string|max:1000']);
            $doc = MarineStructureInsuranceDocument::findOrFail($document);
            if ($doc->is_canceled) {
                return response()->json(['message' => 'هذه الوثيقة ملغية بالفعل'], 422);
            }
            $doc->update([
                'is_canceled' => true,
                'canceled_at' => now(),
                'canceled_by' => $userId,
                'cancel_reason' => $validated['cancel_reason'],
            ]);
            return response()->json(['message' => 'تم إلغاء الوثيقة بنجاح']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'الوثيقة غير موجودة'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'سبب الإلغاء مطلوب', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error in MarineStructureInsuranceDocumentController@cancel: ' . $e->getMessage());
            return response()->json(['message' => 'حدث خطأ أثناء إلغاء الوثيقة'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($document)
    {
        try {
            $document = MarineStructureInsuranceDocument::findOrFail($document);
            $document->delete();
            return response()->json(['message' => 'تم حذف الوثيقة بنجاح']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'الوثيقة غير موجودة'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error in MarineStructureInsuranceDocumentController@destroy: ' . $e->getMessage());
            return response()->json([
                'message' => 'حدث خطأ أثناء حذف الوثيقة',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 500);
        }
    }

    /**
     * Print marine structure insurance document.
     */
    public function print($document)
    {
        try {
            $document = MarineStructureInsuranceDocument::with(['registrationAuthority.city:id,name_ar,name_en,order', 'engines', 'branchAgent'])->findOrFail($document);
            
            $mainEngine = $document->engines->where('engine_type', 'main')->first();
            $auxiliaryEngine = $document->engines->where('engine_type', 'auxiliary')->first();
            $mainEngineHorsepower = $mainEngine && $mainEngine->horsepower ? $mainEngine->horsepower : 0;
            $auxiliaryEngineHorsepower = $auxiliaryEngine && $auxiliaryEngine->horsepower ? $auxiliaryEngine->horsepower : 0;
            
            // قائمة الدول للبحث عن الاسم الإنجليزي
            $countries = [
                'مصري' => 'Egyptian', 'سوداني' => 'Sudanese', 'ليبي' => 'Libyan', 'تونسي' => 'Tunisian',
                'جزائري' => 'Algerian', 'مغربي' => 'Moroccan', 'موريتاني' => 'Mauritanian', 'صحراوي' => 'Sahrawi',
                'تشادي' => 'Chadian', 'نيجري' => 'Nigerien', 'مالي' => 'Malian', 'سنغالي' => 'Senegalese',
                'غامبي' => 'Gambian', 'غيني' => 'Guinean', 'غيني-بيساوي' => 'Bissau-Guinean', 'سيراليوني' => 'Sierra Leonean',
                'ليبيري' => 'Liberian', 'إيفواري (ساحل العاج)' => 'Ivorian', 'غاني' => 'Ghanaian', 'توغولي' => 'Togolese',
                'بنيني' => 'Beninese', 'نيجيري' => 'Nigerian', 'كاميروني' => 'Cameroonian', 'كونغولي' => 'Congolese',
                'كونغولي (جمهورية الكونغو الديمقراطية)' => 'Congolese (DRC)', 'أنغولي' => 'Angolan', 'زامبي' => 'Zambian',
                'زيمبابوي' => 'Zimbabwean', 'بوتسواني' => 'Botswanan', 'ناميبي' => 'Namibian', 'ليسوتوي' => 'Basotho',
                'إسواتيني' => 'Swazi', 'مدغشقري' => 'Malagasy', 'موريشي' => 'Mauritian', 'سيشيلي' => 'Seychellois',
                'جزر قمري' => 'Comorian', 'جيبوتي' => 'Djiboutian', 'صومالي' => 'Somali', 'إثيوبي' => 'Ethiopian',
                'إريتري' => 'Eritrean', 'جنوب سوداني' => 'South Sudanese', 'أوغندي' => 'Ugandan', 'كيني' => 'Kenyan',
                'تنزاني' => 'Tanzanian', 'رواندي' => 'Rwandan', 'بوروندي' => 'Burundian', 'ملاوي' => 'Malawian',
                'موزمبيقي' => 'Mozambican', 'سعودي' => 'Saudi', 'كويتي' => 'Kuwaiti', 'قطري' => 'Qatari',
                'بحريني' => 'Bahraini', 'إماراتي' => 'Emirati', 'عماني' => 'Omani', 'يمني' => 'Yemeni',
                'عراقي' => 'Iraqi', 'سوري' => 'Syrian', 'لبناني' => 'Lebanese', 'أردني' => 'Jordanian',
                'فلسطيني' => 'Palestinian', 'تركي' => 'Turkish', 'إيراني' => 'Iranian', 'أفغاني' => 'Afghan',
                'باكستاني' => 'Pakistani', 'هندي' => 'Indian', 'نيبالي' => 'Nepali', 'بنغلاديشي' => 'Bangladeshi',
                'سريلانكي' => 'Sri Lankan', 'بوتاني' => 'Bhutanese', 'مالديفي' => 'Maldivian', 'صيني' => 'Chinese',
                'ياباني' => 'Japanese', 'كوري جنوبي' => 'South Korean', 'كوري شمالي' => 'North Korean', 'منغولي' => 'Mongolian',
                'كازاخستاني' => 'Kazakh', 'أوزبكي' => 'Uzbek', 'تركماني' => 'Turkmen', 'طاجيكي' => 'Tajik',
                'قيرغيزي' => 'Kyrgyz', 'ميانماري' => 'Burmese', 'تايلاندي' => 'Thai', 'كامبودي' => 'Cambodian',
                'فيتنامي' => 'Vietnamese', 'لاوسي' => 'Laotian', 'ماليزاي' => 'Malaysian', 'سنغافوري' => 'Singaporean',
                'إندونيسي' => 'Indonesian', 'فلبيني' => 'Filipino', 'تيموري' => 'Timorese', 'جورجي' => 'Georgian',
                'أرميني' => 'Armenian', 'أذربيجاني' => 'Azerbaijani', 'قبرصي' => 'Cypriot', 'بريطاني' => 'British',
                'إنجليزي' => 'English', 'إسكتلندي' => 'Scottish', 'ويلزي' => 'Welsh', 'إيرلندي' => 'Irish',
                'فرنسي' => 'French', 'ألماني' => 'German', 'إيطالي' => 'Italian', 'إسباني' => 'Spanish',
                'برتغالي' => 'Portuguese', 'هولندي' => 'Dutch', 'بلجيكي' => 'Belgian', 'لوكسمبورغي' => 'Luxembourger',
                'نمساوي' => 'Austrian', 'سويسري' => 'Swiss', 'دنماركي' => 'Danish', 'سويدي' => 'Swedish',
                'نرويجي' => 'Norwegian', 'فنلندي' => 'Finnish', 'آيسلندي' => 'Icelandic', 'بولندي' => 'Polish',
                'تشيكي' => 'Czech', 'سلوفاكي' => 'Slovak', 'هنغاري' => 'Hungarian', 'روماني' => 'Romanian',
                'بلغاري' => 'Bulgarian', 'صربي' => 'Serbian', 'كرواتي' => 'Croatian', 'بوسني' => 'Bosnian',
                'سلوفيني' => 'Slovenian', 'مقدوني' => 'Macedonian', 'ألباني' => 'Albanian', 'يوناني' => 'Greek',
                'مالطي' => 'Maltese', 'ليتواني' => 'Lithuanian', 'لاتفي' => 'Latvian', 'إستوني' => 'Estonian',
                'أوكراني' => 'Ukrainian', 'روسي' => 'Russian', 'بيلاروسي' => 'Belarusian', 'مولدوفي' => 'Moldovan',
                'أمريكي' => 'American', 'كندي' => 'Canadian', 'مكسيكي' => 'Mexican', 'غواتيمالي' => 'Guatemalan',
                'هندوراسي' => 'Honduran', 'سلفادوري' => 'Salvadoran', 'نيكاراغوي' => 'Nicaraguan', 'كوستاريكي' => 'Costa Rican',
                'بانامي' => 'Panamanian', 'كوبي' => 'Cuban', 'دومينيكاني' => 'Dominican', 'هايتي' => 'Haitian',
                'جامايكي' => 'Jamaican', 'باهامي' => 'Bahamian', 'بربادوسي' => 'Barbadian', 'ترينيدادي' => 'Trinidadian',
                'أنتيغوي' => 'Antiguan', 'سانت لوسي' => 'Saint Lucian', 'غرينادي' => 'Grenadian', 'برازيلي' => 'Brazilian',
                'أرجنتيني' => 'Argentine', 'أوروغواياني' => 'Uruguayan', 'باراغوايي' => 'Paraguayan', 'تشيلي' => 'Chilean',
                'بوليفي' => 'Bolivian', 'بيروفي' => 'Peruvian', 'إكوادوري' => 'Ecuadorian', 'سورينامي' => 'Surinamese',
                'غوياني' => 'Guyanese', 'أسترالي' => 'Australian', 'نيوزيلندي' => 'New Zealander', 'بابواني' => 'Papuan',
                'فيجياني' => 'Fijian', 'سامواني' => 'Samoan', 'تونغاني' => 'Tongan', 'فانواتي' => 'Vanuatuan',
                'كيريباتي' => 'Kiribati', 'ميكرونيزي' => 'Micronesian', 'مارشالي' => 'Marshallese', 'ناورووي' => 'Nauruan',
                'بالاوي' => 'Palauan', 'توفالي' => 'Tuvaluan',
            ];
            
            // تحضير بيانات الوكالة
            $agencyData = [
                'agency_name' => 'المدار الليبي للتأمين',
                'code' => 'ML0001',
                'agent_name' => 'محمد علي',
            ];
            
            if ($document->branchAgent) {
                $agencyData['agency_name'] = $document->branchAgent->agency_name ?? 'المدار الليبي للتأمين';
                $agencyData['code'] = $document->branchAgent->code ?? 'ML0001';
                $agencyData['agent_name'] = $document->branchAgent->agent_name ?? 'محمد علي';
            }
            
            $getCountryDisplay = function($arabicName) use ($countries) {
                return isset($countries[$arabicName]) ? $arabicName . ' ' . $countries[$arabicName] : $arabicName;
            };
            
            $formatRegistrationAuthority = function($document) {
                // استخدام registrationAuthority مباشرة
                $registrationAuthority = $document->registrationAuthority;
                
                // إعادة تحميل العلاقة city إذا لم تكن محملة
                if ($registrationAuthority && !$registrationAuthority->relationLoaded('city')) {
                    $registrationAuthority->load('city:id,name_ar,name_en,order');
                }
                
                $portValue = trim($document->port ?? '');
                $hasPort = !empty($portValue);
                
                // التحقق من وجود city في registrationAuthority
                if ($registrationAuthority && $registrationAuthority->city) {
                    $city = $registrationAuthority->city;
                    // التأكد من تحميل name_ar و name_en
                    if (!isset($city->name_ar) || !isset($city->name_en)) {
                        $city = \App\Models\City::select('id', 'name_ar', 'name_en', 'order')->find($city->id);
                    }
                    if ($city && isset($city->name_ar)) {
                        $result = $city->name_ar;
                        if ($city->name_en) {
                            $result .= ' ' . $city->name_en;
                        }
                        return $result;
                    }
                } elseif ($hasPort) {
                    return $portValue;
                } elseif ($registrationAuthority) {
                    return $registrationAuthority->plate_number ?? '-';
                }
                
                return '-';
            };
            
            $formatPlateNumber = function($document) {
                $registrationAuthority = $document->registrationAuthority;
                if ($registrationAuthority && $registrationAuthority->city && isset($registrationAuthority->city->order)) {
                    return $registrationAuthority->city->order . '-' . ($document->plate_number ?? $registrationAuthority->plate_number);
                }
                return $document->plate_number ?? ($registrationAuthority ? $registrationAuthority->plate_number : '-');
            };
            
            // تحضير البيانات للطباعة
            $printData = [
                'issue_date' => Carbon::parse($document->issue_date)->format('d/m/Y h:i A'),
                'insurance_number' => $document->insurance_number,
                'start_date' => Carbon::parse($document->start_date)->format('d/m/Y'),
                'end_date' => Carbon::parse($document->end_date)->format('d/m/Y'),
                'duration' => $document->duration === 'سنة (365 يوم)' ? '365 يوم' : ($document->duration === 'سنتين (730 يوم)' ? '730 يوم' : $document->duration),
                'insured_name' => $document->insured_name ?? '-',
                'phone' => $document->phone ?? '-',
                'license_number' => $document->license_number ?? '-',
                'vessel_name' => $document->vessel_name ?? '-',
                'plate_number' => $formatPlateNumber($document),
                'registration_authority' => $formatRegistrationAuthority($document),
                'hull_number' => $document->hull_number ?? '-',
                'passenger_count' => $document->passenger_count ?? 0,
                'load_capacity' => $document->load_capacity ?? 0,
                'license_purpose' => $document->license_purpose ?? '-',
                'manufacturing_country' => $document->manufacturing_country ? $getCountryDisplay($document->manufacturing_country) : '-',
                'color' => $document->color ?? '-',
                'port' => $document->port ?? '-',
                'manufacturing_material' => $document->manufacturing_material ?? '-',
                'manufacturing_year' => $document->manufacturing_year ?? '-',
                'size' => ($document->length ?? 0) . ' × ' . ($document->width ?? 0) . ' × ' . ($document->depth ?? 0),
                'fuel_tank_capacity' => $document->fuel_tank_capacity ?? 0,
                'main_engine_horsepower' => $mainEngineHorsepower,
                'auxiliary_engine_horsepower' => $auxiliaryEngineHorsepower,
                'engine_horsepower_display' => $mainEngineHorsepower . ' / ' . $auxiliaryEngineHorsepower,
                'premium' => number_format($document->premium, 3, '.', ''),
                'tax' => number_format($document->tax, 3, '.', ''),
                'stamp' => number_format($document->stamp, 3, '.', ''),
                'issue_fees' => number_format($document->issue_fees, 3, '.', ''),
                'supervision_fees' => number_format($document->supervision_fees, 3, '.', ''),
                'total' => number_format($document->total, 3, '.', ''),
                'total_in_words' => $this->numberToArabicWords($document->total),
                'agency_name' => $agencyData['agency_name'],
                'agency_code' => $agencyData['code'],
                'agent_name' => $agencyData['agent_name'],
                'prepared_at' => Carbon::now()->format('d/m/y H:i:s'),
                'qr_data' => [
                    'insurance_number' => $document->insurance_number,
                    'issue_date' => Carbon::parse($document->issue_date)->format('Y-m-d'),
                    'insured_name' => $document->insured_name ?? '',
                    'total' => $document->total
                ]
            ];
            
            return view('marine-structure-insurance-documents.print', compact('document', 'printData'));
        } catch (\Exception $e) {
            Log::error('Error in MarineStructureInsuranceDocumentController@print: ' . $e->getMessage());
            abort(404, 'الوثيقة غير موجودة');
        }
    }
    
    private function numberToArabicWords($number)
    {
        $ones = ['', 'واحد', 'اثنان', 'ثلاثة', 'أربعة', 'خمسة', 'ستة', 'سبعة', 'ثمانية', 'تسعة'];
        $teens = ['عشرة', 'أحد عشر', 'اثنا عشر', 'ثلاثة عشر', 'أربعة عشر', 'خمسة عشر', 'ستة عشر', 'سبعة عشر', 'ثمانية عشر', 'تسعة عشر'];
        $tens = ['', '', 'عشرون', 'ثلاثون', 'أربعون', 'خمسون', 'ستون', 'سبعون', 'ثمانون', 'تسعون'];
        $hundreds = ['', 'مائة', 'مائتان', 'ثلاثمائة', 'أربعمائة', 'خمسمائة', 'ستمائة', 'سبعمائة', 'ثمانمائة', 'تسعمائة'];
        
        // فصل الجزء الصحيح والجزء العشري
        $parts = explode('.', (string)$number);
        $integerPart = (int)($parts[0] ?? 0);
        $decimalPart = isset($parts[1]) ? (int)($parts[1]) : 0;
        
        // تحويل الجزء الصحيح
        $words = '';
        
        if ($integerPart == 0 && $decimalPart == 0) {
            return 'صفر دينار';
        }
        
        if ($integerPart > 0) {
            $num = $integerPart;
            
            // الآلاف
            if ($num >= 1000) {
                $thousands = (int)($num / 1000);
                if ($thousands == 1) {
                    $words .= 'ألف ';
                } elseif ($thousands == 2) {
                    $words .= 'ألفان ';
                } elseif ($thousands >= 3 && $thousands <= 10) {
                    $words .= $ones[$thousands] . ' آلاف ';
                } else {
                    $words .= number_format($thousands) . ' ألف ';
                }
                $num = $num % 1000;
            }
            
            // المئات
            if ($num >= 100) {
                $hundred = (int)($num / 100);
                $words .= $hundreds[$hundred] . ' ';
                $num = $num % 100;
            }
            
            // العشرات والآحاد
            if ($num >= 20) {
                $ten = (int)($num / 10);
                $one = $num % 10;
                if ($one > 0) {
                    $words .= $ones[$one] . ' و' . $tens[$ten];
                } else {
                    $words .= $tens[$ten];
                }
            } elseif ($num >= 10) {
                $words .= $teens[$num - 10];
            } elseif ($num > 0) {
                $words .= $ones[$num];
            }
            
            $words .= ' دينار';
        }
        
        // تحويل الجزء العشري
        if ($decimalPart > 0) {
            if ($integerPart > 0) {
                $words .= ' و';
            }
            $words .= $decimalPart . ' درهم';
        }
        
        return trim($words);
    }
}
