<?php

namespace App\Services\Catalog\Content;

use App\Models\CatalogItem;

/**
 * Builds structured editorial fields for training items ("pelatihan"). Field
 * values derive from keyword families in the official catalog name and are
 * always phrased cautiously: accreditation, duration, price, location,
 * trainer credentials and certificate authorities are never claimed, because
 * none of those facts exist in the catalog source.
 */
final class TrainingContentBuilder
{
    /**
     * Match order matters: the first regex that hits decides the family, so
     * more specific families are listed first.
     */
    private const PATTERNS = [
        'confined-space' => ['confined space', 'ruang terbatas', 'gas tester', 'gas deteksi', 'H2S'],
        'offshore-marine' => ['sea survival', 'huet', 'bosiet', 'mooring', 'barge', 'loading master', 'offshore', 'kelautan', 'oil spill', 'perkapalan', 'shipping', 'platform'],
        'drilling-migas' => ['drilling', 'migas', 'pop', 'pom', 'pou', 'popal'],
        'working-at-height' => ['ketinggian', 'height', 'gondola', 'perancah', 'scaffold', 'tkbt', 'tkpk', 'wah', 'manlift', 'boomlift', 'skylift', 'scissor lift', 'lifter'],
        'lifting-crane' => ['crane', 'dongkrak', 'lifting', 'rigger', 'juru ikat', 'pesawat angkat', 'papa', 'cargo hoist', 'jib'],
        'pressure-vessel' => ['boiler', 'bejana tekan', 'tangki timbun', 'pesawat tenaga', 'ptp', 'compressor', 'turbin', 'tanur', 'mesin produksi', 'perkakas'],
        'vertical-transport' => ['lift', 'elevator', 'eskalator'],
        'warehouse' => ['forklift', 'pallet', 'liftstacker', 'reachstacker', 'gudang', 'supply chain'],
        'heavy-equipment' => ['sio', 'alat berat', 'excavator', 'bulldozer', 'vibro', 'dump truck', 'wheel loader', 'motor grader', 'compactor', 'telehandler', 'penggerak mula', 'underground truck', 'trailer'],
        'welding-metal' => ['welder', 'welding', 'juru las', 'coating', 'paint', 'blasting', 'blaster', 'korosi', 'perkaratan', 'ndt', 'non destructive', 'vibrasi', 'cathodic protection', 'pipe fitter', 'plate'],
        'electrical' => ['kelistrikan', 'listrik', 'trafo', 'genset', 'mep'],
        'technology-technical' => ['telekomunikasi', 'scada', 'plc', 'instrumentasi', 'drone', 'autocad', 'data center'],
        'fire-rescue' => ['first aid', 'first aider', 'p3k', 'fire', 'damkar', 'kebakaran', 'bhd', 'hiperkes', 'paramedic', 'perawat', 'rumah sakit', 'puskesmas'],
        'chemical-hygiene' => ['kimia', 'hazmat', 'higiene', 'ergonomi', 'asbes', 'mercury', 'fumigasi', 'laboratorium', 'food handler', 'spbu', 'hazop'],
        'environment-waste' => ['limbah', 'b3', 'pencemaran', 'pppa', 'pppu', 'poippu', 'pcua', 'pengambil contoh', 'lingkungan', 'karbon'],
        'mining-agri' => ['tambang', 'mining', 'reklamasi', 'survei', 'juru ukur', 'geoteknik', 'sawit', 'perkebunan', 'pertanian', 'kehutanan', 'manggala agni'],
        'construction' => ['konstruksi', 'tukang batu', 'tukang kayu', 'jalan', 'jembatan', 'building', 'bangunan tinggi'],
        'vehicle-driving' => ['defensive driving', 'ddt', 'ddc', 'kendaraan laut', 'trailer'],
        'iso-system' => ['iso', 'auditor smk3', 'smk3', 'csms', 'haccp', 'nebosh'],
        'management-soft' => ['manajemen', 'management', 'leadership', 'supervisory', 'hrd', 'akuntansi', 'quality control', 'qc', 'selling', 'bimtek', 'pemberdayaan', '7 habits', '5s', 'data analyst', 'tot', 'pasca sekolah', 'permit to work', 'jsa', 'investigator', 'safety officer'],
        'k3-general' => ['k3', 'keselamatan', 'dasar'],
    ];

    private static ?array $profiles = null;

    private static function profiles(): array
    {
        if (self::$profiles !== null) {
            return self::$profiles;
        }

        // subject, audience, blurb, objectives, curriculum, extraFaq
        self::$profiles = [
            // PROFILE-INSERT
            'k3-general' => [
                's' => 'dasar-dasar keselamatan dan kesehatan kerja (K3) untuk berbagai sektor',
                'a' => 'petugas K3, pengawas lapangan, dan personel yang baru ditugasi menangani keselamatan kerja',
                'b' => 'Peserta mempelajari cara mengenali bahaya kerja, menggunakan APD dengan benar, serta melaporkan temuan secara tertib.',
                'o' => [
                    'Menjelaskan peran dan tanggung jawab fungsi K3 pada unit kerja masing-masing',
                    'Mengidentifikasi bahaya harian dan menilai tingkat risikonya',
                    'Menerapkan penggunaan APD sesuai kondisi kerja',
                    'Mendokumentasikan dan melaporkan temuan K3 secara baku',
                ],
                'c' => [
                    'Bahaya kerja umum dan penyakit akibat kerja',
                    'Gambaran singkat sistem manajemen keselamatan kerja',
                    'APD: pemilihan, penggunaan, dan pemeriksaan',
                    'Pengamatan kegiatan dan pelaporan di lapangan',
                    'Studi kasus penerapan di tempat kerja',
                ],
                'x' => [],
            ],
            'confined-space' => [
                's' => 'pekerjaan di ruang terbatas, izin masuk, dan pengukuran atmosfer berbahaya',
                'a' => 'peserta entri ruang terbatas, attendant pintu, penyelamat (rescue), dan teknisi pengukuran gas',
                'b' => 'Peserta mempelajari skenario masuk-keluar ruang terbatas, pemantauan atmosfer, dan tata peran tim entri.',
                'o' => [
                    'Menyusun izin masuk (entry permit) ruang terbatas secara tertib',
                    'Mengenali bahaya atmosfer: kekurangan oksigen, gas mudah terbakar, gas toksik',
                    'Menjalankan tugas attendant dan penyelamat sesuai pembagian peran',
                    'Mengenali kondisi yang mewajibkan pembatalan atau penghentian pekerjaan',
                ],
                'c' => [
                    'Klasifikasi ruang terbatas dan bahayanya',
                    'Peran entrant, attendant, dan pengawas',
                    'Pengukuran atmosfer dengan gas detector',
                    'Prosedur penyelamatan ruang terbatas',
                    'Simulasi kasus masuk ruang terbatas',
                ],
                'x' => [],
            ],
            'offshore-marine' => [
                's' => 'keselamatan kerja area offshore/maritim dan Operasi di lingkungan laut',
                'a' => 'personel offshore, kru pelabuhan/barge, officer maritim, dan pengawas HSE area marine',
                'b' => 'Peserta mempelajari bahaya khas lingkungan laut, prosedur bertahan, dan disiplin kerja di area platform/barge.',
                'o' => [
                    'Mengenali bahaya khas area offshore/maritim beserta pengendaliannya',
                    'Menjalankan prosedur dasar survival dan evakuasi laut',
                    'Menjaga disiplin pembatasan wilayah kerja di platform/barge',
                    'Melaporkan kondisi tidak aman di area maritim secara tertib',
                ],
                'c' => [
                    'Bahaya spesifik offshore: cuaca, gelombang, tumpahan',
                    'Dasar survival laut dan alat pengapung',
                    'Komunikasi darurat dan alat penyelamatan talopes',
                    'Keselamatan bekerja di dermaga, barge, dan platform',
                    'Kasus insiden maritim yang umum dijadikan pembelajaran',
                ],
                'x' => [],
            ],
            'drilling-migas' => [
                's' => 'pengoperasian dan pengawasan keselamatan bidang migas/drilling',
                'a' => 'operator wellsite, pengawas operasi migas, personel rig, dan penanggung jawab operasional sesuai tingkatannya',
                'b' => 'Peserta mempelajari tata kerja aman di rig/rig-less, pengendalian area kritis, dan tanggung jawab penanggung jawab operasional.',
                'o' => [
                    'Menjelaskan tanggung jawab penanggung jawab operasional sesuai tingkat program',
                    'Mengenali bahaya khas migas: kick, ledakan, gas mudah terbakar',
                    'Menerapkan izin kerja dan kendali sumber nyala di area kritis',
                    'Mendokumentasikan laporan operasi harian secara tertib',
                ],
                'c' => [
                    'Dasar dinamika operasi rig dan pengendalian sumur',
                    'Zona bahaya area migas dan pengelompokannya',
                    'Prosedur izin kerja area migas',
                    'Tata tertib operasi workover dan maintenance rig',
                    'Kasus insiden bidang migas untuk pembelajaran',
                ],
                'x' => [],
            ],
            'working-at-height' => [
                's' => 'pekerjaan di ketinggian: platform kerja, perancah, dan pengendalian risiko jatuh',
                'a' => 'pekerja ketinggian, teknisi perancah, pengawas area kerja vertikal, dan personel fasilitas',
                'b' => 'Peserta mempelajari penilaian titik kerja, penggunaan harness, dan pemeriksaan platform sebelum digunakan.',
                'o' => [
                    'Menilai kelayakan titik kerja tinggi sebelum bekerja',
                    'Menggunakan full body harness dan pengikatannya dengan benar',
                    'Memeriksa kondisi perancah/platform menurut prinsip aman',
                    'Menyiapkan rencana penyelamatan di ketinggian',
                ],
                'c' => [
                    'Definisi pekerjaan ketinggian dan zona bahayanya',
                    'Sistem perlindungan jatuh dan pemeriksaannya',
                    'Perancah: komponen, perakitan, dan pemeriksaan',
                    'Pengaruh cuaca (angin/hujan) pada pekerjaan tinggi',
                    'Dasar rencana rescue di ketinggian',
                ],
                'x' => [],
            ],
            'lifting-crane' => [
                's' => 'kegiatan pembebanan (lifting) dan pengoperasian pesawat angkat',
                'a' => 'rigger, operator crane, lifting supervisor, dan personel logistik yang terlibat aktivitas lifting',
                'b' => 'Peserta mempelajari pembacaan rencana lifting, pemeriksaan alat ikat, dan komunikasi sinyal operator-rigger.',
                'o' => [
                    'Menyusun rencana lifting sederhana sesuai kondisi lapangan',
                    'Memeriksa kelayakan sling dan aksesori ikat sebelum digunakan',
                    'Menggunakan sinyal komunikasi operator-rigger secara konsisten',
                    'Menetapkan zona sinyal aman di bawah alat angkat',
                ],
                'c' => [
                    'Jenis pesawat angkat dan batas penggunaannya',
                    'Rencana lifting dan identifikasi zona berbahaya',
                    'Rigging: jenis sling, sudut ikat, beban aman',
                    'Sinyal komunikasi antar operator dan rigger di lapangan',
                    'Kasus kegagalan lifting dan pencegahannya',
                ],
                'x' => [],
            ],
            'pressure-vessel' => [
                's' => 'pesawat tenaga, bejana tekan, dan mesin yang memproses media bertekanan atau berpanas',
                'a' => 'operator mesin produksi, teknisi bejana tekan, personel area tenaga, dan pengawas pabrikasi',
                'b' => 'Peserta mempelajari parameter operasi normal, tanda-tanda penyimpangan, dan pengendalian risiko tekanan.',
                'o' => [
                    'Membaca parameter operasi normal dan tanda penyimpangannya',
                    'Mengenali bahaya kerja pada peralatan bertekanan (ledakan, kebocoran)',
                    'Menerapkan langkah pengamanan saat perawatan berjalan',
                    'Menyiapkan catatan pemeriksaan kelayakan harian',
                ],
                'c' => [
                    'Dasar teknologi pesawat tenaga dan bejana tekan',
                    'Instrumen utama: manometer, safety valve, pengukur level',
                    'Kebocoran dan kerusakan umum pada sistem bertekanan',
                    'Langkah pengamanan sebelum/dalam pemeliharaan',
                    'Simulasi pemeriksaan harian',
                ],
                'x' => [],
            ],
            'vertical-transport' => [
                's' => 'keselamatan lift, eskalator, dan unit transportasi vertikal pada gedung',
                'a' => 'teknisi, pengelola gedung/fasilitas, tim pemeliharaan, dan petugas yang bersentuhan langsung dengan unit',
                'b' => 'Peserta mempelajari inspeksi harian, tata tertib kondisi darurat, dan pembatasan penggunaan unit bermasalah.',
                'o' => [
                    'Mengenali komponen utama unit transportasi vertikal dan tanda kerusakannya',
                    'Menjalankan pemeriksaan harian dan menggunakan logbook',
                    'Menerapkan langkah aman saat unit berhenti/terjebak',
                    'Meneruskan laporan kerja sama pemeliharaan secara tertib',
                ],
                'c' => [
                    'Anatomi lift/eskalator secara ringkas',
                    'Pemeriksaan harian dan pengisian logbook',
                    'Reaksi darurat: unit berhenti, penumpang terjebak',
                    'Batas beban dan kapasitas unit',
                ],
                'x' => [],
            ],
            'warehouse' => [
                's' => 'alur barang, material handling, dan keselamatan operasi di gudang',
                'a' => 'operator material handling, staf gudang, perencana inventory, dan pengawas logistik',
                'b' => 'Peserta mempelajari penataan jalur unit dan pejalan kaki, teknik angkat manual, dan pencatatan alat gudang.',
                'o' => [
                    'Menata jalur pejalan kaki dan jalur unit di area gudang',
                    'Menerapkan tata simpan yang aman dan rapi (tanpa menumpuk sembarangan)',
                    'Menggunakan teknik mengangkat manual yang ergonomis',
                    'Mencatat kondisi dan pemakaian alat gudang secara tertib',
                ],
                'c' => [
                    'Tata letak gudang yang aman',
                    'Alat pemindahan kecil: pallet, mover, stacker',
                    'Manual handling: teknik angkat ergonomis',
                    'Penyimpanan bertingkat dan batas tumpukan',
                    'Ringkasan kasus insiden gudang umum',
                ],
                'x' => [],
            ],
            'heavy-equipment' => [
                's' => 'pengoperasian alat berat dan unit pekerjaan tanah',
                'a' => 'calon operator, operator junior, mekanik unit, dan pengawas area pekerjaan tanah',
                'b' => 'Peserta mempelajari pemeriksaan pra-operasi, zona aman di sekitar unit, dan komunikasi sinyal saat unit bergerak.',
                'o' => [
                    'Menjalankan pemeriksaan pra-operasi mandiri sebelum menyalakan unit',
                    'Menetapkan zona aman dalam radius manuver unit',
                    'Membaca kondisi lapangan / halangan lingkungan sebelum bekerja',
                    'Mendokumentasikan jam operasi dan catatan gangguan unit',
                ],
                'c' => [
                    'Jenis alat berat dan aplikasinya di lapangan',
                    'Alat keselamatan unit: alarm mundur, kamera, cermin',
                    'Pengenalan medan kerja: lereng, tanah lembek, jaringan bawah tanah',
                    'Kecelakaan umum alat berat dan pencegahannya',
                    'Latihan pembacaan sinyal lalu lintas area kerja',
                ],
                'x' => [],
            ],
            'welding-metal' => [
                's' => 'pekerjaan las, persiapan permukaan (coating/painting/blasting), dan proteksi material',
                'a' => 'welder, helper, inspektur las, operator coating/blasting, dan QC area fabrikasi',
                'b' => 'Peserta mempelajari bahaya proses las listrik, permukaan, serta pemeriksaan visual hasil kerja.',
                'o' => [
                    'Menyiapkan area kerja las dengan pembatas aman',
                    'Menggunakan PPE sesuai proses kerja (las, blasting, coating)',
                    'Mengenali cacat umum hasil las/permukaan secara visual',
                    'Melaporkan kondisi tidak aman (percikan api, sisa material) secara cepat',
                ],
                'c' => [
                    'Bahaya proses las: percikan, radiasi, gas hasil pembakaran',
                    'Ventilasi dan kontrol api pada area terbatas',
                    'Persiapan permukaan: blasting, primer, cat lapis',
                    'Korosi/perkaratan: penyebab dan tindakan proteksi dasar',
                    'Ringkasan inspeksi visual tingkat awal',
                ],
                'x' => [],
            ],
            'electrical' => [
                's' => 'keselamatan kerja kelistrikan: instalasi, peralatan, dan izin kerja listrik',
                'a' => 'elektrisi, teknisi listrik gedung/kendaraan, personel instalasi daya, dan pengelola fasilitas',
                'b' => 'Peserta mempelajari isolasi energi, pengukuran aman, dan langkah kerja listrik sehari-hari.',
                'o' => [
                    'Menerapkan langkah de-energize, penguncian, dan verifikasi tegangan',
                    'Mengenali bahaya listrik: sentuhan langsung, tidak langsung, arc flash',
                    'Menggunakan APD kelistrikan sesuai kelas bahayanya',
                    'Memeriksa kondisi dasar instalasi (kabel, proteksi, grounding)',
                ],
                'c' => [
                    'Dasar arus, tegangan, dan proteksi (grounding, pengaman)',
                    'Keselamatan kerja listrik: isolasi dan pengujian',
                    'Kondisi umum kerusakan instalasi dan tanda bahayanya',
                    'Izin kerja pada panel/jaringan aktif',
                    'Kasus insiden listrik untuk pembelajaran',
                ],
                'x' => [],
            ],
            'technology-technical' => [
                's' => 'teknologi teknis: telekomunikasi, otomasi, dan perangkat data lapangan',
                'a' => 'teknisi jaringan/instrumentasi, pengelola data center, operator perangkat teknis lapangan, dan engineer',
                'b' => 'Peserta mempelajari prosedur kerja aman di area teknis: perawatan perangkat, energi cadangan, dan area terbatas.',
                'o' => [
                    'Menerapkan pemeriksaan aman pada panel/rak perangkat teknis',
                    'Menangani baterai/UPS/energi cadangan dengan aman',
                    'Meminta izin kerja pada ruang teknik yang terbatas',
                    'Mendokumentasikan perubahan konfigurasi perangkat',
                ],
                'c' => [
                    'Bahaya umum area teknis: listrik, panas, ruang terbatas',
                    'Keselamatan bekerja di tower/area tinggi telekomunikasi (bila relevan)',
                    'Keamanan pekerjaan fiber/laser dan proteksi mata',
                    'Data center: pendinginan dan aliran daya',
                    'Ringkasan insiden teknis kecil dan cara menghindarinya',
                ],
                'x' => [],
            ],
            'fire-rescue' => [
                's' => 'penanganan darurat: pemadaman api awal, pertolongan pertama, dan evakuasi',
                'a' => 'petugas kecemasan/pemadam api, first aider, personel fasilitas kesehatan, dan petugas sarana publik',
                'b' => 'Peserta mempelajari langkah cepat saat kebakaran awal, pertolongan pertama dasar, dan tata evakuasi.',
                'o' => [
                    'Mengoperasikan APAR dan jalur pemadaman kecil sesuai kelas api',
                    'Memberikan pertolongan pertama dasar kepada korban',
                    'Melakukan evakuasi ke titik kumpul secara tenang dan tertib',
                    'Menyusun laporan sederhana setelah insiden',
                ],
                'c' => [
                    'Segitiga api dan kelas kebakaran',
                    'APAR: jenis, cara pakai, batas aman',
                    'Dasar pertolongan pertama dan stabilisasi korban',
                    'Penanganan luka dan luka bakar ringan',
                    'Simulasi evakuasi dan titik kumpul',
                ],
                'x' => [],
            ],
            'chemical-hygiene' => [
                's' => 'bahaya bahan kimia, keselamatan laboratorium, dan higiene industri',
                'a' => 'petugas kimia, pekerja laboratorium, operator penanganan bahan, dan staf lingkungan kerja',
                'b' => 'Peserta mempelajari label GHS/SDS, pengelompokan bahan, ventilasi, dan langkah pertolongan dasar saat kontak bahan.',
                'o' => [
                    'Membaca label GHS dan lembar data keselamatan (SDS) dengan benar',
                    'Menata penyimpanan bahan kimia sesuai kelompok bahayanya',
                    'Menyiapkan tanggul tumpahan kecil (spill kit)',
                    'Menggunakan APD kimia sesuai sifat bahan',
                ],
                'c' => [
                    'Klasifikasi bahaya kimia dan label GHS',
                    'SDS: bagian penting dan kapan membacanya',
                    'APD kimia: pemilihan dan batas pakai',
                    'Higiene industri dasar: jalur masuk bahaya ke tubuh',
                    'Bahan khusus (asbes, metal berat, fumigasi) bila program menekankan',
                ],
                'x' => [],
            ],
            'environment-waste' => [
                's' => 'pengelolaan limbah, pengendalian pencemaran, dan pengambilan sampel lingkungan',
                'a' => 'petugas pengelola limbah, pengendali pencemaran udara/air, operator lingkungan industri, dan staf lingkungan pabrik',
                'b' => 'Peserta mempelajari alur penanganan limbah, catatan perpindahan, dan pengambilan contoh untuk pemeriksaan.',
                'o' => [
                    'Menjelaskan alur umum penyimpanan sementara dan penyerahan limbah',
                    'Melabeli dan mencatat perpindahan limbah secara tertib',
                    'Mengambil contoh sampel uji sesuai prosedur dasar',
                    'Membaca dan meneruskan data hasil pemantauan lingkungan',
                ],
                'c' => [
                    'Sifat umum limbah dan sumber pencemarannya',
                    'Penyimpanan sementara, pelabelan, dan dokumen perpindahan',
                    'Teknik pengambilan contoh air/tanah/udara dasar',
                    'Catatan lingkungan: logbook dan pelaporan',
                    'Studi kasus penanganan tumpahan limbah kecil',
                ],
                'x' => [],
            ],
            'mining-agri' => [
                's' => 'keselamatan pekerjaan sektor pertambangan, perkebunan, dan sumber daya alam',
                'a' => 'personel site tambang/kebun, surveyor, pengawas lapangan, dan personel reklamasi',
                'b' => 'Peserta mempelajari bahaya lingkungan lapangan, komunikasi unit bergerak, dan tata pelaporan di site.',
                'o' => [
                    'Mengidentifikasi bahaya khas site tambang/kebun beserta pengendalinya',
                    'Menerapkan komunikasi radio dan pelaporan kondisi site',
                    'Menata zona aman di sekitar alat berat tambang',
                    'Mengenali tindakan dasar reklamasi dan perlindungan lingkungan site',
                ],
                'c' => [
                    'Sistem pengawasan bahaya di area tambang',
                    'Kerja di medan berat: lereng, lubang, lorong',
                    'Reklamasi dan pengelolaan lingkungan pasca-aktivitas',
                    'Bahaya khas kebun/kehutanan: pestisida, panas, fauna',
                    'Ringkasan kasus kecelakaan sektor tambang',
                ],
                'x' => [],
            ],
            'construction' => [
                's' => 'keselamatan proses konstruksi: site proyek, pekerjaan kraftangan, dan pengawasan proy',
                'a' => 'personel proyek konstruksi, manajer lapangan, tenaga kerja bangunan, dan staf konsultan pekerjaan sipil',
                'b' => 'Peserta mempelajari prosedur dasar site, pembatasan area, dan inspeksi perancah/area kerja harian.',
                'o' => [
                    'Menyusun catatan bahaya site (tinggi, listrik sementara, material lepas)',
                    'Menerapkan pembatasan zona dan jalur aman di proyek',
                    'Memeriksa kelayakan perancah/temporary structures sebelum digunakan',
                    'Mendokumentasikan inspeksi harian serta menyusun daftar tindakan',
                ],
                'c' => [
                    'Lingkup keselamatan konstruksi dan rencana darurat site',
                    'Pembatasan area kerja dan jalur pejalan kaki',
                    'Keselamatan pekerjaan tradisional (batu, kayu, dll.)',
                    'Asas kerja aman di ketinggian konstruksi',
                    'Ringkasan insiden konstruksi yang umum dijadikan pembelajaran',
                ],
                'x' => [],
            ],
            'vehicle-driving' => [
                's' => 'defensive driving dan keselamatan pengemudian kendaraan operasional',
                'a' => 'pengemudi operasional, driver armada truk, dan pengawas fleet perusahaan',
                'b' => 'Peserta mempelajari antisipasi risiko berkendara, manajemen kelelahan, dan pemeriksaan harian kendaraan.',
                'o' => [
                    'Menerapkan prinsip defensif dalam berkendara di jalan operasional',
                    'Menata jarak aman dan kecepatan sesuai kondisi jalan',
                    'Mengenali tanda kelelahan dan memutuskan berhenti dengan benar',
                    'Menjalankan pemeriksaan pra-rute kendaraan',
                ],
                'c' => [
                    'Faktor kecelakaan: manusia, mesin, lingkungan',
                    'Jarak aman dan teknik rem dan antisipasi kecepatan',
                    'Berkendara malam, hujan, dan medan terbatas',
                    'Checklist kendaraan harian: ban, rem, lampu',
                    'Ringkasan insiden jalan raya khas operasional',
                ],
                'x' => [],
            ],
            'iso-system' => [
                's' => 'sistem manajemen berstandar (ISO/SMK3/HACCP secara umum) dan dasar audit internal',
                'a' => 'staf mutu/HSE, internal auditor, management representative, dan personel yang disiapkan menjadi auditor',
                'b' => 'Peserta mempelajari kerangka klausul, bukti audit, dan cara menyusun laporan ketidaksesuaian.',
                'o' => [
                    'Menjelaskan struktur umum sistem manajemen yang dibahas program',
                    'Menyusun checklist audit sesuai lingkup yang disepakati',
                    'Mengenali ketidaksesuaian dan menulis laporan NCR dasar',
                    'Menyiapkan bukti pemantauan kinerja untuk audit',
                ],
                'c' => [
                    'Kerangka sistem manajemen menurut standar yang dibahas',
                    'Audit internal: perencanaan, pelaksanaan, laporan',
                    'Dokumentasi sistem: prosedur, form, catatan',
                    'Tindak lanjut dan perbaikan berkelanjutan',
                    'Ringkasan peran auditor dan auditee',
                ],
                'x' => [],
            ],
            'management-soft' => [
                's' => 'peningkatan kapasitas manajemen, kepemimpinan, dan pengelolaan kegiatan organisasi',
                'a' => 'manajer, supervisor, staf HRD, pelaku usaha, dan personel yang mengelola program/kegiatan tim',
                'b' => 'Peserta mempelajari kerangka kerja praktis: perumusan tujuan, pembagian tugas, umpan balik kinerja, dan pemantauan hasil.',
                'o' => [
                    'Menyusun rencana kegiatan dengan indikator yang jelas',
                    'Menerapkan delegasi pelaksanaan sesuai kemampuan anggota tim',
                    'Memberikan umpan balik kinerja secara konstruktif',
                    'Mengelola data sederhana untuk pengambilan keputusan',
                ],
                'c' => [
                    'Dasar manajemen berbasis proses',
                    'Perumusan tujuan kerja dan indikatornya',
                    'Komunikasi efektif dan umpan balik',
                    'Pengelolaan rapat/dokumen yang efektif',
                    'Studi kasus penerapan di organisasi',
                ],
                'x' => [],
            ],
        ];

        return self::$profiles;
    }

    // ------------------------------------------------------------------
    // Public API
    // ------------------------------------------------------------------

    /**
     * Build the editorial fields for one training item. "Format" and
     * "assessment" are intentionally left unset: delivery format, assessment
     * method and certificate claims are not stated in the catalog source, so
     * they must be confirmed by a human before appearing on the page.
     *
     * @return array<string, mixed>
     */
    public static function build(CatalogItem $item): array
    {
        $profile = self::matchProfile($item->name);

        $fields = [
            'intro' => self::intro($item->name, $profile),
            'purpose' => self::purpose($item->name, $profile),
            'audience' => self::audience($item->name, $profile),
            'objectives' => self::objectives($item->name, $profile),
            'curriculum' => $profile['c'],
            'documents' => self::documents(),
            'process' => self::processSteps($item->name),
            'faqs' => self::faqs($item->name),
        ];

        $prerequisites = self::prerequisites($item->name);
        if ($prerequisites !== null) {
            $fields['prerequisites'] = $prerequisites;
        }

        return $fields;
    }

    /**
     * @return array{domain: string, s: string, a: string, b: string, o: list<string>, c: list<string>, x: list<array{question: string, answer: string}>}
     */
    public static function matchProfile(string $name): array
    {
        $lower = mb_strtolower($name);

        foreach (self::PATTERNS as $domain => $needles) {
            foreach ($needles as $needle) {
                if (str_contains($lower, mb_strtolower($needle))) {
                    return ['domain' => $domain] + self::profiles()[$domain];
                }
            }
        }

        return ['domain' => 'k3-general'] + self::profiles()['k3-general'];
    }

    // ------------------------------------------------------------------
    // Field composers
    // ------------------------------------------------------------------

    private static function intro(string $name, array $profile): string
    {
        $role = self::roleSentence($name);
        $roleText = $role !== null ? $role.' ' : '';

        return "\"{$name}\" adalah program di bidang {$profile['s']}. {$profile['b']} {$roleText}Nama program dikutip persis dari katalog sumber, sedangkan lingkup akhir pelaksanaan disepakati bersama saat konsultasi.";
    }

    private static function purpose(string $name, array $profile): string
    {
        if ($profile['domain'] === 'management-soft') {
            return 'Tujuan program: membekali peserta dengan kerangka kerja praktis agar pekerjaan tim berjalan lebih tertib, terarah, dan terukur setelah program selesai.';
        }

        return "Tujuan program: membekali peserta dengan pemahaman dan kebiasaan kerja aman pada bidang \"{$name}\" agar tugas di lapangan dapat dijalankan lewat prosedur yang jelas.";
    }

    private static function audience(string $name, array $profile): string
    {
        $tier = self::tierAudience($name);

        return 'Program paling relevan untuk '.$profile['a'].$tier;
    }

    /**
     * First objective quotes the exact program name so every page reads
     * differently; the rest come from the domain profile.
     *
     * @param  array{
     *     domain: string, s: string, a: string, b: string,
     *     o: list<string>, c: list<string>,
     *     x: list<array{question: string, answer: string}>
     * }  $profile
     * @return list<string>
     */
    private static function objectives(string $name, array $profile): array
    {
        $objectives = $profile['o'];
        array_unshift($objectives, "Menautkan lingkup \"{$name}\" dengan tanggung jawab nyata peserta di tempat kerja");

        return array_slice($objectives, 0, 5);
    }

    /**
     * @return list<string>
     */
    private static function documents(): array
    {
        return [
            'Identitas peserta: nama lengkap, jabatan, instansi/perusahaan',
            'Kebutuhan jumlah peserta dan perkiraan waktu pelaksanaan',
            'Gambaran kondisi alat/area kerja bila program bersifat operasional',
        ];
    }

    /**
     * @return list<string>
     */
    private static function processSteps(string $name): array
    {
        return [
            'Hubungi salah satu kanal WhatsApp resmi di halaman ini dan sebutkan program "'.$name.'"',
            'Kirim kebutuhan Anda: jumlah peserta, perkiraan jadwal, dan catatan khusus',
            'Tim kami membalas dengan pertanyaan penjajakan dan gambaran pelaksanaan',
            'Kesepakatan lingkup dan jadwal disepakati lalu program terlaksana sesuai rencana',
        ];
    }

    /**
     * @return list<array{question: string, answer: string}>
     */
    private static function faqs(string $name): array
    {
        return [
            [
                'question' => "Bagaimana cara mendaftar program \"{$name}\"?",
                'answer' => 'Kirim pesan melalui salah satu tombol WhatsApp di halaman ini dan sebutkan nama program tersebut. Tim kami memandu langkah pendaftaran yang berlaku saat ini.',
            ],
            [
                'question' => 'Jumlah peserta minimum berapa untuk bisa jalan?',
                'answer' => 'Batas jumlah peserta tidak tercantum di katalog sumber; mohon konfirmasi langsung kepada tim kami lewat WhatsApp.',
            ],
            [
                'question' => 'Apakah tersedia pelaksanaan di lokasi kami?',
                'answer' => 'Penempatan pelaksanaan disepakati saat konsultasi. Semua pertanyaan jadwal dan lokasi bisa langsung diajukan lewat kanal WhatsApp di halaman ini.',
            ],
        ];
    }

    // ------------------------------------------------------------------
    // Nuance helpers (name to sentence)
    // ------------------------------------------------------------------

    private static function roleSentence(string $name): ?string
    {
        if (preg_match('/(pengawas|penanggung jawab|inspektur|manggala|supervisory|manager)/i', $name) === 1) {
            return 'Karena menyasar peran pengawas/penanggung jawab, pembahasan menekankan pengambilan keputusan, pengendalian lapangan, dan pelaporan.';
        }

        if (preg_match('/(teknisi|perawatan|mekanik|instrumentasi)/i', $name) === 1) {
            return 'Karena menyasar tenaga teknis, pembahasan menekankan pemeriksaan berkala, perawatan unit, dan penanganan dini gangguan.';
        }

        if (preg_match('/(utama|senior|tingkat 2)/i', $name) === 1) {
            return 'Sebagai program tingkat lanjut, pembahasan menyentuh sisi analisis, penyusunan dokumen, dan penilaian risiko yang lebih dalam.';
        }

        if (preg_match('/^(operator|driver|rigger|welder|painter|blaster|juru|petugas)/i', $name) === 1) {
            return 'Karena menyasar pelaksana/praktisi, pembahasan menekankan langkah kerja langsung, pemeriksaan pra-operasi, dan penggunaan APD.';
        }

        if (preg_match('/(muda|dasar|tingkat 1|kelas 3)/i', $name) === 1) {
            return 'Sebagai program tingkat awal, pembahasan tidak menuntut pengalaman formal yang panjang.';
        }

        return null;
    }

    private static function tierAudience(string $name): string
    {
        if (preg_match('/(utama|tingkat 2|kelas 1)/i', $name) === 1) {
            return ' Ditujukan untuk tingkat lanjut dalam keluarga programnya; peserta didorongan sudah memiliki paparan bidang serupa.';
        }

        if (preg_match('/(madya|kelas 2)/i', $name) === 1) {
            return ' Ditujukan untuk tingkat menengah dalam keluarga programnya.';
        }

        return '';
    }

    /**
     * Tier names imply a level ladder, but the official requirements are not
     * in the source document: this stays a cautious pointer, never a
     * claim.
     */
    private static function prerequisites(string $name): ?string
    {
        if (preg_match('/(kelas\s?\d|madya|utama|tingkat\s?\d)/i', $name) !== 1) {
            return null;
        }

        return 'Nama program memuat penanda tingkat (kelas/madya/utama/tingkat). Persyaratan resmi antar-tingkat tidak tercantum di katalog sumber, jadi mohon konfirmasi syarat teknis (pengalaman/pendidikan) kepada tim kami melalui WhatsApp sebelum mendaftar.';
    }
}
