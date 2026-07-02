<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class AreaSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $govIds = DB::table('governorates')->pluck('id', 'slug');

        $areas = [
            // =================================================================
            // دمشق (Damascus) — أحياء المدينة
            // =================================================================
            ['governorate_id' => $govIds['damascus'], 'name_ar' => 'المالكي', 'name_en' => 'Malki', 'slug' => 'malki', 'lat' => 33.5178, 'lng' => 36.2703],
            ['governorate_id' => $govIds['damascus'], 'name_ar' => 'المزة', 'name_en' => 'Mazzeh', 'slug' => 'mazzeh', 'lat' => 33.4960, 'lng' => 36.2480],
            ['governorate_id' => $govIds['damascus'], 'name_ar' => 'أبو رمانة', 'name_en' => 'Abu Rummaneh', 'slug' => 'abu-rummaneh', 'lat' => 33.5220, 'lng' => 36.2810],
            ['governorate_id' => $govIds['damascus'], 'name_ar' => 'كفرسوسة', 'name_en' => 'Kafr Sousa', 'slug' => 'kafr-sousa', 'lat' => 33.4900, 'lng' => 36.2600],
            ['governorate_id' => $govIds['damascus'], 'name_ar' => 'المهاجرين', 'name_en' => 'Muhajreen', 'slug' => 'muhajreen', 'lat' => 33.5250, 'lng' => 36.2900],
            ['governorate_id' => $govIds['damascus'], 'name_ar' => 'ركن الدين', 'name_en' => 'Rukn al-Din', 'slug' => 'rukn-al-din', 'lat' => 33.5300, 'lng' => 36.2950],
            ['governorate_id' => $govIds['damascus'], 'name_ar' => 'الصالحية', 'name_en' => 'Salhiyah', 'slug' => 'salhiyah', 'lat' => 33.5200, 'lng' => 36.3100],
            ['governorate_id' => $govIds['damascus'], 'name_ar' => 'القصاع', 'name_en' => 'Qassaa', 'slug' => 'qassaa', 'lat' => 33.5150, 'lng' => 36.3200],
            ['governorate_id' => $govIds['damascus'], 'name_ar' => 'الشعلان', 'name_en' => 'Shaalan', 'slug' => 'shaalan', 'lat' => 33.5100, 'lng' => 36.2750],
            ['governorate_id' => $govIds['damascus'], 'name_ar' => 'البرامكة', 'name_en' => 'Baramkeh', 'slug' => 'baramkeh', 'lat' => 33.5000, 'lng' => 36.2800],
            ['governorate_id' => $govIds['damascus'], 'name_ar' => 'الميدان', 'name_en' => 'Midan', 'slug' => 'midan', 'lat' => 33.4850, 'lng' => 36.3000],
            ['governorate_id' => $govIds['damascus'], 'name_ar' => 'باب توما', 'name_en' => 'Bab Touma', 'slug' => 'bab-touma', 'lat' => 33.5180, 'lng' => 36.3150],
            ['governorate_id' => $govIds['damascus'], 'name_ar' => 'باب شرقي', 'name_en' => 'Bab Sharqi', 'slug' => 'bab-sharqi', 'lat' => 33.5120, 'lng' => 36.3300],
            ['governorate_id' => $govIds['damascus'], 'name_ar' => 'العدوي', 'name_en' => 'Adawi', 'slug' => 'adawi', 'lat' => 33.5250, 'lng' => 36.2850],
            ['governorate_id' => $govIds['damascus'], 'name_ar' => 'دمر', 'name_en' => 'Dumar', 'slug' => 'dumar', 'lat' => 33.5020, 'lng' => 36.2200],
            ['governorate_id' => $govIds['damascus'], 'name_ar' => 'الورود', 'name_en' => 'Wurud', 'slug' => 'wurud', 'lat' => 33.4950, 'lng' => 36.2350],
            ['governorate_id' => $govIds['damascus'], 'name_ar' => 'القدم', 'name_en' => 'Qadam', 'slug' => 'qadam', 'lat' => 33.4700, 'lng' => 36.3100],
            ['governorate_id' => $govIds['damascus'], 'name_ar' => 'الزاهرة', 'name_en' => 'Zahira', 'slug' => 'zahira', 'lat' => 33.5450, 'lng' => 36.3000],
            ['governorate_id' => $govIds['damascus'], 'name_ar' => 'جوبر', 'name_en' => 'Jobar', 'slug' => 'jobar', 'lat' => 33.5300, 'lng' => 36.3400],
            ['governorate_id' => $govIds['damascus'], 'name_ar' => 'بزورية', 'name_en' => 'Bzouriya', 'slug' => 'bzouriya', 'lat' => 33.5080, 'lng' => 36.3050],

            // =================================================================
            // ريف دمشق (Rural Damascus)
            // =================================================================
            // Districts
            ['governorate_id' => $govIds['rural-damascus'], 'name_ar' => 'مركز ريف دمشق', 'name_en' => 'Markaz Rif Dimashq', 'slug' => 'markaz-rif-dimashq', 'lat' => 33.5000, 'lng' => 36.3500],
            ['governorate_id' => $govIds['rural-damascus'], 'name_ar' => 'دوما', 'name_en' => 'Douma', 'slug' => 'douma', 'lat' => 33.5740, 'lng' => 36.4020],
            ['governorate_id' => $govIds['rural-damascus'], 'name_ar' => 'الزبداني', 'name_en' => 'Al-Zabadani', 'slug' => 'al-zabadani', 'lat' => 33.7240, 'lng' => 36.1000],
            ['governorate_id' => $govIds['rural-damascus'], 'name_ar' => 'قدسيا', 'name_en' => 'Qudsaya', 'slug' => 'qudsaya', 'lat' => 33.5360, 'lng' => 36.2170],
            ['governorate_id' => $govIds['rural-damascus'], 'name_ar' => 'قطنا', 'name_en' => 'Qatana', 'slug' => 'qatana', 'lat' => 33.4380, 'lng' => 36.0810],
            ['governorate_id' => $govIds['rural-damascus'], 'name_ar' => 'النبك', 'name_en' => 'Al-Nabk', 'slug' => 'al-nabk', 'lat' => 34.0260, 'lng' => 36.7350],
            ['governorate_id' => $govIds['rural-damascus'], 'name_ar' => 'يبرود', 'name_en' => 'Yabrud', 'slug' => 'yabrud', 'lat' => 33.9710, 'lng' => 36.6670],
            ['governorate_id' => $govIds['rural-damascus'], 'name_ar' => 'التل', 'name_en' => 'Al-Tall', 'slug' => 'al-tall', 'lat' => 33.6100, 'lng' => 36.3120],
            ['governorate_id' => $govIds['rural-damascus'], 'name_ar' => 'داريا', 'name_en' => 'Darayya', 'slug' => 'darayya', 'lat' => 33.4570, 'lng' => 36.2420],
            ['governorate_id' => $govIds['rural-damascus'], 'name_ar' => 'القطيفة', 'name_en' => 'Al-Qutayfah', 'slug' => 'al-qutayfah', 'lat' => 33.7380, 'lng' => 36.6040],
            // Major cities/towns
            ['governorate_id' => $govIds['rural-damascus'], 'name_ar' => 'جرمانا', 'name_en' => 'Jaramana', 'slug' => 'jaramana', 'lat' => 33.4900, 'lng' => 36.3550],
            ['governorate_id' => $govIds['rural-damascus'], 'name_ar' => 'حرستا', 'name_en' => 'Harasta', 'slug' => 'harasta', 'lat' => 33.5580, 'lng' => 36.3680],
            ['governorate_id' => $govIds['rural-damascus'], 'name_ar' => 'عربين', 'name_en' => 'Arbin', 'slug' => 'arbin', 'lat' => 33.5370, 'lng' => 36.3710],
            ['governorate_id' => $govIds['rural-damascus'], 'name_ar' => 'زملكا', 'name_en' => 'Zamalka', 'slug' => 'zamalka', 'lat' => 33.5230, 'lng' => 36.3800],
            ['governorate_id' => $govIds['rural-damascus'], 'name_ar' => 'السيدة زينب', 'name_en' => 'Sayyidah Zaynab', 'slug' => 'sayyidah-zaynab', 'lat' => 33.4490, 'lng' => 36.3400],
            ['governorate_id' => $govIds['rural-damascus'], 'name_ar' => 'ببيلا', 'name_en' => 'Babbila', 'slug' => 'babbila', 'lat' => 33.4740, 'lng' => 36.3340],
            ['governorate_id' => $govIds['rural-damascus'], 'name_ar' => 'صحنايا', 'name_en' => 'Sahnaya', 'slug' => 'sahnaya', 'lat' => 33.4280, 'lng' => 36.2300],
            ['governorate_id' => $govIds['rural-damascus'], 'name_ar' => 'عدرا', 'name_en' => 'Adra', 'slug' => 'adra', 'lat' => 33.6100, 'lng' => 36.5200],
            ['governorate_id' => $govIds['rural-damascus'], 'name_ar' => 'جديدة عرطوز', 'name_en' => 'Jdeidat Artouz', 'slug' => 'jdeidat-artouz', 'lat' => 33.4180, 'lng' => 36.2000],
            ['governorate_id' => $govIds['rural-damascus'], 'name_ar' => 'الكسوة', 'name_en' => 'Al-Kiswah', 'slug' => 'al-kiswah', 'lat' => 33.3580, 'lng' => 36.2380],
            ['governorate_id' => $govIds['rural-damascus'], 'name_ar' => 'المعضمية', 'name_en' => 'Al-Muadamiyah', 'slug' => 'al-muadamiyah', 'lat' => 33.4620, 'lng' => 36.2180],
            ['governorate_id' => $govIds['rural-damascus'], 'name_ar' => 'سرغايا', 'name_en' => 'Sargaya', 'slug' => 'sargaya', 'lat' => 33.8000, 'lng' => 36.2000],
            ['governorate_id' => $govIds['rural-damascus'], 'name_ar' => 'معلولا', 'name_en' => 'Maaloula', 'slug' => 'maaloula', 'lat' => 33.8430, 'lng' => 36.5470],
            ['governorate_id' => $govIds['rural-damascus'], 'name_ar' => 'حوش عرب', 'name_en' => 'Hosh Arab', 'slug' => 'hosh-arab', 'lat' => 33.4900, 'lng' => 36.3900],

            // =================================================================
            // حلب (Aleppo) — 10 مناطق
            // =================================================================
            ['governorate_id' => $govIds['aleppo'], 'name_ar' => 'جبل سمعان', 'name_en' => 'Jabal Samman', 'slug' => 'jabal-samman', 'lat' => 36.2000, 'lng' => 37.1500],
            ['governorate_id' => $govIds['aleppo'], 'name_ar' => 'عفرين', 'name_en' => 'Afrin', 'slug' => 'afrin', 'lat' => 36.5110, 'lng' => 36.8680],
            ['governorate_id' => $govIds['aleppo'], 'name_ar' => 'الباب', 'name_en' => 'Al-Bab', 'slug' => 'al-bab', 'lat' => 36.3730, 'lng' => 37.5170],
            ['governorate_id' => $govIds['aleppo'], 'name_ar' => 'دير حافر', 'name_en' => 'Dayr Hafir', 'slug' => 'dayr-hafir', 'lat' => 36.1560, 'lng' => 37.7070],
            ['governorate_id' => $govIds['aleppo'], 'name_ar' => 'منبج', 'name_en' => 'Manbij', 'slug' => 'manbij', 'lat' => 36.5300, 'lng' => 37.9530],
            ['governorate_id' => $govIds['aleppo'], 'name_ar' => 'جرابلس', 'name_en' => 'Jarabulus', 'slug' => 'jarabulus', 'lat' => 36.8180, 'lng' => 38.0100],
            ['governorate_id' => $govIds['aleppo'], 'name_ar' => 'السفيرة', 'name_en' => 'Al-Safira', 'slug' => 'al-safira', 'lat' => 36.0780, 'lng' => 37.3700],
            ['governorate_id' => $govIds['aleppo'], 'name_ar' => 'أعزاز', 'name_en' => 'Azaz', 'slug' => 'azaz', 'lat' => 36.5850, 'lng' => 37.0440],
            ['governorate_id' => $govIds['aleppo'], 'name_ar' => 'الأتارب', 'name_en' => 'Atarib', 'slug' => 'atarib', 'lat' => 36.1400, 'lng' => 36.8300],
            ['governorate_id' => $govIds['aleppo'], 'name_ar' => 'عين العرب', 'name_en' => 'Ayn al-Arab (Kobani)', 'slug' => 'ayn-al-arab', 'lat' => 36.8900, 'lng' => 38.3600],
            // Major cities/towns
            ['governorate_id' => $govIds['aleppo'], 'name_ar' => 'الشهباء', 'name_en' => 'Shahba', 'slug' => 'shahba', 'lat' => 36.2100, 'lng' => 37.1500],
            ['governorate_id' => $govIds['aleppo'], 'name_ar' => 'الفرقان', 'name_en' => 'Furqan', 'slug' => 'furqan', 'lat' => 36.1950, 'lng' => 37.1600],
            ['governorate_id' => $govIds['aleppo'], 'name_ar' => 'بستان القصر', 'name_en' => 'Bustan al-Qasr', 'slug' => 'bustan-al-qasr', 'lat' => 36.2050, 'lng' => 37.1400],
            ['governorate_id' => $govIds['aleppo'], 'name_ar' => 'الصاخور', 'name_en' => 'Al-Sakhour', 'slug' => 'al-sakhour', 'lat' => 36.2200, 'lng' => 37.1700],
            ['governorate_id' => $govIds['aleppo'], 'name_ar' => 'المعري', 'name_en' => 'Al-Maari', 'slug' => 'al-maari', 'lat' => 36.1800, 'lng' => 37.1300],
            ['governorate_id' => $govIds['aleppo'], 'name_ar' => 'العزيزية', 'name_en' => 'Al-Aziziyah', 'slug' => 'al-aziziyah', 'lat' => 36.2150, 'lng' => 37.1600],
            ['governorate_id' => $govIds['aleppo'], 'name_ar' => 'الحمدانية', 'name_en' => 'Al-Hamdaniyah', 'slug' => 'al-hamdaniyah', 'lat' => 36.1900, 'lng' => 37.1200],
            ['governorate_id' => $govIds['aleppo'], 'name_ar' => 'نبل', 'name_en' => 'Nubl', 'slug' => 'nubl', 'lat' => 36.3700, 'lng' => 36.9900],
            ['governorate_id' => $govIds['aleppo'], 'name_ar' => 'الزهراء', 'name_en' => 'Al-Zahraa', 'slug' => 'al-zahraa', 'lat' => 36.3650, 'lng' => 36.9800],
            ['governorate_id' => $govIds['aleppo'], 'name_ar' => 'تل رفعت', 'name_en' => 'Tell Rifaat', 'slug' => 'tell-rifaat', 'lat' => 36.4700, 'lng' => 37.0900],

            // =================================================================
            // حمص (Homs) — 6 مناطق
            // =================================================================
            ['governorate_id' => $govIds['homs'], 'name_ar' => 'حمص (المركز)', 'name_en' => 'Homs (Center)', 'slug' => 'homs-center', 'lat' => 34.7320, 'lng' => 36.7130],
            ['governorate_id' => $govIds['homs'], 'name_ar' => 'الرستن', 'name_en' => 'Al-Rastan', 'slug' => 'al-rastan', 'lat' => 34.9170, 'lng' => 36.7320],
            ['governorate_id' => $govIds['homs'], 'name_ar' => 'تدمر', 'name_en' => 'Palmyra (Tadmur)', 'slug' => 'tadmur', 'lat' => 34.5600, 'lng' => 38.2670],
            ['governorate_id' => $govIds['homs'], 'name_ar' => 'المخرم', 'name_en' => 'Al-Mukharram', 'slug' => 'al-mukharram', 'lat' => 34.8300, 'lng' => 37.0900],
            ['governorate_id' => $govIds['homs'], 'name_ar' => 'القصير', 'name_en' => 'Al-Qusayr', 'slug' => 'al-qusayr', 'lat' => 34.5080, 'lng' => 36.5810],
            ['governorate_id' => $govIds['homs'], 'name_ar' => 'تلكلخ', 'name_en' => 'Talkalakh', 'slug' => 'talkalakh', 'lat' => 34.6670, 'lng' => 36.2600],
            // Major towns
            ['governorate_id' => $govIds['homs'], 'name_ar' => 'الوعر', 'name_en' => 'Al-Waer', 'slug' => 'al-waer', 'lat' => 34.7500, 'lng' => 36.6900],
            ['governorate_id' => $govIds['homs'], 'name_ar' => 'صدد', 'name_en' => 'Sadad', 'slug' => 'sadad', 'lat' => 34.3100, 'lng' => 36.9230],
            ['governorate_id' => $govIds['homs'], 'name_ar' => 'فيروزة', 'name_en' => 'Fairouzeh', 'slug' => 'fairouzeh', 'lat' => 34.6900, 'lng' => 36.6800],
            ['governorate_id' => $govIds['homs'], 'name_ar' => 'مشتى الحلو', 'name_en' => 'Mashta al-Helu', 'slug' => 'mashta-al-helu', 'lat' => 34.7800, 'lng' => 36.1800],
            ['governorate_id' => $govIds['homs'], 'name_ar' => 'حسياء', 'name_en' => 'Hasya', 'slug' => 'hasya', 'lat' => 34.2000, 'lng' => 36.7500],

            // =================================================================
            // حماة (Hama) — 5 مناطق
            // =================================================================
            ['governorate_id' => $govIds['hama'], 'name_ar' => 'حماة (المركز)', 'name_en' => 'Hama (Center)', 'slug' => 'hama-center', 'lat' => 35.1318, 'lng' => 36.7580],
            ['governorate_id' => $govIds['hama'], 'name_ar' => 'السلمية', 'name_en' => 'Al-Salamiyah', 'slug' => 'al-salamiyah', 'lat' => 35.0110, 'lng' => 37.0540],
            ['governorate_id' => $govIds['hama'], 'name_ar' => 'مصياف', 'name_en' => 'Masyaf', 'slug' => 'masyaf', 'lat' => 35.0640, 'lng' => 36.3410],
            ['governorate_id' => $govIds['hama'], 'name_ar' => 'الغاب', 'name_en' => 'Al-Ghab', 'slug' => 'al-ghab', 'lat' => 35.4000, 'lng' => 36.3000],
            ['governorate_id' => $govIds['hama'], 'name_ar' => 'محردة', 'name_en' => 'Mhardeh', 'slug' => 'mhardeh', 'lat' => 35.2460, 'lng' => 36.5850],
            // Major towns
            ['governorate_id' => $govIds['hama'], 'name_ar' => 'السقيلبية', 'name_en' => 'Al-Suqaylabiyah', 'slug' => 'al-suqaylabiyah', 'lat' => 35.3700, 'lng' => 36.3800],
            ['governorate_id' => $govIds['hama'], 'name_ar' => 'صوران', 'name_en' => 'Suran', 'slug' => 'suran', 'lat' => 35.3000, 'lng' => 36.7500],
            ['governorate_id' => $govIds['hama'], 'name_ar' => 'طيبة الإمام', 'name_en' => 'Taybat al-Imam', 'slug' => 'taybat-al-imam', 'lat' => 35.2650, 'lng' => 36.7100],
            ['governorate_id' => $govIds['hama'], 'name_ar' => 'كفر زيتا', 'name_en' => 'Kafr Zita', 'slug' => 'kafr-zita', 'lat' => 35.3800, 'lng' => 36.6000],
            ['governorate_id' => $govIds['hama'], 'name_ar' => 'قلعة المضيق', 'name_en' => 'Qalaat al-Madiq', 'slug' => 'qalaat-al-madiq', 'lat' => 35.4100, 'lng' => 36.3900],

            // =================================================================
            // اللاذقية (Latakia) — 4 مناطق
            // =================================================================
            ['governorate_id' => $govIds['latakia'], 'name_ar' => 'اللاذقية (المركز)', 'name_en' => 'Latakia (Center)', 'slug' => 'latakia-center', 'lat' => 35.5317, 'lng' => 35.7913],
            ['governorate_id' => $govIds['latakia'], 'name_ar' => 'جبلة', 'name_en' => 'Jableh', 'slug' => 'jableh', 'lat' => 35.3620, 'lng' => 35.9210],
            ['governorate_id' => $govIds['latakia'], 'name_ar' => 'الحفة', 'name_en' => 'Al-Haffah', 'slug' => 'al-haffah', 'lat' => 35.5900, 'lng' => 36.0300],
            ['governorate_id' => $govIds['latakia'], 'name_ar' => 'القرداحة', 'name_en' => 'Qardaha', 'slug' => 'qardaha', 'lat' => 35.4500, 'lng' => 36.0600],
            // Major towns
            ['governorate_id' => $govIds['latakia'], 'name_ar' => 'الشاطئ الأزرق', 'name_en' => 'Blue Beach', 'slug' => 'blue-beach', 'lat' => 35.5400, 'lng' => 35.7700],
            ['governorate_id' => $govIds['latakia'], 'name_ar' => 'عين البيضا', 'name_en' => 'Ayn al-Bayda', 'slug' => 'ayn-al-bayda', 'lat' => 35.6600, 'lng' => 35.8200],
            ['governorate_id' => $govIds['latakia'], 'name_ar' => 'سلنفة', 'name_en' => 'Slinfah', 'slug' => 'slinfah', 'lat' => 35.6000, 'lng' => 36.1800],
            ['governorate_id' => $govIds['latakia'], 'name_ar' => 'كساب', 'name_en' => 'Kasab', 'slug' => 'kasab', 'lat' => 35.9100, 'lng' => 35.9800],
            ['governorate_id' => $govIds['latakia'], 'name_ar' => 'ربيعة', 'name_en' => 'Rabia', 'slug' => 'rabia', 'lat' => 35.7700, 'lng' => 35.9900],

            // =================================================================
            // طرطوس (Tartus) — 5 مناطق
            // =================================================================
            ['governorate_id' => $govIds['tartus'], 'name_ar' => 'طرطوس (المركز)', 'name_en' => 'Tartus (Center)', 'slug' => 'tartus-center', 'lat' => 34.8887, 'lng' => 35.8865],
            ['governorate_id' => $govIds['tartus'], 'name_ar' => 'بانياس', 'name_en' => 'Baniyas', 'slug' => 'baniyas', 'lat' => 35.1820, 'lng' => 35.9480],
            ['governorate_id' => $govIds['tartus'], 'name_ar' => 'صافيتا', 'name_en' => 'Safita', 'slug' => 'safita', 'lat' => 34.8210, 'lng' => 36.1180],
            ['governorate_id' => $govIds['tartus'], 'name_ar' => 'دريكيش', 'name_en' => 'Dreikish', 'slug' => 'dreikish', 'lat' => 34.9000, 'lng' => 36.1400],
            ['governorate_id' => $govIds['tartus'], 'name_ar' => 'الشيخ بدر', 'name_en' => 'Al-Shaykh Badr', 'slug' => 'al-shaykh-badr', 'lat' => 34.9800, 'lng' => 36.0700],
            // Major towns
            ['governorate_id' => $govIds['tartus'], 'name_ar' => 'الكورنيش', 'name_en' => 'Corniche', 'slug' => 'corniche', 'lat' => 34.8900, 'lng' => 35.8800],
            ['governorate_id' => $govIds['tartus'], 'name_ar' => 'القدموس', 'name_en' => 'Al-Qadmus', 'slug' => 'al-qadmus', 'lat' => 35.0500, 'lng' => 36.1600],
            ['governorate_id' => $govIds['tartus'], 'name_ar' => 'مشتى الحلو', 'name_en' => 'Mashta al-Helu', 'slug' => 'mashta-al-helu-tartus', 'lat' => 34.7800, 'lng' => 36.1800],
            ['governorate_id' => $govIds['tartus'], 'name_ar' => 'الروضة', 'name_en' => 'Al-Rawda', 'slug' => 'al-rawda-tartus', 'lat' => 34.9200, 'lng' => 35.9000],

            // =================================================================
            // إدلب (Idlib) — 5 مناطق
            // =================================================================
            ['governorate_id' => $govIds['idlib'], 'name_ar' => 'إدلب (المركز)', 'name_en' => 'Idlib (Center)', 'slug' => 'idlib-center', 'lat' => 35.9314, 'lng' => 36.6333],
            ['governorate_id' => $govIds['idlib'], 'name_ar' => 'معرة النعمان', 'name_en' => 'Maarat al-Numan', 'slug' => 'maarat-al-numan', 'lat' => 35.6500, 'lng' => 36.6800],
            ['governorate_id' => $govIds['idlib'], 'name_ar' => 'جسر الشغور', 'name_en' => 'Jisr al-Shughur', 'slug' => 'jisr-al-shughur', 'lat' => 35.8100, 'lng' => 36.3200],
            ['governorate_id' => $govIds['idlib'], 'name_ar' => 'حارم', 'name_en' => 'Harem', 'slug' => 'harem', 'lat' => 36.2100, 'lng' => 36.5200],
            ['governorate_id' => $govIds['idlib'], 'name_ar' => 'أريحا', 'name_en' => 'Ariha', 'slug' => 'ariha', 'lat' => 35.8200, 'lng' => 36.6100],
            // Major towns
            ['governorate_id' => $govIds['idlib'], 'name_ar' => 'سرمين', 'name_en' => 'Sarmin', 'slug' => 'sarmin', 'lat' => 35.9000, 'lng' => 36.7300],
            ['governorate_id' => $govIds['idlib'], 'name_ar' => 'بينش', 'name_en' => 'Binnish', 'slug' => 'binnish', 'lat' => 35.9500, 'lng' => 36.7100],
            ['governorate_id' => $govIds['idlib'], 'name_ar' => 'كفر تخاريم', 'name_en' => 'Kafr Takharim', 'slug' => 'kafr-takharim', 'lat' => 36.1200, 'lng' => 36.5200],
            ['governorate_id' => $govIds['idlib'], 'name_ar' => 'سلفكين', 'name_en' => 'Salqin', 'slug' => 'salqin', 'lat' => 36.1400, 'lng' => 36.4500],
            ['governorate_id' => $govIds['idlib'], 'name_ar' => 'الدانا', 'name_en' => 'Al-Dana', 'slug' => 'al-dana', 'lat' => 36.2200, 'lng' => 36.7700],
            ['governorate_id' => $govIds['idlib'], 'name_ar' => 'أطمة', 'name_en' => 'Atmah', 'slug' => 'atmah', 'lat' => 36.1900, 'lng' => 36.7000],
            ['governorate_id' => $govIds['idlib'], 'name_ar' => 'خان شيخون', 'name_en' => 'Khan Shaykhun', 'slug' => 'khan-shaykhun', 'lat' => 35.4400, 'lng' => 36.6500],

            // =================================================================
            // السويداء (As-Suwayda) — 3 مناطق
            // =================================================================
            ['governorate_id' => $govIds['as-suwayda'], 'name_ar' => 'السويداء (المركز)', 'name_en' => 'As-Suwayda (Center)', 'slug' => 'as-suwayda-center', 'lat' => 32.7082, 'lng' => 36.5669],
            ['governorate_id' => $govIds['as-suwayda'], 'name_ar' => 'شهبا', 'name_en' => 'Shahba', 'slug' => 'shahba-as-suwayda', 'lat' => 32.8540, 'lng' => 36.6270],
            ['governorate_id' => $govIds['as-suwayda'], 'name_ar' => 'صلخد', 'name_en' => 'Salkhad', 'slug' => 'salkhad', 'lat' => 32.4920, 'lng' => 36.7100],
            // Major towns
            ['governorate_id' => $govIds['as-suwayda'], 'name_ar' => 'قنوات', 'name_en' => 'Qanawat', 'slug' => 'qanawat', 'lat' => 32.7500, 'lng' => 36.6200],
            ['governorate_id' => $govIds['as-suwayda'], 'name_ar' => 'المزرعة', 'name_en' => 'Al-Mazraa', 'slug' => 'al-mazraa', 'lat' => 32.7800, 'lng' => 36.4800],

            // =================================================================
            // درعا (Daraa) — 3 مناطق
            // =================================================================
            ['governorate_id' => $govIds['daraa'], 'name_ar' => 'درعا (المركز)', 'name_en' => 'Daraa (Center)', 'slug' => 'daraa-center', 'lat' => 32.6189, 'lng' => 36.1021],
            ['governorate_id' => $govIds['daraa'], 'name_ar' => 'إزرع', 'name_en' => 'Izra', 'slug' => 'izra', 'lat' => 32.8440, 'lng' => 36.2490],
            ['governorate_id' => $govIds['daraa'], 'name_ar' => 'الصنمين', 'name_en' => 'Al-Sanamayn', 'slug' => 'al-sanamayn', 'lat' => 33.0760, 'lng' => 36.1710],
            // Major towns
            ['governorate_id' => $govIds['daraa'], 'name_ar' => 'نوى', 'name_en' => 'Nawa', 'slug' => 'nawa', 'lat' => 32.8900, 'lng' => 36.0400],
            ['governorate_id' => $govIds['daraa'], 'name_ar' => 'جاسم', 'name_en' => 'Jasim', 'slug' => 'jasim', 'lat' => 32.9700, 'lng' => 36.0700],
            ['governorate_id' => $govIds['daraa'], 'name_ar' => 'الشجرة', 'name_en' => 'Al-Shajara', 'slug' => 'al-shajara', 'lat' => 32.7000, 'lng' => 35.9500],
            ['governorate_id' => $govIds['daraa'], 'name_ar' => 'بصرى الشام', 'name_en' => 'Bosra', 'slug' => 'bosra', 'lat' => 32.5200, 'lng' => 36.4800],
            ['governorate_id' => $govIds['daraa'], 'name_ar' => 'داعل', 'name_en' => 'Daael', 'slug' => 'daael', 'lat' => 32.7600, 'lng' => 36.1300],

            // =================================================================
            // القنيطرة (Quneitra) — 2 مناطق
            // =================================================================
            ['governorate_id' => $govIds['quneitra'], 'name_ar' => 'القنيطرة (المركز)', 'name_en' => 'Quneitra (Center)', 'slug' => 'quneitra-center', 'lat' => 33.1262, 'lng' => 35.8243],
            ['governorate_id' => $govIds['quneitra'], 'name_ar' => 'فيق', 'name_en' => 'Fiq', 'slug' => 'fiq', 'lat' => 32.7800, 'lng' => 35.7000],
            // Major towns
            ['governorate_id' => $govIds['quneitra'], 'name_ar' => 'خان أرنبة', 'name_en' => 'Khan Arnabeh', 'slug' => 'khan-arnabeh', 'lat' => 33.1800, 'lng' => 35.8900],

            // =================================================================
            // دير الزور (Deir ez-Zor) — 3 مناطق
            // =================================================================
            ['governorate_id' => $govIds['deir-ez-zor'], 'name_ar' => 'دير الزور (المركز)', 'name_en' => 'Deir ez-Zor (Center)', 'slug' => 'deir-ez-zor-center', 'lat' => 35.3359, 'lng' => 40.1408],
            ['governorate_id' => $govIds['deir-ez-zor'], 'name_ar' => 'البوكمال', 'name_en' => 'Al-Bukamal', 'slug' => 'al-bukamal', 'lat' => 34.4400, 'lng' => 40.9200],
            ['governorate_id' => $govIds['deir-ez-zor'], 'name_ar' => 'الميادين', 'name_en' => 'Al-Mayadin', 'slug' => 'al-mayadin', 'lat' => 35.0200, 'lng' => 40.4500],
            // Major towns
            ['governorate_id' => $govIds['deir-ez-zor'], 'name_ar' => 'موحسن', 'name_en' => 'Muhasan', 'slug' => 'muhasan', 'lat' => 35.0500, 'lng' => 40.3500],
            ['governorate_id' => $govIds['deir-ez-zor'], 'name_ar' => 'القورية', 'name_en' => 'Al-Quriyah', 'slug' => 'al-quriyah', 'lat' => 34.9500, 'lng' => 40.5500],
            ['governorate_id' => $govIds['deir-ez-zor'], 'name_ar' => 'عشارة', 'name_en' => 'Ashara', 'slug' => 'ashara', 'lat' => 34.7500, 'lng' => 40.8000],

            // =================================================================
            // الحسكة (Al-Hasakah) — 4 مناطق
            // =================================================================
            ['governorate_id' => $govIds['al-hasakah'], 'name_ar' => 'الحسكة (المركز)', 'name_en' => 'Al-Hasakah (Center)', 'slug' => 'al-hasakah-center', 'lat' => 36.5024, 'lng' => 40.7450],
            ['governorate_id' => $govIds['al-hasakah'], 'name_ar' => 'القامشلي', 'name_en' => 'Qamishli', 'slug' => 'qamishli', 'lat' => 37.0520, 'lng' => 41.2310],
            ['governorate_id' => $govIds['al-hasakah'], 'name_ar' => 'المالكية', 'name_en' => 'Al-Malikiyah', 'slug' => 'al-malikiyah', 'lat' => 37.1700, 'lng' => 42.1400],
            ['governorate_id' => $govIds['al-hasakah'], 'name_ar' => 'رأس العين', 'name_en' => 'Ras al-Ayn', 'slug' => 'ras-al-ayn', 'lat' => 36.8500, 'lng' => 40.0800],
            // Major towns
            ['governorate_id' => $govIds['al-hasakah'], 'name_ar' => 'الدرباسية', 'name_en' => 'Al-Darbasiyah', 'slug' => 'al-darbasiyah', 'lat' => 37.0700, 'lng' => 40.6500],
            ['governorate_id' => $govIds['al-hasakah'], 'name_ar' => 'عامودا', 'name_en' => 'Amuda', 'slug' => 'amuda', 'lat' => 37.1000, 'lng' => 40.9300],
            ['governorate_id' => $govIds['al-hasakah'], 'name_ar' => 'الشدادي', 'name_en' => 'Al-Shaddadi', 'slug' => 'al-shaddadi', 'lat' => 36.0700, 'lng' => 40.7400],
            ['governorate_id' => $govIds['al-hasakah'], 'name_ar' => 'تل تمر', 'name_en' => 'Tell Tamer', 'slug' => 'tell-tamer', 'lat' => 36.6500, 'lng' => 40.3700],
            ['governorate_id' => $govIds['al-hasakah'], 'name_ar' => 'معبدة', 'name_en' => 'Al-Mabada', 'slug' => 'al-mabada', 'lat' => 37.0500, 'lng' => 41.3500],

            // =================================================================
            // الرقة (Raqqa) — 3 مناطق
            // =================================================================
            ['governorate_id' => $govIds['raqqa'], 'name_ar' => 'الرقة (المركز)', 'name_en' => 'Raqqa (Center)', 'slug' => 'raqqa-center', 'lat' => 35.9500, 'lng' => 38.9981],
            ['governorate_id' => $govIds['raqqa'], 'name_ar' => 'الطبقة', 'name_en' => 'Al-Thawrah (Tabqa)', 'slug' => 'al-thawrah', 'lat' => 35.8400, 'lng' => 38.5500],
            ['governorate_id' => $govIds['raqqa'], 'name_ar' => 'تل أبيض', 'name_en' => 'Tell Abyad', 'slug' => 'tell-abyad', 'lat' => 36.7000, 'lng' => 38.9500],
            // Major towns
            ['governorate_id' => $govIds['raqqa'], 'name_ar' => 'المنصورة', 'name_en' => 'Al-Mansurah', 'slug' => 'al-mansurah-raqqa', 'lat' => 35.8400, 'lng' => 39.2100],
            ['governorate_id' => $govIds['raqqa'], 'name_ar' => 'السبخة', 'name_en' => 'Al-Sabkhah', 'slug' => 'al-sabkhah', 'lat' => 35.7400, 'lng' => 39.2900],
        ];

        foreach ($areas as &$row) {
            $row['properties_count'] = 0;
            $row['created_at'] = $now;
            $row['updated_at'] = $now;
        }

        DB::table('areas')->insert($areas);
    }
}
