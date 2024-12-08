<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Laporan extends Model
{
    use HasFactory;

    protected $fillable = [
        'nomor_tiket',
        'nomor_pengadu',
        'email',
        'nama_lengkap',
        'nik',
        'jenis_kelamin',
        'alamat_lengkap',
        'judul',
        'detail',
        'lokasi',
        'dokumen_pendukung',
        'tanggal_kejadian',
        'status',
        'tanggapan',
        'klasifikasi', // Field baru
        'kategori',    // Field baru
        'disposisi',   // Field baru
        'sumber_pengaduan',
    ];

    protected $casts = [
        'tanggal_kejadian' => 'date',
    ];

    protected $attributes = [
        'tanggapan' => 'Laporan pengaduan Anda dalam proses verifikasi & penelaahan, sesuai ketentuan akan dilakukan dalam 14 (empat belas) hari kerja sejak laporan lengkap diterima.',
    ];

    /**
     * Accessor untuk format tanggal DD/MM/YYYY.
     */
    public function getTanggalKejadianAttribute($value)
    {
        return $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : null;
    }

    /**
     * Mutator untuk format tanggal ke YYYY-MM-DD.
     */
    public function setTanggalKejadianAttribute($value)
    {
        $this->attributes['tanggal_kejadian'] = $value ? \Carbon\Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d') : null;
    }

    /**
     * Mutator untuk jenis_kelamin: Simpan L/P berdasarkan input Laki-laki/Perempuan.
     */
    public function setJenisKelaminAttribute($value)
    {
        $this->attributes['jenis_kelamin'] = $value === 'Laki-laki' ? 'L' : ($value === 'Perempuan' ? 'P' : null);
    }

    /**
     * Accessor untuk jenis_kelamin: Kembalikan Laki-laki/Perempuan.
     */
    public function getJenisKelaminAttribute($value)
    {
        return $value === 'L' ? 'Laki-laki' : ($value === 'P' ? 'Perempuan' : null);
    }

    protected static function boot()
    {
        parent::boot();

        // Automasi kategori dan disposisi saat data dibuat
        static::creating(function ($laporan) {
            if (!empty($laporan->judul)) {
                $result = self::tentukanKategoriDanDeputi($laporan->judul);

                $laporan->kategori = $result['kategori'] ?? 'Lainnya';
                $laporan->disposisi = $result['deputi'] ?? null;
            }
        });

        // Automasi kategori dan disposisi saat data diperbarui
        static::updating(function ($laporan) {
            if (!empty($laporan->judul)) {
                $result = self::tentukanKategoriDanDeputi($laporan->judul);

                $laporan->kategori = $laporan->kategori ?? $result['kategori'] ?? 'Lainnya';
                $laporan->disposisi = $laporan->disposisi ?? $result['deputi'] ?? null;
            }
        });
    }

    // Daftar kata kunci dan kategori
    private static $kategoriKataKunci = [
        'Agama' => ['agama', 'ibadah', 'rumah ibadah', 'masjid', 'gereja', 'penistaan', 'hari besar keagamaan', 'yayasan keagamaan', 'zakat', 'wakaf', 'pesantren', 'haji', 'umroh', 'toleransi', 'santri'],
        'Ekonomi dan Keuangan' => ['ekonomi', 'keuangan', 'uang', 'investasi', 'bank', 'pinjaman', 'kredit', 'tabungan', 'inflasi', 'pinjol', 'utang', 'modal usaha', 'hutang', 'bodong', 'dana', 'asuransi', 'online', 'pajak', 'modal', 'penjaminan', 'pailit'],
        'Kesehatan' => ['kesehatan', 'fasilitas dan pelayanan kesehatan', 'dokter', 'puskesmas', 'obat', 'penyakit', 'vaksin', 'bpjs kesehatan', 'perawat', 'stunting', 'rumah sakit', 'organisasi profesi tenaga kesehatan', 'malpraktek', 'pasien', 'sehat', 'sakit'],
        'Kesetaraan Gender dan Sosial Inklusif' => ['gender', 'kesetaraan', 'inklusi', 'organisasi wanita', 'difabel', 'perempuan', 'lgbt', 'waria', 'hak', 'gay', 'anak', 'ketahanan keluarga'],
        'Ketentraman, Ketertiban Umum, dan Perlindungan Masyarakat' => ['ketentraman', 'ketertiban', 'perlindungan', 'keamanan', 'keributan', 'masyarakat', 'konflik', 'kerusuhan', 'kriminalitas', 'kekerasan'],
        'Lingkungan Hidup dan Kehutanan' => ['lingkungan', 'hutan', 'polusi','sampah','air','pencemaran','deforestasi','kehutanan','reboisasi','limbah','banjir','erosi','kerusakan','ekosistem','abrasi','udara','penghijauan','kebakaran','perhutanan sosial','sungai','tanah','lahan','sawit','ulayat','adat'],
        'Pekerjaan Umum, Perumahan, dan Penataan Ruang' => ['pekerjaan umum', 'infrastruktur', 'jalan','jembatan','bangunan','penataan ruang','pemukiman','gedung','rtrw','bendungan','sertifikat','tanah','shm','rumah','perkebunan','irigasi','ajb','ptsl','hgb','hgu','tora','agraria','shp','sertifikat','psn','mbr','rusun','apartemen','adat','sewa'],
        'Pembangunan Desa, Daerah Tertinggal, Daerah Perbatasan, dan Transmigrasi' => ['desa','pembangunan','daerah tertinggal','transmigrasi','pedesaan','pengembangan daerah','daerah 3T','dana desa','pembangunan desa'],
        'Pendidikan, Kepemudaan, Kebudayaan, dan Olahraga' => ['pendidikan','sekolah','guru','murid','sekolah inklusif','kebudayaan','olahraga','universitas','pelajaran','beasiswa','buku','modul','tenaga pendidikan','ujian','jambore','pramuka','ijazah','kurikulum','prestasi siswa','prestasi guru','dosen','penerimaan siswa baru','pemagangan','zonasi'],
        'Pertanian dan Peternakan' => ['pertanian','peternakan','tanaman','pupuk','petani','ternak','hasil panen','sapi','ayam','bibit','lahan','teknologi pertanian','produktifitas','kesejahteraan petani','nelayan','perkapalan','kesejahteraaan nelayan','kapal ikan','tambak','daging sapi','perkebunan','padi','anak buah kapal','abk','pakan ikan','KUR pertanian','KUR perikanan'],
        'Politik dan Hukum' => ['politik','hukum','peraturan','pemilu','korupsi','regulasi','pengadilan','keadilan','legislasi','partai politik','putusan pengadilan','mafia hukum','lembaga peradilan','pertanahan','parpol'],
        'Politisasi ASN' => ['asn','politisasi asn','netralitas asn','kampanye','pegawai negeri','pns','kode etik asn','manajemen asn','pengangkatan p3k','gaji asn','honorer','mutasi','penyalahgunaan wewenang','tes cpns'],
        'Sosial dan Kesejahteraan' => ['sosial','kesejahteraan','bansos','kesejahteraan sosial','penanggulangan kemiskinan','keluarga miskin','lansia','difabel','kartu lansia','disabilitas','tunggakan spp','tebus ijazah','baznas','miskin','bantuan sosial','pkh','dtks','blt','bpjs'],
        'Energi dan Sumber Daya Alam' => ['energi','minyak','gas','pertambangan','sumber daya alam','sda','listrik','pembangkit','bbm','pln','ebt','smelter','hilirisasi'],
        'Kekerasan di Satuan Pendidikan (Sekolah, Kampus, Lembaga Khusus)' => ['kekerasan','bullying','pelecehan','lembaga diklat','kampus','sekolah','pendidikan','bully','dosen','mahasiswa','siswa'],
        'Kependudukan dan KB' => ['penduduk','kependudukan','ktp','nik','keluarga berencana','domisili','data','dukcapil','kartu keluarga','alat kontasepsi','pernikahan'],
        'Ketenagakerjaan' => ['pekerja','migran','tenaga kerja','buruh','karyawan','phk','upah','gaji','tunjangan','pensiun','jaminan kerja','outsourcing','hubungan industrial','kesempatan kerja','cuti','bpjs ketenagakerjaan','serikat pekerja','lowongan','pengangguran','pecat'],
        'Netralitas ASN' => ['asn','pns','netralitas','politik','pegawai negeri','pilkada','kampanye'],
        'Pemulihan Ekonomi Nasional' => ['pemulihan','ekonomi','nasional','program','recovery','dampak pandemi','modal usaha'],
        'Pencegahan dan Pemberantasan Penyalahgunaan dan Peredaran Gelap Narkotika dan Prekursor Narkotika (P4GN)' => ['narkoba','p4gn','peredaran','penyalahgunaan','narkotika','obat'],
        'Mudik' => ['mudik','peniadaan','larangan','lebaran','transportasi','ppkm','tahun baru','mudik gratis','angkutan','lalu lintas','harga tiket','macet','tiket','libur','rest area','cuti','kecelakaan','natal','tol','tuslah','diskon','online'],
        'Perairan' => ['air','laut','sungai','bendungan','pelabuhan','irigasi','IPAL','keramba jaring apung','ikan','perikanan','budidaya','kualitas air','kja','udang','tambak','ekosistem'],
        'Perhubungan' => ['transportasi','angkutan','jalan','kendaraan','kereta','bus','pesawat','ojek online','ojek','mobil','motor','kapal','terminal','lrt','mrt','bandar udara','pelabuhan','stasiun','halte','tol','logistik','paket','barang','surat','asuransi','tod','parkir','sertifikasi','psn','tiket'],
        'Perlindungan Konsumen' => ['konsumen','perlindungan','penipuan','online','jual','ecommerce','bajakan','shopping','belanja','beli','produk','harga'],
        'Teknologi Informasi dan Komunikasi' => ['teknologi','informasi','komunikasi','internet','digital','aplikasi','telekomunikasi','bts','literasi','hardware','software','data pribadi','data','jaringan','sistem','AI','5G','sambungan','satelit','keamanan','cloud','frekuensi'],
        'Topik Khusus' => ['khusus','topik','isu tertentu','spesifik','pajak'],
        'Bantuan Masyarakat' => ['tunggakan sekolah','modal usaha','bantuan','tunggakan spp','proposal','tunggakan','proposal masjid','tebus ijazah','ambil ijazah','gereja','proposal desa','tunggak','tunggakan','spp'],
        'Luar Negeri' => ['imigran','kekonsuleran','pengungsi','migran','repatriasi','pencari suaka','tppo','deportan'],
        'Pariwisata dan Ekonomi Kreatif' => ['visa','turis','turis lokal','turis asing','tiket pesawat','tiket masuk','wisata','akomodasi','hotel','wisatawan','pemandu wisata','souvenir','budaya','tari','performance','konser','musik','ihburan','film','entertainment','penyanyi','penari','pelawak','komedi','lagu','kreatif','okupansi','destinasi','desa wisata','cagar budaya','penulis','lukisan','anyaman','tenun','batik','atraksi','hospitaliti','trip','travel','festival'],
        'Pemberdayaan Masyarakat, Koperasi, dan UMKM' => ['umkm','modal usaha','pemberdayaan masyarakat','kur','koperasi','kredit macet','jaminan kur','usaha kecil','usaha mikro','usaha menengah','blacklist bank'],
        'Industri dan Perdagangan' => ['barang','online','beli','dagang','jual','ekspor','impor','jasa','produsen','distributor','harga','toko','koperasi','pemasok','industri','tekstil','otomotif','konsumen','mesin','gudang','logistik','industri pengolahan','restoran','rumah makan','warung','pabrik','manufaktur','bahan baku','pasar','retail','supermarket','usaha','grosir','harga','bahan pokok','monopoli','kuota ekspor','dumping','e-commerce','bea masuk','profit','komoditi','komoditas','produk'],
        'Penanggulangan Bencana' => ['gempa bumi','gunung meletus','banjir','tsunami','tanah longsor','relokasi','hunian tetap','hunian sementara','bnpb','rehabilitasi','rekonstruksi','bantuan korban bencana','pengungsi','bencana','bpbd','dana siap pakai','dsp','early warning system','kebakaran hutan dan lahan','pasca bencana','perubahan iklim','dana hibah','erupsi','mitigasi bencana','tanggap darurat','desa tangguh bencana','logistik bantuan','kekeringan','bencana non alam','pra bencana','krisis air'],
        'Lainnya' => [],
    ];

    private static $kategoriDeputi = [
        'deputi_1' => ['Ekonomi dan Keuangan', 'Lingkungan Hidup dan Kehutanan', 'Pekerjaan Umum, Perumahan, dan Penataan Ruang', 'Pertanian dan Peternakan', 'Pemulihan Ekonomi Nasional', 'Energi dan Sumber Daya Alam', 'Mudik', 'Perairan', 'Perhubungan', 'Teknologi Informasi dan Komunikasi', 'Perlindungan Konsumen', 'Pariwisata dan Ekonomi Kreatif', 'Industri dan Perdagangan'],
        'deputi_2' => ['Kesehatan', 'Penanggulangan Bencana', 'Pendidikan, Kepemudaan, Kebudayaan, dan Olahraga', 'Sosial dan Kesejahteraan', 'Ketenagakerjaan', 'Kesetaraan Gender dan Sosial Inklusif', 'Pembangunan Desa, Daerah Tertinggal, Daerah Perbatasan, dan Transmigrasi', 'Kependudukan dan KB', 'Agama', 'Pemberdayaan Masyarakat, Koperasi, dan UMKM', 'Kekerasan di Satuan Pendidikan (Sekolah, Kampus, Lembaga Khusus)'],
        'deputi_3' => ['Ketentraman, Ketertiban Umum, dan Perlindungan Masyarakat','Politik dan Hukum', 'Politisasi ASN', 'Manajemen ASN', 'Netralitas ASN', 'Pencegahan dan Pemberantasan Penyalahgunaan dan Peredaran Gelap Narkotika dan Prekursor Narkotika (P4GN)', 'Wawasan Kebangsaan', 'Luar Negeri'],
        'deputi_4' => ['Topik Khusus', 'Topik Lainnya', 'Bantuan Masyarakat'],
    ];

    public static function tentukanKategoriDanDeputi($judul)
    {
        $judul = strtolower($judul);
        $kategoriScores = [];

        // Hitung skor untuk kategori berdasarkan kata kunci
        foreach (self::$kategoriKataKunci as $kategori => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) {
                if (stripos($judul, $keyword) !== false) {
                    $score++;
                }
            }
            $kategoriScores[$kategori] = $score;
        }

        // Tentukan kategori dengan skor tertinggi
        $kategori = 'Lainnya';
        $maxScore = max($kategoriScores);
        if ($maxScore > 0) {
            $kategori = array_search($maxScore, $kategoriScores);
        }

        // Tentukan deputi berdasarkan kategori
        $deputi = null;
        foreach (self::$kategoriDeputi as $key => $categories) {
            if (in_array($kategori, $categories)) {
                $deputi = $key;
                break;
            }
        }

        return ['kategori' => $kategori, 'deputi' => $deputi];
    }
}