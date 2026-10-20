<?php

namespace Database\Seeders;

use App\Models\Content;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class ContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            // laporan-tahunan
            [
                'title' => 'Laporan Tahunan WALHI Jawa Barat 2025',
                'slug' => 'laporan-tahunan-2025',
                'body' => '{"subtitle":"Melawan Eksploitasi, Membangun Kedaulatan Rakyat","stats":["289 Kasus Kerusakan Lingkungan Ditangani (Periode 2025)","45 Advokasi Hukum Didampingi","18 Kampanye Besar Dilaksanakan","12.450 Masyarakat Terlibat Langsung"],"pages":"156 Halaman","downloads":"3.2K Downloads"}',
                'tags' => 'laporan, tahunan, 2025',
                'status' => 'published',
                'image_url' => 'documents/laporan-tahunan-walhi-jabar-2025.pdf',
                'publish_date' => '2025-12-31',
                'category' => 'laporan-tahunan',
            ],
            [
                'title' => 'Laporan Tahunan WALHI Jawa Barat 2024',
                'slug' => 'laporan-tahunan-2024',
                'body' => '{"subtitle":"Tahun Perjuangan Keadilan Ekologis","stats":["267 Kasus Ditangani (Periode 2024)","38 Advokasi Hukum","15 Kampanye Dilaksanakan","10.200 Masyarakat Terlibat"],"pages":"142 Halaman","downloads":"4.1K Downloads"}',
                'tags' => 'laporan, tahunan, 2024',
                'status' => 'published',
                'image_url' => 'documents/laporan-tahunan-walhi-jabar-2024.pdf',
                'publish_date' => '2024-12-31',
                'category' => 'laporan-tahunan',
            ],
            [
                'title' => 'Laporan Tahunan WALHI Jawa Barat 2023',
                'slug' => 'laporan-tahunan-2023',
                'body' => '{"subtitle":"Resistensi Rakyat terhadap Krisis Ekologis","stats":["241 Kasus Ditangani (Periode 2023)","32 Advokasi Hukum","12 Kampanye Dilaksanakan","8.500 Masyarakat Terlibat"],"pages":"128 Halaman","downloads":"2.8K Downloads"}',
                'tags' => 'laporan, tahunan, 2023',
                'status' => 'published',
                'image_url' => 'documents/laporan-tahunan-walhi-jabar-2023.pdf',
                'publish_date' => '2023-12-31',
                'category' => 'laporan-tahunan',
            ],
            // regulasi
            [
                'title' => 'Undang-Undang No. 32 Tahun 2009 tentang Perlindungan dan Pengelolaan Lingkungan Hidup',
                'slug' => 'uu-32-2009-pplh',
                'body' => 'Undang-Undang Republik Indonesia Nomor 32 Tahun 2009 mengatur kerangka hukum mendasar perlindungan lingkungan hidup, instrumen pencegahan pencemaran, baku mutu lingkungan, perizinan amdal, serta hak masyarakat atas lingkungan yang baik dan sehat termasuk perlindungan anti-SLAPP bagi pejuang lingkungan.',
                'tags' => 'undang-undang, Pemerintah RI, berlaku',
                'status' => 'published',
                'image_url' => 'documents/uu-32-2009-perlindungan-pengelolaan-lingkungan-hidup.pdf',
                'publish_date' => '2009-10-03',
                'category' => 'regulasi',
            ],
            [
                'title' => 'Peraturan Pemerintah No. 22 Tahun 2021 tentang Penyelenggaraan Perlindungan dan Pengelolaan Lingkungan Hidup',
                'slug' => 'pp-22-2021-pplh',
                'body' => 'Peraturan Pemerintah turunan implementasi persetujuan lingkungan, baku mutu air, udara, pengelolaan limbah B3, dan pemulihan fungsi lingkungan hidup pasca aktivitas industri dan ekstraksi sumber daya alam.',
                'tags' => 'peraturan pemerintah, Pemerintah RI, berlaku',
                'status' => 'published',
                'image_url' => 'documents/pp-22-2021-penyelenggaraan-perlindungan-pengelolaan-lh.pdf',
                'publish_date' => '2021-02-02',
                'category' => 'regulasi',
            ],
            [
                'title' => 'Peraturan Daerah Provinsi Jawa Barat No. 1 Tahun 2012 tentang Pengelolaan Lingkungan Hidup',
                'slug' => 'perda-jabar-1-2012-plh',
                'body' => 'Peraturan Daerah Provinsi Jawa Barat tentang pengelolaan lingkungan hidup, pencegahan dan penanggulangan pencemaran serta pemulihan kerusakan lingkungan hidup di wilayah Jawa Barat.',
                'tags' => 'peraturan daerah, Pemprov Jawa Barat, berlaku',
                'status' => 'published',
                'image_url' => 'documents/perda-jabar-1-2012-pengelolaan-lingkungan-hidup.pdf',
                'publish_date' => '2012-04-16',
                'category' => 'regulasi',
            ],
            [
                'title' => 'Peraturan Daerah Provinsi Jawa Barat No. 2 Tahun 2016 tentang Pedoman Pengendalian Kawasan Bandung Utara',
                'slug' => 'perda-jabar-2-2016-kbu',
                'body' => 'Peraturan Daerah mengenai pedoman pengendalian Kawasan Bandung Utara (KBU) sebagai kawasan resapan air strategis lintas kabupaten/kota di Jawa Barat guna mencegah bencana banjir, longsor, dan alih fungsi lahan lindung.',
                'tags' => 'peraturan daerah, Pemprov Jawa Barat, berlaku',
                'status' => 'published',
                'image_url' => 'documents/perda-jabar-2-2016-pedoman-kbu.pdf',
                'publish_date' => '2016-06-20',
                'category' => 'regulasi',
            ],
            [
                'title' => 'Peraturan Menteri LHK No. 4 Tahun 2021 tentang Usaha dan/atau Kegiatan Wajib AMDAL',
                'slug' => 'permen-lhk-4-2021-wajib-amdal',
                'body' => 'Peraturan Menteri Lingkungan Hidup dan Kehutanan yang menetapkan daftar rencana usaha dan/atau kegiatan yang wajib memiliki analisis mengenai dampak lingkungan hidup (AMDAL), UKL-UPL, atau SPPL.',
                'tags' => 'peraturan menteri, Kementerian Lingkungan Hidup dan Kehutanan, berlaku',
                'status' => 'published',
                'image_url' => 'documents/permen-lhk-p4-2021-daftar-usaha-wajib-amdal.pdf',
                'publish_date' => '2021-03-29',
                'category' => 'regulasi',
            ],
            [
                'title' => 'Keputusan Menteri ESDM No. 96.K/MB.01/MEM.B/2020 tentang Wilayah Pertambangan Jawa Barat',
                'slug' => 'kepmen-esdm-96-2020-pertambangan-jabar',
                'body' => 'Keputusan Menteri ESDM tentang penetapan wilayah pertambangan di Provinsi Jawa Barat, menjadi rujukan hukum monitoring izin tambang galian C dan mineral logam WALHI Jabar.',
                'tags' => 'keputusan menteri, Kementerian ESDM, berlaku',
                'status' => 'published',
                'image_url' => 'documents/kepmen-esdm-96-2020-wilayah-pertambangan-jabar.pdf',
                'publish_date' => '2020-04-15',
                'category' => 'regulasi',
            ],
            // infografis with documents
            [
                'title' => 'Memadamkan Bara: Kepak JATAYU Menghalau PLTU di Desa Mekarsari Indramayu',
                'slug' => 'memadamkan-bara',
                'body' => "Dokumentasi dan infografis advokasi warga Indramayu yang tergabung dalam Jaringan Tanpa Asap Batubara Indramayu (JATAYU) dalam menolak rencana pembangunan PLTU Batubara Indramayu 1 x 1000 MW di Desa Mekarsari, Kecamatan Patrol, Kabupaten Indramayu.\n\nLaporan ini memetakan dampak sosial-ekologis, hilangnya ruang penghidupan petani dan nelayan, serta kronologi perjuangan rakyat mempertahankan ruang hidup dari jeratan energi kotor.",
                'tags' => 'infografis, jatayu, pltu indramayu',
                'status' => 'published',
                'image_url' => 'documents/memadamkan-bara-2022.pdf',
                'publish_date' => '2023-07-15',
                'category' => 'infografis',
            ],
            [
                'title' => 'Jawa Barat Dalam Ancaman Solusi Palsu Energi',
                'slug' => 'jawa-barat-dalam-ancaman-solusi-palsu-energi',
                'body' => "Infografis dan kertas posisi Koalisi KUTUB (Koalisi Bersihkan Masa Depan) bersama WALHI Jawa Barat mengenai ancaman co-firing biomassa pada PLTU batubara di Jawa Barat.\n\nPraktik co-firing dinilai sebagai solusi palsu transisi energi yang memperpanjang usia operasional PLTU, memicu deforestasi skala luas untuk pemenuhan pasokan biomassa kayu, serta tidak menurunkan emisi secara signifikan.",
                'tags' => 'infografis, co-firing, transisi energi',
                'status' => 'published',
                'image_url' => 'documents/kertas-posisi-co-firing-koalisi-kutub.pdf',
                'publish_date' => '2023-03-20',
                'category' => 'infografis',
            ],
            [
                'title' => 'Potret Kelam Investasi Energi Kotor',
                'slug' => 'potret-kelam-investasi-energi-kotor',
                'body' => 'Infografis hasil investigasi WALHI Jawa Barat mengenai potret kelam investasi energi kotor di wilayah pesisir Jawa Barat, dampak kesehatan akibat emisi cerobong PLTU, serta dokumentasi perlawanan komunitas lokal dalam mempertahankan ruang hidup dan lingkungan sehat.',
                'tags' => 'infografis, energi kotor, investigasi',
                'status' => 'published',
                'image_url' => 'documents/potret-kelam-investasi-energi-kotor.pdf',
                'publish_date' => '2021-08-10',
                'category' => 'infografis',
            ],
            // sejarah
            [
                'title' => 'Sejarah WALHI Jawa Barat',
                'slug' => 'sejarah',
                'body' => 'WALHI Jawa Barat berdiri pada tahun 1990 sebagai respons atas meningkatnya konflik lingkungan...',
                'tags' => 'sejarah',
                'status' => 'published',
                'image_url' => '',
                'publish_date' => '1990-01-01',
                'category' => 'sejarah',
            ],
            // visi-misi
            [
                'title' => 'Visi dan Misi WALHI Jabar',
                'slug' => 'visi-misi',
                'body' => 'Visi: Terwujudnya tatanan sosial, ekonomi, dan politik yang adil serta demokratis yang menjamin hak rakyat atas lingkungan hidup...',
                'tags' => 'visi, misi',
                'status' => 'published',
                'image_url' => '',
                'publish_date' => '2024-01-01',
                'category' => 'visi-misi',
            ],
            // kontak
            [
                'title' => 'Informasi Kontak WALHI Jabar',
                'slug' => 'kontak',
                'body' => "alamat: Jl. Simponi No.29, Turangga, Kec. Lengkong, Kota Bandung, Jawa Barat 40264\nwhatsapp: +62 821-1982-1159\nemail: walhijabar@gmail.com\nfacebook: https://facebook.com/walhi.jabar\ninstagram: https://instagram.com/walhi.jabar\nyoutube: https://www.youtube.com/@walhijabar",
                'tags' => 'kontak',
                'status' => 'published',
                'image_url' => '',
                'publish_date' => '2024-01-01',
                'category' => 'kontak',
            ],
            // statistik
            [
                'title' => 'Kasus Dampingan (Akumulatif)',
                'slug' => 'kasus-ditangani',
                'body' => '2,847',
                'tags' => 'Icon-10.svg',
                'status' => 'published',
                'image_url' => '',
                'publish_date' => '2025-01-01',
                'category' => 'statistik',
            ],
            [
                'title' => 'Keluarga Terdampak',
                'slug' => 'keluarga-terdampak',
                'body' => '15,000+',
                'tags' => 'Icon-11.svg',
                'status' => 'published',
                'image_url' => '',
                'publish_date' => '2025-01-01',
                'category' => 'statistik',
            ],
            [
                'title' => 'Tingkat Keberhasilan',
                'slug' => 'tingkat-keberhasilan',
                'body' => '85%',
                'tags' => 'Icon-12.svg',
                'status' => 'published',
                'image_url' => '',
                'publish_date' => '2025-01-01',
                'category' => 'statistik',
            ],
            [
                'title' => 'Laporan Investigasi',
                'slug' => 'laporan-investigasi',
                'body' => '150+',
                'tags' => 'Icon-13.svg',
                'status' => 'published',
                'image_url' => '',
                'publish_date' => '2025-01-01',
                'category' => 'statistik',
            ],
            // isu-kritis
            [
                'title' => 'Pertambangan Ilegal',
                'slug' => 'pertambangan-ilegal-isu',
                'body' => "Aktivitas pertambangan tanpa izin (PETI) dan penambangan pasir/batu galian C ilegal di Jawa Barat telah mencapai lebih dari 200 titik yang tersebar di wilayah Bogor, Sukabumi, Cianjur, Tasikmalaya, hingga kawasan kaki Gunung Halimun Salak dan Gunung Ciremai.\n\nKerusakan yang ditimbulkan meliputi hilangnya tutupan vegetasi hutan lindung, pendangkalan dan sedimentasi aliran sungai, ancaman bencana longsor bagi permukiman warga di sekitar lereng bukit, serta konflik sosial berkepanjangan antara masyarakat tapak dengan pemodal ilegal.\n\nWALHI Jawa Barat secara konsisten mendesak aparat penegak hukum dan Kementerian ESDM untuk mencabut perlindungan terhadap pemodal tambang ilegal, memulihkan ekosistem yang hancur, serta melindungi hak warga atas lingkungan hidup yang bersih, sehat, dan bebas dari teror kriminalisasi.",
                'tags' => '200+ Titik',
                'status' => 'published',
                'image_url' => '/assets/images/resources/causes-1-1.jpg',
                'publish_date' => '2025-01-01',
                'category' => 'isu-kritis',
            ],
            [
                'title' => 'Deforestasi',
                'slug' => 'deforestasi-isu',
                'body' => "Laju kehilangan tutupan pohon di Jawa Barat diperkirakan mencapai 15.000 hektar per tahun akibat ekspansi perkebunan monokultur skala besar, konversi kawasan hutan menjadi kawasan pariwisata komersil, serta pembangunan infrastruktur yang mengorbankan koridor hidrologis dan habitat satwa endemik.\n\nDeforestasi di kawasan hulu daerah aliran sungai (DAS) seperti Citarum, Cimanuk, dan Cisadane telah menyebabkan turunnya kemampuan tanah menyerap air hujan, yang secara langsung melipatgandakan intensitas banjir bandang dan longsor setiap musim penghujan tiba.\n\nWALHI Jawa Barat mendorong pengakuan Wilayah Kelola Rakyat (WKR) dan restorasi hutan berbasis kearifan lokal komunitas adat serta petani tapak sebagai jalan utama menghentikan deforestasi dan memulihkan daya dukung lingkungan Jawa Barat.",
                'tags' => '15.000 Ha/Tahun',
                'status' => 'published',
                'image_url' => '/assets/images/resources/causes-1-2.jpg',
                'publish_date' => '2025-01-01',
                'category' => 'isu-kritis',
            ],
            [
                'title' => 'Pencemaran Sungai',
                'slug' => 'pencemaran-sungai-isu',
                'body' => "Sebanyak 75% Daerah Aliran Sungai (DAS) utama di Jawa Barat berstatus tercemar berat hingga sedang. Sungai Citarum, Cimanuk, dan Cilamaya terus menerima buangan limbah cair industri tekstil, logam berat, serta timbulan sampah domestik tanpa pengolahan yang memadai.\n\nPencemaran ini tidak hanya mematikan biota perairan dan merusak ekosistem persawahan warga yang mengandalkan irigasi sungai, tetapi juga menimbulkan ancaman krisis kesehatan kronis bagi jutaan warga yang terpaksa menggunakan air tercemar untuk kebutuhan hidup harian.\n\nWALHI Jawa Barat menuntut penegakan hukum pidana lingkungan tanpa pandang bulu terhadap korporasi pencemar, kewajiban pemulihan sungai oleh pelaku industri, serta transparansi pengawasan berkala dari instansi pemerintah terkait.",
                'tags' => '75% Tercemar',
                'status' => 'published',
                'image_url' => '/assets/images/resources/causes-1-3.jpg',
                'publish_date' => '2025-01-01',
                'category' => 'isu-kritis',
            ],
            [
                'title' => 'Konflik Agraria',
                'slug' => 'konflik-agraria-isu',
                'body' => "Lebih dari 2.500 kasus konflik agraria dan ketimpangan struktur penguasaan lahan di Jawa Barat belum terselesaikan secara adil. Ribuan keluarga petani penggarap dan masyarakat adat terus berhadapan dengan klaim sepihak kawasan hutan negara, izin konsesi perusahaan kehutanan, serta penggusuran oleh proyek-proyek strategis.\n\nKetimpangan penguasaan tanah ini memiskinkan masyarakat pedesaan dan merampas kedaulatan pangan warga lokal, yang kerap kali diiringi praktik kekerasan, intimidasi aparat, serta kriminalisasi terhadap pejuang hak atas tanah.\n\nWALHI Jawa Barat mendampingi perjuangan komunitas petani di tapak untuk merebut kembali tanah ulayat dan ruang hidup mereka melalui skema reforma agraria sejati yang berpusat pada kedaulatan rakyat, bukan kepentingan korporasi.",
                'tags' => '2.500+ Kasus',
                'status' => 'published',
                'image_url' => '/assets/images/resources/causes-1-4.jpg',
                'publish_date' => '2025-01-01',
                'category' => 'isu-kritis',
            ],
            [
                'title' => 'Krisis Iklim',
                'slug' => 'krisis-iklim-isu',
                'body' => "Jawa Barat berada di garis depan kerentanan krisis iklim global. Anomali cuaca ekstrem, gelombang panas, kekeringan berkepanjangan pada lahan pertanian, hingga kenaikan muka air laut yang menenggelamkan permukiman pesisir di Muara Gembong Bekasi dan Karawang semakin sering terjadi.\n\nProyeksi kenaikan temperatur global yang melampaui batas aman 1,5°C hingga mencapai +2,5°C akan melumpuhkan sektor pertanian pangan, merusak keanekaragaman hayati dataran tinggi, dan meningkatkan frekuensi bencana hidrometeorologi di 27 kabupaten/kota se-Jawa Barat.\n\nWALHI Jawa Barat mendesak pemerintah untuk mendeklarasikan darurat iklim, menghentikan pembangunan pembangkit berbasis energi fosil batubara, serta mengalokasikan anggaran mitigasi dan adaptasi iklim yang adil bagi komunitas rentan.",
                'tags' => '+2.5°C Target',
                'status' => 'published',
                'image_url' => '/assets/images/resources/causes-1-5.jpg',
                'publish_date' => '2025-01-01',
                'category' => 'isu-kritis',
            ],
            [
                'title' => 'Energi Kotor & PLTU',
                'slug' => 'energi-kotor-pltu-isu',
                'body' => "Konsentrasi PLTU batubara raksasa di sepanjang pesisir utara dan selatan Jawa Barat—seperti PLTU Indramayu, PLTU Cirebon, dan PLTGU Karawang—merupakan sumber polusi sulfur dioksida (SO2), nitrogen oksida (NOx), dan partikel PM2.5 yang meracuni udara.\n\nNelayan tradisional kehilangan wilayah tangkap akibat pembuangan air bahang panas dan limbah abu batubara (fly ash dan bottom ash), sementara ribuan warga sekitar menderita gangguan pernapasan kronis (ISPA) dan kehilangan mata pencaharian.\n\nWALHI Jawa Barat bersama koalisi masyarakat tapak seperti JATAYU konsisten menuntut penghentian operasi dini PLTU batubara, pembatalan ekspansi pembangkit energi kotor baru, dan transisi energi bersih yang berkeadilan tanpa solusi palsu seperti co-firing biomassa.",
                'tags' => '5 PLTU Raksasa',
                'status' => 'published',
                'image_url' => '/assets/images/resources/causes-1-6.jpg',
                'publish_date' => '2025-01-01',
                'category' => 'isu-kritis',
            ],
            // infografis with documents
            [
                'title' => 'Memadamkan Bara: Kepak JATAYU Menghalau PLTU di Desa Mekarsari Indramayu',
                'slug' => 'memadamkan-bara',
                'body' => "Dokumentasi dan infografis advokasi warga Indramayu yang tergabung dalam Jaringan Tanpa Asap Batubara Indramayu (JATAYU) dalam menolak rencana pembangunan PLTU Batubara Indramayu 1 x 1000 MW di Desa Mekarsari, Kecamatan Patrol, Kabupaten Indramayu.\n\nLaporan ini memetakan dampak sosial-ekologis, hilangnya ruang penghidupan petani dan nelayan, serta kronologi perjuangan rakyat mempertahankan ruang hidup dari jeratan energi kotor.",
                'tags' => 'infografis, jatayu, pltu indramayu',
                'status' => 'published',
                'image_url' => 'documents/memadamkan-bara-2022.pdf',
                'publish_date' => '2023-07-15',
                'category' => 'infografis',
            ],
            [
                'title' => 'Jawa Barat Dalam Ancaman Solusi Palsu Energi',
                'slug' => 'jawa-barat-dalam-ancaman-solusi-palsu-energi',
                'body' => "Infografis dan kertas posisi Koalisi KUTUB (Koalisi Bersihkan Masa Depan) bersama WALHI Jawa Barat mengenai ancaman co-firing biomassa pada PLTU batubara di Jawa Barat.\n\nPraktik co-firing dinilai sebagai solusi palsu transisi energi yang memperpanjang usia operasional PLTU, memicu deforestasi skala luas untuk pemenuhan pasokan biomassa kayu, serta tidak menurunkan emisi secara signifikan.",
                'tags' => 'infografis, co-firing, transisi energi',
                'status' => 'published',
                'image_url' => 'documents/kertas-posisi-co-firing-koalisi-kutub.pdf',
                'publish_date' => '2023-03-20',
                'category' => 'infografis',
            ],
            [
                'title' => 'Potret Kelam Investasi Energi Kotor',
                'slug' => 'potret-kelam-investasi-energi-kotor',
                'body' => 'Infografis hasil investigasi WALHI Jawa Barat mengenai potret kelam investasi energi kotor di wilayah pesisir Jawa Barat, dampak kesehatan akibat emisi cerobong PLTU, serta dokumentasi perlawanan komunitas lokal dalam mempertahankan ruang hidup dan lingkungan sehat.',
                'tags' => 'infografis, energi kotor, investigasi',
                'status' => 'published',
                'image_url' => 'documents/potret-kelam-investasi-energi-kotor.pdf',
                'publish_date' => '2021-08-10',
                'category' => 'infografis',
            ],
        ];

        foreach ($data as $item) {
            Content::updateOrCreate(
                ['slug' => $item['slug']],
                $item
            );

            if (! empty($item['image_url']) && str_starts_with($item['image_url'], 'documents/')) {
                if (! Storage::disk('local')->exists($item['image_url'])) {
                    Storage::disk('local')->put(
                        $item['image_url'],
                        "%PDF-1.4\n% WALHI Jabar Official Document: ".$item['title']."\n%%EOF"
                    );
                }
            }
        }
    }
}
