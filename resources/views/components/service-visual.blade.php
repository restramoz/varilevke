@props(['item' => null, 'category' => 'pelatihan'])

@php
    $name = strtolower($item->name ?? '');
    $cat = strtolower($item->category ?? $category);

    $archetype = 'default';
    if (str_contains($name, 'forklift')) {
        $archetype = 'forklift';
    } elseif (str_contains($name, 'crane') || str_contains($name, 'rigger') || str_contains($name, 'angkat') || str_contains($name, 'lifting')) {
        $archetype = 'crane';
    } elseif (str_contains($name, 'kebakaran') || str_contains($name, 'damkar') || str_contains($name, 'fire') || str_contains($name, 'fire fighting') || str_contains($name, 'basic fire')) {
        $archetype = 'fire';
    } elseif (str_contains($name, 'emisi') || str_contains($name, 'lingkungan') || str_contains($name, 'amdal') || str_contains($name, 'limbah') || str_contains($name, 'paling') || str_contains($name, 'ukl') || str_contains($name, 'upl')) {
        $archetype = 'env';
    } elseif ($cat === 'kajian' || str_contains($name, 'kajian') || str_contains($name, 'studi') || str_contains($name, 'audit') || str_contains($name, 'risk')) {
        $archetype = 'kajian';
    } elseif ($cat === 'jasa' || str_contains($name, 'slf') || str_contains($name, 'slo') || str_contains($name, 'silo') || str_contains($name, 'sia') || str_contains($name, 'riksa') || str_contains($name, 'izin') || str_contains($name, 'perizinan') || str_contains($name, 'iso') || str_contains($name, 'publikasi') || str_contains($name, 'jurnal')) {
        $archetype = 'jasa';
    }
@endphp

<div class="card-k3 p-6 lg:p-8 space-y-6 my-8">
    <!-- Visual Section Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-grid pb-4 gap-2">
        <div class="flex items-center gap-2.5">
            <span class="w-3 h-3 bg-accent inline-block"></span>
            <span class="font-display font-bold text-xs uppercase tracking-widest text-fg">
                Skema Teknis &amp; Alur Operasional Layanan
            </span>
        </div>
        <div class="font-display text-[10px] text-accent-bright bg-regulatory-900 border border-accent px-2.5 py-1 uppercase tracking-wider self-start sm:self-auto">
            STANDAR REGULASI KEMNAKER RI / BNSP
        </div>
    </div>

    @if($archetype === 'forklift')
        <!-- Forklift Operational & Safety Matrix -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 font-display text-xs">
            <div class="bg-regulatory-900 border border-grid p-4 space-y-2">
                <div class="text-accent-bright text-[11px] font-bold uppercase tracking-wider">TAHAP 01 &bull; PRE-USE CHECK</div>
                <h4 class="font-bold text-fg">Pemeriksaan Visual Unit</h4>
                <ul class="text-muted text-[11px] space-y-1 font-body">
                    <li>&bull; Cek tekanan ban &amp; kebocoran hidrolik</li>
                    <li>&bull; Mast assembly &amp; rantai angkat lift chain</li>
                    <li>&bull; Kondisi garpu (fork) &amp; sistem kemudi</li>
                </ul>
            </div>
            <div class="bg-regulatory-900 border border-grid p-4 space-y-2">
                <div class="text-link text-[11px] font-bold uppercase tracking-wider">TAHAP 02 &bull; LOAD CAPACITY</div>
                <h4 class="font-bold text-fg">Segitiga Stabilitas &amp; Beban</h4>
                <ul class="text-muted text-[11px] space-y-1 font-body">
                    <li>&bull; Perhitungan Load Center 500mm / 600mm</li>
                    <li>&bull; Manuver area sempit &amp; kemiringan lereng</li>
                    <li>&bull; Prosedur stacking &amp; de-stacking palet</li>
                </ul>
            </div>
            <div class="bg-regulatory-900 border border-grid p-4 space-y-2">
                <div class="text-caution-bright text-[11px] font-bold uppercase tracking-wider">TAHAP 03 &bull; LISENSI K3</div>
                <h4 class="font-bold text-fg">Sertifikasi SIO Kemnaker</h4>
                <ul class="text-muted text-[11px] space-y-1 font-body">
                    <li>&bull; Ujian teori peraturan perundangan K3</li>
                    <li>&bull; Uji praktik pengoperasian mandiri</li>
                    <li>&bull; Penerbitan Lisensi K3 / SIO resmi</li>
                </ul>
            </div>
        </div>

    @elseif($archetype === 'crane')
        <!-- Crane & Rigging Operational Layout -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 font-display text-xs">
            <div class="bg-regulatory-900 border border-grid p-4 space-y-2">
                <div class="text-accent-bright text-[11px] font-bold uppercase tracking-wider">MODUL 01 &bull; RIGGING CALCULATION</div>
                <h4 class="font-bold text-fg">Kapasitas Angkat (SWL)</h4>
                <p class="text-muted text-[11px] font-body">Perhitungan beban aman Safe Working Load (SWL), sudut sling webbing, dan titik berat center of gravity.</p>
            </div>
            <div class="bg-regulatory-900 border border-grid p-4 space-y-2">
                <div class="text-link text-[11px] font-bold uppercase tracking-wider">MODUL 02 &bull; RADIUS &amp; CLEARANCE</div>
                <h4 class="font-bold text-fg">Zona Kerja &amp; Perimeter Aman</h4>
                <p class="text-muted text-[11px] font-body">Analisis radius kerja crane, ground bearing pressure outrigger, serta mitigasi bahaya tegangan kabel listrik.</p>
            </div>
            <div class="bg-regulatory-900 border border-grid p-4 space-y-2">
                <div class="text-caution-bright text-[11px] font-bold uppercase tracking-wider">MODUL 03 &bull; SIGNAL &amp; CONTROL</div>
                <h4 class="font-bold text-fg">Sinyal Standar &amp; Riksa Teknis</h4>
                <p class="text-muted text-[11px] font-body">Pemberian aba-aba hand signal standar internasional, koordinasi rigger-operator, dan buku kerja harian.</p>
            </div>
        </div>

    @elseif($archetype === 'fire')
        <!-- Fire Safety Defense Sequence -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 font-display text-xs">
            <div class="bg-regulatory-900 border border-grid p-3 space-y-1.5">
                <span class="text-accent-bright font-bold text-[10px]">TINGKAT 1 &bull; PENCEGAHAN</span>
                <div class="font-bold text-fg">Fire Hazard Inspection</div>
                <p class="text-[11px] text-muted font-body">Identifikasi bahan mudah terbakar, isolasi potensi sumber api, dan inspeksi rutin jalur kabel listrik.</p>
            </div>
            <div class="bg-regulatory-900 border border-grid p-3 space-y-1.5">
                <span class="text-link font-bold text-[10px]">TINGKAT 2 &bull; DETEKSI DINI</span>
                <div class="font-bold text-fg">Alarm &amp; Smoke Sensor</div>
                <p class="text-[11px] text-muted font-body">Pengujian sensor asap, heat detector, dan panel kontrol alarm otomatis terintegrasi.</p>
            </div>
            <div class="bg-regulatory-900 border border-grid p-3 space-y-1.5">
                <span class="text-caution-bright font-bold text-[10px]">TINGKAT 3 &bull; PEMADAMAN AWAL</span>
                <div class="font-bold text-fg">APAR &amp; Hydrant System</div>
                <p class="text-[11px] text-muted font-body">Operasional tabung APAR CO2/Powder, tekanan nozzle hydrant pilar, serta pemeliharaan berkala.</p>
            </div>
            <div class="bg-regulatory-900 border border-grid p-3 space-y-1.5">
                <span class="text-rose-400 font-bold text-[10px]">TINGKAT 4 &bull; EVAKUASI</span>
                <div class="font-bold text-fg">Titik Kumpul &amp; Drill</div>
                <p class="text-[11px] text-muted font-body">Manajemen muster point, pintu darurat bebas hambatan, serta simulasi fire drill berkala.</p>
            </div>
        </div>

    @elseif($archetype === 'env')
        <!-- Environmental Monitoring Workflow -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 font-display text-xs">
            <div class="bg-regulatory-900 border border-grid p-4 space-y-2">
                <div class="text-accent-bright text-[11px] font-bold uppercase tracking-wider">TAHAP 01 &bull; EMISI CEROBONG</div>
                <h4 class="font-bold text-fg">Isokinetic Sampling Teknis</h4>
                <p class="text-muted text-[11px] font-body">Pengambilan sampel partikulat cerobong industri, SO2, NO2, CO, dan opasitas sesuai standar baku mutu KLHK.</p>
            </div>
            <div class="bg-regulatory-900 border border-grid p-4 space-y-2">
                <div class="text-link text-[11px] font-bold uppercase tracking-wider">TAHAP 02 &bull; UJI AMBIEN &amp; BISING</div>
                <h4 class="font-bold text-fg">Pemantauan Lingkungan Kerja</h4>
                <p class="text-muted text-[11px] font-body">Pengukuran kebisingan 24 jam dengan Sound Level Meter, pencahayaan lux meter, dan kualitas udara ambien tapak pabrik.</p>
            </div>
            <div class="bg-regulatory-900 border border-grid p-4 space-y-2">
                <div class="text-caution-bright text-[11px] font-bold uppercase tracking-wider">TAHAP 03 &bull; LAPORAN RESMI</div>
                <h4 class="font-bold text-fg">Sertifikat Uji Akreditasi KAN</h4>
                <p class="text-muted text-[11px] font-body">Analisis laboratorium terakreditasi ISO 17025 / KAN lengkap dengan matriks evaluasi ketaatan izin lingkungan.</p>
            </div>
        </div>

    @elseif($archetype === 'kajian')
        <!-- Kajian Teknis & Studi Kelayakan -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 font-display text-xs">
            <div class="bg-regulatory-900 border border-grid p-3 space-y-1.5">
                <span class="text-link font-bold text-[10px]">STEP 01</span>
                <div class="font-bold text-fg">Scoping &amp; Desk Audit</div>
                <p class="text-[11px] text-muted font-body">Penelaahan dokumen gambar teknis, as-built drawing, layout pabrik, dan riwayat operasional.</p>
            </div>
            <div class="bg-regulatory-900 border border-grid p-3 space-y-1.5">
                <span class="text-link font-bold text-[10px]">STEP 02</span>
                <div class="font-bold text-fg">Site Measurement</div>
                <p class="text-[11px] text-muted font-body">Pengukuran fisik lapangan oleh tim insinyur K3 berlisensi menggunakan instrumen terkalibrasi.</p>
            </div>
            <div class="bg-regulatory-900 border border-grid p-3 space-y-1.5">
                <span class="text-link font-bold text-[10px]">STEP 03</span>
                <div class="font-bold text-fg">Risk Modeling</div>
                <p class="text-[11px] text-muted font-body">Pemodelan konsekuensi bahaya (HAZOP/FMEA/QRA) dan perhitungan batas aman paparan teknis.</p>
            </div>
            <div class="bg-regulatory-900 border border-grid p-3 space-y-1.5">
                <span class="text-accent-bright font-bold text-[10px]">STEP 04</span>
                <div class="font-bold text-fg">Laporan Rekomendasi</div>
                <p class="text-[11px] text-muted font-body">Dokumen kajian komprehensif bertanda tangan Tenaga Ahli untuk pemenuhan dinas terkait.</p>
            </div>
        </div>

    @elseif($archetype === 'jasa')
        <!-- Jasa Teknis & SLF Workflow -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 font-display text-xs">
            <div class="bg-regulatory-900 border border-grid p-3 space-y-1.5">
                <span class="text-caution-bright font-bold text-[10px]">FASE 01</span>
                <div class="font-bold text-fg">Pemeriksaan Struktur</div>
                <p class="text-[11px] text-muted font-body">Uji non-destruktif (Hammer Test / Ultrasonic Pulse Velocity) pada kolom dan balok utama gedung.</p>
            </div>
            <div class="bg-regulatory-900 border border-grid p-3 space-y-1.5">
                <span class="text-caution-bright font-bold text-[10px]">FASE 02</span>
                <div class="font-bold text-fg">Audit MEP &amp; Proteksi</div>
                <p class="text-[11px] text-muted font-body">Riksa uji instalasi listrik, genset, penangkal petir, sprinkler, dan sirkulasi tata udara mekanikal.</p>
            </div>
            <div class="bg-regulatory-900 border border-grid p-3 space-y-1.5">
                <span class="text-caution-bright font-bold text-[10px]">FASE 03</span>
                <div class="font-bold text-fg">Kelaikan Arsitektur</div>
                <p class="text-[11px] text-muted font-body">Verifikasi jalur evakuasi, tangga darurat, aksesibilitas difabel, dan fasilitas sanitasi kerja.</p>
            </div>
            <div class="bg-regulatory-900 border border-grid p-3 space-y-1.5">
                <span class="text-accent-bright font-bold text-[10px]">FASE 04</span>
                <div class="font-bold text-fg">Penerbitan SLF / Sertifikat</div>
                <p class="text-[11px] text-muted font-body">Penyusunan Berita Acara Pemeriksaan dan pengawalan sidang TABG hingga sertifikat SLF terbit resmi.</p>
            </div>
        </div>

    @else
        <!-- Default: K3 Training Competency Pipeline -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 font-display text-xs">
            <div class="bg-regulatory-900 border border-grid p-3.5 space-y-1.5">
                <div class="flex items-center justify-between text-[10px]">
                    <span class="text-accent-bright font-bold">PIPELINE 01</span>
                    <span class="text-dim">KEMNAKER RI</span>
                </div>
                <div class="font-bold text-fg">Pondasi Teori Regulasi</div>
                <p class="text-[11px] text-muted font-body">Kajian mendalam UU 1/1970, standar kepatuhan industri, dan manajemen identifikasi risiko bahaya tempat kerja.</p>
            </div>
            <div class="bg-regulatory-900 border border-grid p-3.5 space-y-1.5">
                <div class="flex items-center justify-between text-[10px]">
                    <span class="text-link font-bold">PIPELINE 02</span>
                    <span class="text-dim">PRAKTEK LAPANGAN</span>
                </div>
                <div class="font-bold text-fg">Praktik Lapangan (PKL)</div>
                <p class="text-[11px] text-muted font-body">Kunjungan praktik kerja nyata di sentra industri mitra, simulasi audit inspeksi, dan observasi langsung.</p>
            </div>
            <div class="bg-regulatory-900 border border-grid p-3.5 space-y-1.5">
                <div class="flex items-center justify-between text-[10px]">
                    <span class="text-caution-bright font-bold">PIPELINE 03</span>
                    <span class="text-dim">EVALUASI</span>
                </div>
                <div class="font-bold text-fg">Seminar &amp; Ujian Evaluasi</div>
                <p class="text-[11px] text-muted font-body">Pemaparan laporan PKL di depan tim evaluator pengawas ketenagakerjaan dan ujian kompetensi standar.</p>
            </div>
            <div class="bg-regulatory-900 border border-grid p-3.5 space-y-1.5">
                <div class="flex items-center justify-between text-[10px]">
                    <span class="text-accent-bright font-bold">PIPELINE 04</span>
                    <span class="text-dim">NASIONAL</span>
                </div>
                <div class="font-bold text-fg">Sertifikat &amp; Lisensi Resmi</div>
                <p class="text-[11px] text-muted font-body">Penerbitan Surat Keputusan Penunjukan (SKP) dan Lisensi K3 sah berlaku nasional di {{ $countCities ?? '500+' }} Kota/Kabupaten.</p>
            </div>
        </div>
    @endif
</div>