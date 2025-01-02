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
        'dokumen_ktp', // Tambahkan kolom baru di sini
        'dokumen_kk', // Tambahkan kolom dokumen KK
        'dokumen_skuasa', // Tambahkan kolom baru di sini
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

    // Deklarasi properti
    private static $kategoriKataKunci = [
        // Tambahkan semua kategori lama dan baru di sini
        'Agama' => ['agama', 'ibadah', 'rumah ibadah', 'masjid', 'gereja', 'penistaan', 'hari besar keagamaan', 'yayasan keagamaan', 'zakat', 'wakaf', 'pesantren', 'haji', 'umroh', 'toleransi', 'santri', 'iman', 'amil', 'p3ukdk'],
        'Corona Virus' => ['covid', 'corona', 'pandemi', 'vaksin', 'omicron', 'lockdown', 'ppkm', 'varian'],
        'Ekonomi dan Keuangan' => ['ekonomi', 'keuangan', 'uang', 'investasi', 'bank', 'pinjaman', 'kredit', 'tabungan', 'inflasi', 'pinjol', 'utang', 'modal usaha', 'hutang', 'bodong', 'dana', 'asuransi', 'online', 'pajak', 'modal', 'penjaminan', 'pailit', 'angsuran', 'ojk', 'rentenir', 'tagihan', 'bi checking', 'restru'],
        'Kesehatan' => ['kesehatan', 'fasilitas dan pelayanan kesehatan', 'dokter', 'puskesmas', 'obat', 'penyakit', 'vaksin', 'bpjs kesehatan', 'perawat', 'stunting', 'rumah sakit', 'organisasi profesi tenaga kesehatan', 'malpraktek', 'pasien', 'sehat', 'sakit', 'stunting', 'gizi', 'rsud', 'persalinan', 'posyandu', 'akupuntur'],
        'Kesetaraan Gender dan Sosial Inklusif' => ['gender', 'kesetaraan', 'inklusi', 'organisasi wanita', 'difabel', 'perempuan', 'lgbt', 'waria', 'hak', 'gay', 'anak', 'ketahanan keluarga'],
        'Ketentraman, Ketertiban Umum, dan Perlindungan Masyarakat' => ['ketentraman', 'ketertiban', 'perlindungan', 'keamanan', 'keributan', 'masyarakat', 'konflik', 'kerusuhan', 'kriminalitas', 'kekerasan', 'bising', 'pkl', 'liar', 'rokok'],
        'Lingkungan Hidup dan Kehutanan' => ['lingkungan', 'hutan', 'polusi','sampah','air','pencemaran','deforestasi','kehutanan','reboisasi','limbah','banjir','erosi','kerusakan','ekosistem','abrasi','udara','penghijauan','kebakaran','perhutanan sosial','sungai','tanah','lahan','sawit','ulayat','adat'],
        'Pekerjaan Umum dan Penataan Ruang' => ['pekerjaan umum', 'infrastruktur', 'jalan','jembatan','bangunan','penataan ruang','pemukiman','gedung','rtrw','bendungan','sertifikat','tanah','shm','rumah','perkebunan','irigasi','ajb','ptsl','hgb','hgu','tora','agraria','shp','sertifikat','psn','mbr','rusun','apartemen','adat','sewa', 'jalan rusak', 'fasilitas umum','proyek'],
        'Pembangunan Desa, Daerah Tertinggal, dan Transmigrasi' => ['desa','pembangunan','daerah tertinggal','transmigrasi','pedesaan','pengembangan daerah','daerah 3T','dana desa','pembangunan desa'],
        'Pendidikan dan Kebudayaan' => ['pendidikan','sekolah','guru','murid','sekolah inklusif','kebudayaan','olahraga','universitas','pelajaran','beasiswa','buku','modul','tenaga pendidikan','ujian','jambore','pramuka','ijazah','kurikulum','prestasi siswa','prestasi guru','dosen','penerimaan siswa baru','pemagangan','zonasi', 's1', 'kuliah', 'ukt', 'renovasi', 'bbh', 'kip', 'nuptk', 'tpg', 'gtk', 'tendik', 'lpdp', 'dapodik', 'pip', 'kjmu'],
        'Pertanian dan Peternakan' => ['pertanian','peternakan','tanaman','pupuk','petani','ternak','hasil panen','sapi','ayam','bibit','lahan','teknologi pertanian','produktifitas','kesejahteraan petani','nelayan','perkapalan','kesejahteraaan nelayan','kapal ikan','tambak','daging sapi','perkebunan','padi','anak buah kapal','abk','pakan ikan','KUR pertanian','KUR perikanan'],
        'Politik dan Hukum' => ['politik','hukum','peraturan','pemilu','korupsi','regulasi','pengadilan','keadilan','legislasi','partai politik','putusan pengadilan','mafia hukum','lembaga peradilan','pertanahan','parpol', 'peradilan', 'pertanahan', 'polisi', 'polres', 'jaksa', 'penipuan', 'pidana', 'kasus', 'begal', 'pungli', 'kriminal', 'aniaya', 'skck', 'mediasi', 'pungutan', 'perkara', 'polda', 'penindakan', 'polsek', 'curanmor', 'kdrt', 'hilang', 'mata elang', 'adil', 'ormas', 'scam', 'pemerasan', 'ancaman', 'hinaan', 'grasi', 'judi', 'curi'],
        'Politisasi ASN' => ['asn','politisasi asn','netralitas asn','kampanye','pegawai negeri','pns','kode etik asn','manajemen asn','pengangkatan p3k','gaji asn','honorer','mutasi','penyalahgunaan wewenang','tes cpns'],
        'Sosial dan Kesejahteraan' => ['sosial','kesejahteraan','bansos','kesejahteraan sosial','penanggulangan kemiskinan','keluarga miskin','lansia','difabel','kartu lansia','disabilitas','tunggakan spp','tebus ijazah','baznas','miskin','bantuan sosial','pkh','dtks','blt','bpjs', 'makan gratis', 'makan', 'jkn', 'subsidi', 'bpnt', 'kjp', 'kis'],
        'SP4N Lapor' => ['lapor', 'pengaduan', 'sp4n', 'tindak lanjut', 'sistem pengaduan'],
        'Energi dan Sumber Daya Alam' => ['energi','minyak','gas','pertambangan','sumber daya alam','sda','listrik','pembangkit','bbm','pln','ebt','smelter','hilirisasi'],
        'Kekerasan di Satuan Pendidikan (Sekolah, Kampus, Lembaga Khusus)' => ['kekerasan','bullying','pelecehan','lembaga diklat','kampus','sekolah','pendidikan','bully','dosen','mahasiswa','siswa'],
        'Kependudukan' => ['penduduk', 'kependudukan', 'ktp', 'nik', 'domisili', 'data', 'dukcapil', 'kartu keluarga', 'pernikahan'],
        'Ketenagakerjaan' => ['pekerja','migran','tenaga kerja','buruh','karyawan','phk','upah','gaji','tunjangan','pensiun','jaminan kerja','outsourcing','hubungan industrial','kesempatan kerja','cuti','bpjs ketenagakerjaan','serikat pekerja','lowongan','pengangguran','pecat', 'kerja', 'rekrutmen', 'recruitment', 'pkwt', 'putus kontrak', 'loker', 'umk', 'thr'],
        'Netralitas ASN' => ['asn','pns','netralitas','politik','pegawai negeri','pilkada','kampanye'],
        'Pemulihan Ekonomi Nasional' => ['pemulihan','ekonomi','nasional','program','recovery','dampak pandemi','modal usaha'],
        'Pencegahan dan Pemberantasan Penyalahgunaan dan Peredaran Gelap Narkotika dan Prekursor Narkotika (P4GN)' => ['narkoba','p4gn','peredaran','penyalahgunaan','narkotika','obat'],
        'Mudik' => ['mudik','peniadaan','larangan','lebaran','transportasi','ppkm','tahun baru','mudik gratis','angkutan','lalu lintas','harga tiket','macet','tiket','libur','rest area','cuti','kecelakaan','natal','tol','tuslah','diskon','online'],
        'Perairan' => ['air','laut','sungai','bendungan','pelabuhan','irigasi','IPAL','keramba jaring apung','ikan','perikanan','budidaya','kualitas air','kja','udang','tambak','ekosistem'],
        'Perhubungan' => ['transportasi', 'angkutan', 'jalan', 'kendaraan', 'kereta', 'bus', 'pesawat', 'ojek online', 'ojek', 'mobil', 'motor', 'kapal', 'terminal', 'lrt', 'mrt', 'bandar udara', 'pelabuhan', 'stasiun', 'halte', 'tol', 'logistik', 'paket', 'barang', 'surat', 'asuransi', 'tod', 'parkir', 'sertifikasi', 'psn', 'tiket', 'truck', 'truk'],
        'Perlindungan Konsumen' => ['konsumen', 'perlindungan', 'penipuan', 'online', 'jual', 'ecommerce', 'bajakan', 'shopping', 'belanja', 'beli', 'produk', 'harga', 'robot', 'trading', 'transfer', 'tipu', 'shop', 'teror', 'afiliasi', 'korban', 'net89', 'noop'],
        'Teknologi Informasi dan Komunikasi' => ['teknologi', 'informasi', 'komunikasi', 'internet', 'digital', 'aplikasi', 'telekomunikasi', 'bts', 'literasi', 'hardware', 'software', 'data pribadi', 'data', 'jaringan', 'sistem', 'AI', '5G', '4G', 'sambungan', 'satelit', 'keamanan', 'cloud', 'frekuensi', 'hack', 'judol'],
        'Topik Khusus' => ['khusus', 'topik', 'isu tertentu', 'spesifik', 'pajak'],
        'Topik Lainnya' => ['lainnya'],
        'Perumahan' => ['pemukiman', 'gedung', 'sertipikat', 'tanah', 'shm', 'rumah', 'ajb', 'mbr', 'rusun', 'apartemen', 'adat','sewa', 'bangunan', 'kpr'],
        'Daerah Perbatasan' => ['daerah perbatasan', 'perbatasan', 'wilayah perbatasan', '3t', 'border', 'plbn', 'lintas batas'],
        'Kepemudaan dan Olahraga' => ['pendidikan', 'sekolah', 'guru', 'murid', 'sekolah inklusif', 'kebudayaan', 'universitas', 'pelajaran', 'beasiswa', 'olahraga', 'sport', 'kebudayaan', 'pelajaran', 'buku', 'modul', 'tenaga pendidikan', 'ujian', 'jambore', 'pramuka', 'ijazah', 'kurikulum', 'prestasi siswa', 'prestasi guru', 'dosen', 'penerimaan siswa baru', 'pemagangan', 'zonasi', 's1'],
        'Manajemen ASN' => ['asn', 'pegawai negeri', 'manajemen', 'gaji', 'pns', 'pengangkatan', 'seleksi', 'cpns', 'p3k', 'formasi', 'pppk', 'remun', 'psikotes', 'cp3k'],
        'Keluarga Berencana' => ['kb', 'keluarga berencana', 'alat kontrasepsi'],
        'Bantuan Masyarakat' => ['tunggakan sekolah', 'modal usaha', 'bantuan', 'tunggakan spp', 'tunggakan', 'proposal', 'proposal masjid', 'tebus ijazah', 'ambil ijazah', 'gereja', 'proposal desa', 'tunggak', 'spp'],
        'Luar Negeri' => ['imigran', 'kekonsuleran', 'pengungsi', 'migran', 'deportan', 'pencari suaka', 'tppo', 'paspor', 'wna', 'tkw', 'tki', 'imigrasi', 'kitas'],
        'Pariwisata dan Ekonomi Kreatif' => ['pariwisata', 'kreatif', 'wisata', 'turis', 'visa', 'turis lokal', 'turis asing', 'tiket pesawat', 'tiket masuk', 'wisata', 'akomodasi', 'hotel', 'wisatawan', 'pemandu wisata', 'souvenir', 'budaya', 'tari', 'performence', 'konser', 'musik', 'hiburan', 'film', 'entertainment', 'penyanyi', 'penari', 'pelawak', 'komedi', 'lagu', 'kreatif', 'okupansi', 'destinasi', 'desa wisata', 'cagar budaya', 'penulis', 'lukisan', 'anyaman', 'tenun', 'batik', 'atraksi', 'hospitaliti', 'trip', 'travel', 'festival'],
        'Pemberdayaan Masyarakat, Koperasi, dan UMKM' => ['umkm', 'koperasi', 'usaha kecil', 'usaha mikro', 'modal usaha', 'pemberdayaan masyarakat', 'kur', 'kredit macet', 'jaminan kur', 'usaha menengah', 'blacklist bank'],
        'Industri dan Perdagangan' => ['industri', 'perdagangan', 'ekspor', 'impor', 'barang', 'online', 'beli', 'dagang', 'jual', 'jasa', 'produsen', 'distributor', 'harga', 'toko', 'koperasi', 'pemasok', 'industri', 'tekstil', 'otomotif', 'konsumen', 'mesin', 'gudang', 'logistik', 'industri pengolahan', 'restoran', 'rumah makan', 'warung', 'pabrik', 'manufaktur', 'bahan baku', 'pasar', 'retail', 'supermarket', 'usaha', 'grosir', 'harga', 'bahan pokok', 'monopoli', 'kuota ekspor', 'dumping', 'e-commerce', 'bea masuk', 'profit', 'komoditi', 'komoditas', 'produk', 'perindag'],
        'Penanggulangan Bencana' => ['bencana', 'gempa', 'banjir', 'kebakaran', 'gunung meletus', 'tsunami', 'tanah longsor', 'relokasi', 'hunian tetap', 'hunian sementara', 'bnpb', 'rehabilitasi', 'rekonstruksi', 'bantuan korban bencana', 'bpbd', 'dana siap pakai', 'early warning system', 'kebakaran hutan dan lahan', 'pasca bencana', 'perubahan iklim', 'dana hibah', 'erupsi', 'mitigasi bencana', 'tanggap darurat', 'desa tangguh bencana', 'logistik bantuan', 'kekeringan', 'bencana non alam', 'pra bencana', 'krisis air'],
        'Pertanahan' => ['tanah', 'agraria', 'sertifikat', 'pembebasan lahan', 'pungutan', 'pungli', 'tanah', 'bangunan', 'bpn'],
        'Pelayanan Publik' => ['samsat', 'pelayanan', 'sim', 'birokrasi'],
        'TNI/Polri' => ['tni', 'polri'],
        'Lainnya' => [],
    ];

    // Daftar kata kunci dan kategori
    private static $kategoriSP4NLapor = [
        'Agama' => ['agama', 'ibadah', 'rumah ibadah', 'masjid', 'gereja', 'penistaan', 'hari besar keagamaan', 'yayasan keagamaan', 'zakat', 'wakaf', 'pesantren', 'haji', 'umroh', 'toleransi', 'santri', 'iman', 'amil', 'p3ukdk'],
        'Corona Virus' => ['covid', 'corona', 'pandemi', 'vaksin', 'omicron', 'lockdown', 'ppkm', 'varian'],
        'Ekonomi dan Keuangan' => ['ekonomi', 'keuangan', 'uang', 'investasi', 'bank', 'pinjaman', 'kredit', 'tabungan', 'inflasi', 'pinjol', 'utang', 'modal usaha', 'hutang', 'bodong', 'dana', 'asuransi', 'online', 'pajak', 'modal', 'penjaminan', 'pailit', 'angsuran', 'ojk', 'rentenir', 'tagihan', 'bi checking', 'restru'],
        'Kesehatan' => ['kesehatan', 'fasilitas dan pelayanan kesehatan', 'dokter', 'puskesmas', 'obat', 'penyakit', 'vaksin', 'bpjs kesehatan', 'perawat', 'stunting', 'rumah sakit', 'organisasi profesi tenaga kesehatan', 'malpraktek', 'pasien', 'sehat', 'sakit', 'stunting', 'gizi', 'rsud', 'persalinan', 'posyandu', 'akupuntur'],
        'Kesetaraan Gender dan Sosial Inklusif' => ['gender', 'kesetaraan', 'inklusi', 'organisasi wanita', 'difabel', 'perempuan', 'lgbt', 'waria', 'hak', 'gay', 'anak', 'ketahanan keluarga'],
        'Ketentraman, Ketertiban Umum, dan Perlindungan Masyarakat' => ['ketentraman', 'ketertiban', 'perlindungan', 'keamanan', 'keributan', 'masyarakat', 'konflik', 'kerusuhan', 'kriminalitas', 'kekerasan', 'bising', 'pkl', 'liar', 'rokok'],
        'Lingkungan Hidup dan Kehutanan' => ['lingkungan', 'hutan', 'polusi','sampah','air','pencemaran','deforestasi','kehutanan','reboisasi','limbah','banjir','erosi','kerusakan','ekosistem','abrasi','udara','penghijauan','kebakaran','perhutanan sosial','sungai','tanah','lahan','sawit','ulayat','adat'],
        'Pekerjaan Umum dan Penataan Ruang' => ['pekerjaan umum', 'infrastruktur', 'jalan','jembatan','bangunan','penataan ruang','pemukiman','gedung','rtrw','bendungan','sertifikat','tanah','shm','rumah','perkebunan','irigasi','ajb','ptsl','hgb','hgu','tora','agraria','shp','sertifikat','psn','mbr','rusun','apartemen','adat','sewa', 'jalan rusak', 'fasilitas umum','proyek'],
        'Pembangunan Desa, Daerah Tertinggal, dan Transmigrasi' => ['desa','pembangunan','daerah tertinggal','transmigrasi','pedesaan','pengembangan daerah','daerah 3T','dana desa','pembangunan desa'],
        'Pendidikan dan Kebudayaan' => ['pendidikan','sekolah','guru','murid','sekolah inklusif','kebudayaan','olahraga','universitas','pelajaran','beasiswa','buku','modul','tenaga pendidikan','ujian','jambore','pramuka','ijazah','kurikulum','prestasi siswa','prestasi guru','dosen','penerimaan siswa baru','pemagangan','zonasi', 's1', 'kuliah', 'ukt', 'renovasi', 'bbh', 'kip', 'nuptk', 'tpg', 'gtk', 'tendik', 'lpdp', 'dapodik', 'pip', 'kjmu'],
        'Pertanian dan Peternakan' => ['pertanian','peternakan','tanaman','pupuk','petani','ternak','hasil panen','sapi','ayam','bibit','lahan','teknologi pertanian','produktifitas','kesejahteraan petani','nelayan','perkapalan','kesejahteraaan nelayan','kapal ikan','tambak','daging sapi','perkebunan','padi','anak buah kapal','abk','pakan ikan','KUR pertanian','KUR perikanan'],
        'Politik dan Hukum' => ['politik','hukum','peraturan','pemilu','korupsi','regulasi','pengadilan','keadilan','legislasi','partai politik','putusan pengadilan','mafia hukum','lembaga peradilan','pertanahan','parpol', 'peradilan', 'pertanahan', 'polisi', 'polres', 'jaksa', 'penipuan', 'pidana', 'kasus', 'begal', 'pungli', 'kriminal', 'aniaya', 'skck', 'mediasi', 'pungutan', 'perkara', 'polda', 'penindakan', 'polsek', 'curanmor', 'kdrt', 'hilang', 'mata elang', 'adil', 'ormas', 'scam', 'pemerasan', 'ancaman', 'hinaan', 'grasi', 'judi', 'curi'],
        'Politisasi ASN' => ['asn','politisasi asn','netralitas asn','kampanye','pegawai negeri','pns','kode etik asn','manajemen asn','pengangkatan p3k','gaji asn','honorer','mutasi','penyalahgunaan wewenang','tes cpns'],
        'Sosial dan Kesejahteraan' => ['sosial','kesejahteraan','bansos','kesejahteraan sosial','penanggulangan kemiskinan','keluarga miskin','lansia','difabel','kartu lansia','disabilitas','tunggakan spp','tebus ijazah','baznas','miskin','bantuan sosial','pkh','dtks','blt','bpjs', 'makan gratis', 'makan', 'jkn', 'subsidi', 'bpnt', 'kjp', 'kis'],
        'SP4N Lapor' => ['lapor', 'pengaduan', 'sp4n', 'tindak lanjut', 'sistem pengaduan'],
        'Energi dan Sumber Daya Alam' => ['energi','minyak','gas','pertambangan','sumber daya alam','sda','listrik','pembangkit','bbm','pln','ebt','smelter','hilirisasi'],
        'Kekerasan di Satuan Pendidikan (Sekolah, Kampus, Lembaga Khusus)' => ['kekerasan','bullying','pelecehan','lembaga diklat','kampus','sekolah','pendidikan','bully','dosen','mahasiswa','siswa'],
        'Kependudukan' => ['penduduk', 'kependudukan', 'ktp', 'nik', 'domisili', 'data', 'dukcapil', 'kartu keluarga', 'pernikahan'],
        'Ketenagakerjaan' => ['pekerja','migran','tenaga kerja','buruh','karyawan','phk','upah','gaji','tunjangan','pensiun','jaminan kerja','outsourcing','hubungan industrial','kesempatan kerja','cuti','bpjs ketenagakerjaan','serikat pekerja','lowongan','pengangguran','pecat', 'kerja', 'rekrutmen', 'recruitment', 'pkwt', 'putus kontrak', 'loker', 'umk', 'thr'],
        'Netralitas ASN' => ['asn','pns','netralitas','politik','pegawai negeri','pilkada','kampanye'],
        'Pemulihan Ekonomi Nasional' => ['pemulihan','ekonomi','nasional','program','recovery','dampak pandemi','modal usaha'],
        'Pencegahan dan Pemberantasan Penyalahgunaan dan Peredaran Gelap Narkotika dan Prekursor Narkotika (P4GN)' => ['narkoba','p4gn','peredaran','penyalahgunaan','narkotika','obat'],
        'Mudik' => ['mudik','peniadaan','larangan','lebaran','transportasi','ppkm','tahun baru','mudik gratis','angkutan','lalu lintas','harga tiket','macet','tiket','libur','rest area','cuti','kecelakaan','natal','tol','tuslah','diskon','online'],
        'Perairan' => ['air','laut','sungai','bendungan','pelabuhan','irigasi','IPAL','keramba jaring apung','ikan','perikanan','budidaya','kualitas air','kja','udang','tambak','ekosistem'],
        'Perhubungan' => ['transportasi', 'angkutan', 'jalan', 'kendaraan', 'kereta', 'bus', 'pesawat', 'ojek online', 'ojek', 'mobil', 'motor', 'kapal', 'terminal', 'lrt', 'mrt', 'bandar udara', 'pelabuhan', 'stasiun', 'halte', 'tol', 'logistik', 'paket', 'barang', 'surat', 'asuransi', 'tod', 'parkir', 'sertifikasi', 'psn', 'tiket', 'truck', 'truk'],
        'Perlindungan Konsumen' => ['konsumen', 'perlindungan', 'penipuan', 'online', 'jual', 'ecommerce', 'bajakan', 'shopping', 'belanja', 'beli', 'produk', 'harga', 'robot', 'trading', 'transfer', 'tipu', 'shop', 'teror', 'afiliasi', 'korban', 'net89', 'noop'],
        'Teknologi Informasi dan Komunikasi' => ['teknologi', 'informasi', 'komunikasi', 'internet', 'digital', 'aplikasi', 'telekomunikasi', 'bts', 'literasi', 'hardware', 'software', 'data pribadi', 'data', 'jaringan', 'sistem', 'AI', '5G', '4G', 'sambungan', 'satelit', 'keamanan', 'cloud', 'frekuensi', 'hack', 'judol'],
        'Topik Khusus' => ['khusus', 'topik', 'isu tertentu', 'spesifik', 'pajak'],
        'Topik Lainnya' => ['lainnya'],
    ];

    private static $kategoriBaru = [
        'Perumahan' => ['pemukiman', 'gedung', 'sertipikat', 'tanah', 'shm', 'rumah', 'ajb', 'mbr', 'rusun', 'apartemen', 'adat','sewa', 'bangunan', 'kpr'],
        'Daerah Perbatasan' => ['daerah perbatasan', 'perbatasan', 'wilayah perbatasan', '3t', 'border', 'plbn', 'lintas batas'],
        'Kepemudaan dan Olahraga' => ['pendidikan', 'sekolah', 'guru', 'murid', 'sekolah inklusif', 'kebudayaan', 'universitas', 'pelajaran', 'beasiswa', 'olahraga', 'sport', 'kebudayaan', 'pelajaran', 'buku', 'modul', 'tenaga pendidikan', 'ujian', 'jambore', 'pramuka', 'ijazah', 'kurikulum', 'prestasi siswa', 'prestasi guru', 'dosen', 'penerimaan siswa baru', 'pemagangan', 'zonasi', 's1'],
        'Manajemen ASN' => ['asn', 'pegawai negeri', 'manajemen', 'gaji', 'pns', 'pengangkatan', 'seleksi', 'cpns', 'p3k', 'formasi', 'pppk', 'remun', 'psikotes', 'cp3k'],
        'Keluarga Berencana' => ['kb', 'keluarga berencana', 'alat kontrasepsi'],
        'Bantuan Masyarakat' => ['tunggakan sekolah', 'modal usaha', 'bantuan', 'tunggakan spp', 'tunggakan', 'proposal', 'proposal masjid', 'tebus ijazah', 'ambil ijazah', 'gereja', 'proposal desa', 'tunggak', 'spp'],
        'Luar Negeri' => ['imigran', 'kekonsuleran', 'pengungsi', 'migran', 'deportan', 'pencari suaka', 'tppo', 'paspor', 'wna', 'tkw', 'tki', 'imigrasi', 'kitas'],
        'Pariwisata dan Ekonomi Kreatif' => ['pariwisata', 'kreatif', 'wisata', 'turis', 'visa', 'turis lokal', 'turis asing', 'tiket pesawat', 'tiket masuk', 'wisata', 'akomodasi', 'hotel', 'wisatawan', 'pemandu wisata', 'souvenir', 'budaya', 'tari', 'performence', 'konser', 'musik', 'hiburan', 'film', 'entertainment', 'penyanyi', 'penari', 'pelawak', 'komedi', 'lagu', 'kreatif', 'okupansi', 'destinasi', 'desa wisata', 'cagar budaya', 'penulis', 'lukisan', 'anyaman', 'tenun', 'batik', 'atraksi', 'hospitaliti', 'trip', 'travel', 'festival'],
        'Pemberdayaan Masyarakat, Koperasi, dan UMKM' => ['umkm', 'koperasi', 'usaha kecil', 'usaha mikro', 'modal usaha', 'pemberdayaan masyarakat', 'kur', 'kredit macet', 'jaminan kur', 'usaha menengah', 'blacklist bank'],
        'Industri dan Perdagangan' => ['industri', 'perdagangan', 'ekspor', 'impor', 'barang', 'online', 'beli', 'dagang', 'jual', 'jasa', 'produsen', 'distributor', 'harga', 'toko', 'koperasi', 'pemasok', 'industri', 'tekstil', 'otomotif', 'konsumen', 'mesin', 'gudang', 'logistik', 'industri pengolahan', 'restoran', 'rumah makan', 'warung', 'pabrik', 'manufaktur', 'bahan baku', 'pasar', 'retail', 'supermarket', 'usaha', 'grosir', 'harga', 'bahan pokok', 'monopoli', 'kuota ekspor', 'dumping', 'e-commerce', 'bea masuk', 'profit', 'komoditi', 'komoditas', 'produk', 'perindag'],
        'Penanggulangan Bencana' => ['bencana', 'gempa', 'banjir', 'kebakaran', 'gunung meletus', 'tsunami', 'tanah longsor', 'relokasi', 'hunian tetap', 'hunian sementara', 'bnpb', 'rehabilitasi', 'rekonstruksi', 'bantuan korban bencana', 'bpbd', 'dana siap pakai', 'early warning system', 'kebakaran hutan dan lahan', 'pasca bencana', 'perubahan iklim', 'dana hibah', 'erupsi', 'mitigasi bencana', 'tanggap darurat', 'desa tangguh bencana', 'logistik bantuan', 'kekeringan', 'bencana non alam', 'pra bencana', 'krisis air'],
        'Pertanahan' => ['tanah', 'agraria', 'sertifikat', 'pembebasan lahan', 'pungutan', 'pungli', 'tanah', 'bangunan', 'bpn'],
        'Pelayanan Publik' => ['samsat', 'pelayanan', 'sim', 'birokrasi'],
        'TNI/Polri' => ['tni', 'polri'],
        'Lainnya' => [],
    ];

    public static function getKategoriSP4NLapor()
    {
        return array_keys(self::$kategoriSP4NLapor);
    }

    public static function getKategoriBaru()
    {
        return array_keys(self::$kategoriBaru);
    }

    private static $kategoriDeputi = [
        'deputi_1' => ['Ekonomi dan Keuangan', 'Lingkungan Hidup dan Kehutanan', 'Pekerjaan Umum dan Penataan Ruang', 'Pertanian dan Peternakan', 'Pemulihan Ekonomi Nasional', 'Energi dan Sumber Daya Alam', 'Mudik', 'Perairan', 'Perhubungan', 'Teknologi Informasi dan Komunikasi', 'Perlindungan Konsumen', 'Pariwisata dan Ekonomi Kreatif', 'Industri dan Perdagangan', 'Perumahan'],
        'deputi_2' => ['Agama', 'Corona Virus', 'Kesehatan', 'Kesetaraan Gender dan Sosial Inklusif', 'Pembangunan Desa, Daerah Tertinggal, dan Transmigrasi', 'Pendidikan dan Kebudayaan', 'Sosial dan Kesejahteraan', 'Kekerasan di Satuan Pendidikan (Sekolah, Kampus, Lembaga Khusus)', 'Penanggulangan Bencana', 'Ketenagakerjaan', 'Kependudukan', 'Pemberdayaan Masyarakat, Koperasi, dan UMKM', 'Daerah Perbatasan', 'Kepemudaan dan Olahraga', 'Keluarga Berencana'],
        'deputi_3' => ['Ketentraman, Ketertiban Umum, dan Perlindungan Masyarakat','Politik dan Hukum', 'Politisasi ASN', 'SP4N Lapor', 'Netralitas ASN', 'Pencegahan dan Pemberantasan Penyalahgunaan dan Peredaran Gelap Narkotika dan Prekursor Narkotika (P4GN)', 'Manajemen ASN', 'Luar Negeri', 'Pertanahan', 'Pelayanan Publik', 'TNI/Polri'],
        'deputi_4' => ['Topik Khusus', 'Topik Lainnya', 'Bantuan Masyarakat'],
    ];

    public static function tentukanKategoriDanDeputi($judul)
    {
        $judul = strtolower($judul); // Ubah judul ke huruf kecil
        $kategoriScores = [];

        // Gabungkan semua kategori SP4N Lapor dan Kategori Baru
        $gabunganKategori = array_merge(self::$kategoriSP4NLapor, self::$kategoriBaru);

        // Hitung skor untuk setiap kategori berdasarkan kata kunci
        foreach ($gabunganKategori as $kategori => $keywords) {
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

    public static function getKategoriKataKunci()
    {
        return self::$kategoriKataKunci;
    }

    public static function getKategoriDeputi()
    {
        return self::$kategoriDeputi;
    }
}