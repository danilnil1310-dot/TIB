<?php
// website.php
session_start();
require_once 'config.php'; 

$error_msg = null;
$success_msg = null;

// FUNGSI UTAS: Menangani Unggah Gambar secara Aman ke Server
function handleFileUpload($fileInput, $currentAvatar = null) {
    if (isset($_FILES[$fileInput]) && $_FILES[$fileInput]['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES[$fileInput]['tmp_name'];
        $fileName = $_FILES[$fileInput]['name'];
        
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($fileExtension, $allowedExtensions)) {
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $uploadFileDir = './uploads/';
            $dest_path = $uploadFileDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                return $dest_path; 
            }
        }
    }
    return $currentAvatar ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=150';
}

// 1. PROSES LOGIN
if (isset($_POST['action']) && $_POST['action'] == 'login') {
    $input_nomor = trim($_POST['nomor'] ?? '');
    $input_sandi = $_POST['sandi'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE nomor = ?");
    $stmt->execute([$input_nomor]);
    $user_data = $stmt->fetch();

    if ($user_data && password_verify($input_sandi, $user_data['sandi'])) {
        $_SESSION['user'] = [
            'nomor'  => $user_data['nomor'],
            'nama'   => $user_data['nama'],
            'role'   => $user_data['role'],
            'avatar' => $user_data['avatar']
        ];
        header("Location: website.php");
        exit;
    } else {
        $error_msg = "Nomor HP atau kata sandi salah!";
    }
}

// 2. PROSES REGISTRASI
if (isset($_POST['action']) && $_POST['action'] == 'register') {
    $reg_nama   = trim($_POST['reg_nama'] ?? '');
    $reg_nomor  = trim($_POST['reg_nomor'] ?? '');
    $reg_sandi  = $_POST['reg_sandi'] ?? '';

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE nomor = ?");
    $stmt->execute([$reg_nomor]);
    
    if ($stmt->fetchColumn() > 0) {
        $error_msg = "Nomor HP sudah terdaftar!";
    } elseif (empty($reg_nama) || empty($reg_nomor) || empty($reg_sandi)) {
        $error_msg = "Semua kolom pendaftaran wajib diisi!";
    } else {
        $avatar_path = handleFileUpload('reg_avatar');
        $sandi_secure = password_hash($reg_sandi, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (nomor, nama, sandi, role, avatar) VALUES (?, ?, ?, 'user', ?)");
        $stmt->execute([$reg_nomor, $reg_nama, $sandi_secure, $avatar_path]);

        $_SESSION['user'] = [
            'nomor'  => $reg_nomor,
            'nama'   => $reg_nama,
            'role'   => 'user',
            'avatar' => $avatar_path
        ];
        header("Location: website.php");
        exit;
    }
}

$is_logged_in = isset($_SESSION['user']);
$user_role    = $is_logged_in ? $_SESSION['user']['role'] : 'guest';

// 3. PROSES UPDATE PROFIL USER
if (isset($_POST['action']) && $_POST['action'] == 'update_profile' && $is_logged_in) {
    $nama_baru   = trim($_POST['update_nama'] ?? '');
    $sandi_baru  = $_POST['update_sandi'] ?? '';
    $nomor_user  = $_SESSION['user']['nomor'];

    if (!empty($nama_baru) && !empty($sandi_baru)) {
        $avatar_final = handleFileUpload('update_avatar', $_SESSION['user']['avatar']);
        $sandi_secure = password_hash($sandi_baru, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET nama = ?, sandi = ?, avatar = ? WHERE nomor = ?");
        $stmt->execute([$nama_baru, $sandi_secure, $avatar_final, $nomor_user]);

        $_SESSION['user']['nama']   = $nama_baru;
        $_SESSION['user']['avatar'] = $avatar_final;
        $success_msg = "Profil & Foto Baru berhasil disimpan!";
    } else {
        $error_msg = "Kolom Nama dan Kata Sandi wajib diisi!";
    }
}

// 4. TAMBAH DESTINASI (EKSKLUSIF ADMIN) - FIX BUG & DAFTAR KOLOM BARU
if (isset($_POST['action']) && $_POST['action'] == 'tambah_destinasi' && $user_role == 'admin') {
    $nama = trim($_POST['nama_destinasi'] ?? '');
    $tag = trim($_POST['tag_destinasi'] ?? 'Umum');
    $gambar = trim($_POST['gambar_destinasi'] ?? 'https://images.unsplash.com/photo-1537996194471-e657df975ab4');
    $deskripsi = trim($_POST['deskripsi_destinasi'] ?? '');
    
    $rating = !empty($_POST['rating']) ? floatval($_POST['rating']) : 5.0;
    $biaya = !empty($_POST['biaya']) ? intval($_POST['biaya']) : 0;
    $fasilitas = !empty($_POST['fasilitas']) ? trim($_POST['fasilitas']) : '-';
    $waktu_terbaik = !empty($_POST['waktu_terbaik']) ? trim($_POST['waktu_terbaik']) : '-';

    if (!empty($nama) && !empty($deskripsi)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO destinasi (nama, tag, gambar, deskripsi, rating, biaya, fasilitas, waktu_terbaik) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nama, $tag, $gambar, $deskripsi, $rating, $biaya, $fasilitas, $waktu_terbaik]);
            $success_msg = "Destinasi baru berhasil ditambahkan dan diterbitkan!";
        } catch (PDOException $e) {
            $error_msg = "Gagal menyimpan ke database: " . $e->getMessage();
        }
    } else {
        $error_msg = "Nama tempat wisata dan deskripsi wajib diisi!";
    }
}

// 5. EDIT/UPDATE DESTINASI (EKSKLUSIF ADMIN)
if (isset($_POST['action']) && $_POST['action'] == 'edit_destinasi' && $user_role == 'admin') {
    $id = intval($_POST['id_destinasi'] ?? 0);
    $nama = trim($_POST['nama_destinasi'] ?? '');
    $tag = trim($_POST['tag_destinasi'] ?? 'Umum');
    $gambar = trim($_POST['gambar_destinasi'] ?? '');
    $deskripsi = trim($_POST['deskripsi_destinasi'] ?? '');
    
    $rating = !empty($_POST['rating']) ? floatval($_POST['rating']) : 5.0;
    $biaya = !empty($_POST['biaya']) ? intval($_POST['biaya']) : 0;
    $fasilitas = !empty($_POST['fasilitas']) ? trim($_POST['fasilitas']) : '-';
    $waktu_terbaik = !empty($_POST['waktu_terbaik']) ? trim($_POST['waktu_terbaik']) : '-';

    if ($id > 0 && !empty($nama) && !empty($deskripsi)) {
        try {
            $stmt = $pdo->prepare("UPDATE destinasi SET nama=?, tag=?, gambar=?, deskripsi=?, rating=?, biaya=?, fasilitas=?, waktu_terbaik=? WHERE id=?");
            $stmt->execute([$nama, $tag, $gambar, $deskripsi, $rating, $biaya, $fasilitas, $waktu_terbaik, $id]);
            $success_msg = "Destinasi berhasil diperbarui!";
        } catch (PDOException $e) {
            $error_msg = "Gagal memperbarui database: " . $e->getMessage();
        }
    } else {
        $error_msg = "Nama tempat wisata dan deskripsi tidak boleh kosong.";
    }
}

// 6. HAPUS DESTINASI
if (isset($_GET['action']) && $_GET['action'] == 'hapus_destinasi' && $user_role == 'admin') {
    $id_hapus = intval($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("DELETE FROM destinasi WHERE id = ?");
    $stmt->execute([$id_hapus]);
    $success_msg = "Destinasi berhasil dihapus!";
}

// 7. PROSES LOGOUT
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    unset($_SESSION['user']);
    session_destroy();
    header("Location: website.php");
    exit;
}

$destinasi_list = $pdo->query("SELECT * FROM destinasi ORDER BY id DESC")->fetchAll();
$user_list = [];
$total_user_biasa = 0;

if ($user_role == 'admin') {
    $user_list = $pdo->query("SELECT nomor, nama, role, avatar FROM users WHERE role = 'user'")->fetchAll();
    $total_user_biasa = count($user_list);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesona Lombok</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .modal { transition: opacity 0.25s ease; }
        body.modal-active { overflow: hidden; }
    </style>
</head>
<body class="bg-gray-50 font-sans antialiased text-gray-800">

    <?php if (!$is_logged_in): ?>
    <div class="min-h-screen flex items-center justify-center bg-cover bg-center px-4 py-8" style="background-image: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('https://images.unsplash.com/photo-1571731956612-40262cf9b117?auto=format&fit=crop&w=1200&q=80');">
        <div class="bg-white p-8 rounded-2xl shadow-2xl w-full max-w-md border border-gray-100">
            <div class="text-center mb-6">
                <h2 class="text-3xl font-bold text-teal-600 tracking-wide">Pesona Lombok</h2>
                <p class="text-gray-500 text-sm mt-1">Silakan masuk untuk menjelajahi keindahan Lombok</p>
            </div>

            <?php if ($error_msg): ?>
                <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-2 rounded-xl text-sm font-medium text-center mb-4">
                    <i class="fas fa-exclamation-circle mr-1"></i> <?php echo htmlspecialchars($error_msg); ?>
                </div>
            <?php endif; ?>

            <form id="login-form" action="website.php" method="POST" class="space-y-4 block">
                <input type="hidden" name="action" value="login">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Nomor HP / WA</label>
                    <input type="text" name="nomor" required class="w-full px-4 py-2.5 border border-gray-300 rounded-xl outline-none" placeholder="0812xxxx">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Kata Sandi</label>
                    <input type="password" name="sandi" required class="w-full px-4 py-2.5 border border-gray-300 rounded-xl outline-none" placeholder="••••••••">
                </div>
                <button type="submit" class="w-full bg-teal-600 hover:bg-teal-700 text-white font-medium py-2.5 rounded-xl transition shadow-lg">Masuk</button>
                <p class="text-center text-sm text-gray-600">Belum punya akun? <button type="button" onclick="toggleAuthForm('register')" class="text-teal-600 font-semibold hover:underline">Daftar Akun Baru</button></p>
            </form>

            <form id="register-form" action="website.php" method="POST" enctype="multipart/form-data" class="space-y-4 hidden">
                <input type="hidden" name="action" value="register">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Nama Lengkap</label>
                    <input type="text" name="reg_nama" required class="w-full px-4 py-2.5 border border-gray-300 rounded-xl outline-none" placeholder="Masukkan nama Anda">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Nomor HP / WA</label>
                    <input type="text" name="reg_nomor" required class="w-full px-4 py-2.5 border border-gray-300 rounded-xl outline-none" placeholder="0812xxxx">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Kata Sandi</label>
                    <input type="password" name="reg_sandi" required class="w-full px-4 py-2.5 border border-gray-300 rounded-xl outline-none" placeholder="••••••••">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Pilih Foto Profil (Opsional)</label>
                    <input type="file" name="reg_avatar" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100">
                </div>
                <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-2.5 rounded-xl transition shadow-lg">Daftar & Masuk</button>
                <p class="text-center text-sm text-gray-600">Sudah memiliki akun? <button type="button" onclick="toggleAuthForm('login')" class="text-teal-600 font-semibold hover:underline">Login disini</button></p>
            </form>
        </div>
    </div>
    <script>
        function toggleAuthForm(mode) {
            document.getElementById('login-form').style.display = mode === 'register' ? 'none' : 'block';
            document.getElementById('register-form').style.display = mode === 'register' ? 'block' : 'none';
        }
    </script>

    <?php else: ?>
    <div>
        <nav class="bg-white border-b border-gray-100 sticky top-0 z-50 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="text-2xl font-bold text-teal-600 tracking-wide">Pesona Lombok</span>
                    <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full <?php echo $user_role == 'admin' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'; ?>">
                        <?php echo strtoupper(htmlspecialchars($user_role)); ?>
                    </span>
                </div>
                <div class="flex items-center gap-4">
                    <button onclick="toggleModal('account-modal')" class="flex items-center gap-2 bg-gray-50 hover:bg-gray-100 border border-gray-200 px-4 py-2 rounded-xl transition">
                        <img src="<?php echo htmlspecialchars($_SESSION['user']['avatar']); ?>" class="w-6 h-6 rounded-full object-cover">
                        <span class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($_SESSION['user']['nama']); ?></span>
                    </button>
                    <a href="website.php?action=logout" class="text-sm font-medium text-red-500 hover:text-red-600 flex items-center gap-2 border border-red-100 px-3 py-2 rounded-xl hover:bg-red-50 transition">
                        Keluar
                    </a>
                </div>
            </div>
        </nav>

        <?php if ($success_msg): ?>
            <div class="bg-emerald-50 border-b border-emerald-200 text-emerald-700 px-4 py-3 text-center text-sm font-medium">
                <i class="fas fa-check-circle mr-2"></i> <?php echo htmlspecialchars($success_msg); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_msg): ?>
            <div class="bg-red-50 border-b border-red-200 text-red-700 px-4 py-3 text-center text-sm font-medium">
                <i class="fas fa-exclamation-triangle mr-2"></i> <?php echo htmlspecialchars($error_msg); ?>
            </div>
        <?php endif; ?>

        <?php if ($user_role == 'admin'): ?>
            <div class="bg-slate-900 text-white py-12 px-4 shadow-inner">
                <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-2 space-y-4">
                        <h1 class="text-3xl font-extrabold mb-2"><i class="fas fa-tools text-purple-400 mr-2"></i>Panel Kontrol MySQL</h1>
                        <div class="bg-slate-800 p-6 rounded-2xl border border-slate-700 shadow-xl">
                            <h3 class="font-bold text-lg mb-4 text-purple-300"><i class="fas fa-plus-circle mr-2"></i>Tambah Tempat Wisata</h3>
                            <form action="website.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <input type="hidden" name="action" value="tambah_destinasi">
                                <div>
                                    <label class="block text-xs text-slate-400 mb-1">Nama Tempat Wisata</label>
                                    <input type="text" name="nama_destinasi" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2 text-white text-sm outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs text-slate-400 mb-1">Tag Kategori</label>
                                    <input type="text" name="tag_destinasi" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2 text-white text-sm outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs text-slate-400 mb-1">Rating Pengunjung (1.0 - 5.0)</label>
                                    <input type="number" step="0.1" min="1" max="5" name="rating" placeholder="4.5" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2 text-white text-sm outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs text-slate-400 mb-1">Biaya Tiket Masuk (Rp)</label>
                                    <input type="number" name="biaya" placeholder="0 jika Gratis" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2 text-white text-sm outline-none">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs text-slate-400 mb-1">Waktu Terbaik Berkunjung</label>
                                    <input type="text" name="waktu_terbaik" placeholder="Contoh: Mei - Agustus" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2 text-white text-sm outline-none">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs text-slate-400 mb-1">URL Gambar Tempat</label>
                                    <input type="url" name="gambar_destinasi" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2 text-white text-sm outline-none">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs text-slate-400 mb-1">Fasilitas (Pisahkan dengan koma)</label>
                                    <input type="text" name="fasilitas" placeholder="Toilet, Parkir, Gazebo" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2 text-white text-sm outline-none">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs text-slate-400 mb-1">Deskripsi Singkat</label>
                                    <textarea name="deskripsi_destinasi" rows="2" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2 text-white text-sm outline-none"></textarea>
                                </div>
                                <div class="md:col-span-2 text-right">
                                    <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-medium px-6 py-2 rounded-xl text-sm transition">Publish ke Database</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="lg:col-span-1">
                        <div class="bg-slate-800 p-5 rounded-2xl border border-slate-700 shadow-xl h-full flex flex-col justify-between">
                            <div>
                                <h3 class="font-bold text-lg mb-1 text-emerald-400"><i class="fas fa-users"></i> Pengguna Database</h3>
                                <div class="overflow-y-auto max-h-[320px] space-y-3 mt-4">
                                    <?php foreach ($user_list as $u): ?>
                                        <div class="flex items-center justify-between p-3 bg-slate-900/60 rounded-xl border border-slate-700/50">
                                            <div class="flex items-center gap-3">
                                                <img src="<?php echo htmlspecialchars($u['avatar']); ?>" class="w-9 h-9 rounded-full object-cover">
                                                <div>
                                                    <h4 class="text-sm font-semibold text-slate-200"><?php echo htmlspecialchars($u['nama']); ?></h4>
                                                    <span class="text-xs text-slate-400"><?php echo htmlspecialchars($u['nomor']); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="mt-4 pt-3 border-t border-slate-700/50 text-center text-xs text-slate-500">Total: <b><?php echo $total_user_biasa; ?> Pengguna</b></div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <header class="relative bg-gray-900 text-white h-[30vh] flex items-center justify-center bg-cover bg-center" style="background-image: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('https://images.unsplash.com/photo-1537996194471-e657df975ab4?w=1200');">
                <h1 class="text-3xl md:text-5xl font-extrabold tracking-tight">Halo, <?php echo htmlspecialchars($_SESSION['user']['nama']); ?>!</h1>
            </header>
        <?php endif; ?>

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <h2 class="text-2xl font-bold text-center mb-10">Destinasi Eksotis Lombok</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <?php foreach ($destinasi_list as $dest): ?>
                    <div class="bg-white rounded-2xl overflow-hidden shadow-md border border-gray-100 flex flex-col justify-between">
                        <div>
                            <div class="relative">
                                <img src="<?php echo htmlspecialchars($dest['gambar']); ?>" class="w-full h-48 object-cover">
                                <span class="absolute top-3 right-3 bg-amber-500 text-white text-xs font-bold px-2.5 py-1 rounded-full shadow-md flex items-center gap-1">
                                    <i class="fas fa-star"></i> <?php echo number_format($dest['rating'] ?? 5.0, 1); ?>
                                </span>
                            </div>
                            <div class="p-6 space-y-3">
                                <div>
                                    <span class="bg-teal-50 text-teal-600 text-xs font-semibold px-2.5 py-1 rounded-md"><?php echo htmlspecialchars($dest['tag']); ?></span>
                                    <h3 class="font-bold text-lg mt-2 text-gray-800"><?php echo htmlspecialchars($dest['nama']); ?></h3>
                                </div>
                                <p class="text-gray-600 text-sm leading-relaxed"><?php echo htmlspecialchars($dest['deskripsi']); ?></p>
                                
                                <hr class="border-gray-100">
                                
                                <div class="grid grid-cols-2 gap-2 text-xs text-gray-600 pt-1">
                                    <div class="flex items-center gap-1.5">
                                        <i class="fas fa-ticket-alt text-teal-500 w-4"></i>
                                        <span><b>Tiket:</b> <?php echo (!isset($dest['biaya']) || $dest['biaya'] == 0) ? 'Gratis' : 'Rp ' . number_format($dest['biaya'], 0, ',', '.'); ?></span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <i class="fas fa-calendar-day text-amber-500 w-4"></i>
                                        <span><b>Waktu:</b> <?php echo htmlspecialchars($dest['waktu_terbaik'] ?? '-'); ?></span>
                                    </div>
                                    <div class="col-span-2 pt-1">
                                        <i class="fas fa-concierge-bell text-blue-500 mr-1"></i>
                                        <span class="text-gray-500"><b>Fasilitas:</b> <?php echo htmlspecialchars($dest['Parkiran,toilet,gazebo'] ?? '-'); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="px-6 pb-6 pt-2 flex items-center justify-end bg-gray-50 border-t border-gray-50">
                            <?php if ($user_role == 'admin'): ?>
                                <div class="flex gap-2">
                                    <button 
                                        onclick="openEditModal(<?php echo $dest['id']; ?>, '<?php echo addslashes(htmlspecialchars($dest['nama'])); ?>', '<?php echo addslashes(htmlspecialchars($dest['tag'])); ?>', '<?php echo addslashes(htmlspecialchars($dest['gambar'])); ?>', '<?php echo addslashes(htmlspecialchars($dest['deskripsi'])); ?>', '<?php echo $dest['rating'] ?? 5.0; ?>', '<?php echo $dest['biaya'] ?? 0; ?>', '<?php echo addslashes(htmlspecialchars($dest['fasilitas'] ?? '')); ?>', '<?php echo addslashes(htmlspecialchars($dest['waktu_terbaik'] ?? '')); ?>')" 
                                        class="text-xs bg-amber-100 hover:bg-amber-200 text-amber-700 font-bold px-3 py-1.5 rounded-lg transition">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <a href="website.php?action=hapus_destinasi&id=<?php echo $dest['id']; ?>" onclick="return confirm('Hapus destinasi ini?')" class="text-xs bg-red-100 hover:bg-red-200 text-red-600 font-bold px-3 py-1.5 rounded-lg transition">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <div id="account-modal" class="modal opacity-0 pointer-events-none fixed w-full h-full top-0 left-0 flex items-center justify-center z-50">
        <div class="absolute w-full h-full bg-gray-900 opacity-50" onclick="toggleModal('account-modal')"></div>
        <div class="bg-white w-full max-w-md mx-auto rounded-2xl shadow-2xl z-50 overflow-hidden">
            <div class="px-6 py-4 border-b font-bold text-gray-800">Pengaturan Akun</div>
            <form action="website.php" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                <input type="hidden" name="action" value="update_profile">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Nama</label>
                    <input type="text" name="update_nama" required value="<?php echo htmlspecialchars($_SESSION['user']['nama']); ?>" class="w-full px-4 py-2 border rounded-xl outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Ganti Foto Profil Anda (Unggah File Gambar)</label>
                    <input type="file" name="update_avatar" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100">
                    <p class="text-[10px] text-gray-400 mt-1">*Kosongkan jika tidak ingin mengubah foto saat ini.</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Masukan Kata Sandi Baru / Saat ini (Konfirmasi)</label>
                    <input type="password" name="update_sandi" required class="w-full px-4 py-2 border rounded-xl outline-none" placeholder="••••••••">
                </div>
                <div class="flex justify-end gap-2 pt-4 border-t">
                    <button type="button" onclick="toggleModal('account-modal')" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-xl text-sm">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-teal-600 text-white rounded-xl text-sm">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <div id="edit-destinasi-modal" class="modal opacity-0 pointer-events-none fixed w-full h-full top-0 left-0 flex items-center justify-center z-50">
        <div class="absolute w-full h-full bg-gray-900 opacity-50" onclick="toggleModal('edit-destinasi-modal')"></div>
        <div class="bg-white w-full max-w-xl mx-auto rounded-2xl shadow-2xl z-50 overflow-hidden">
            <div class="px-6 py-4 bg-amber-50 border-b border-amber-100 font-bold text-amber-800 flex items-center gap-2">
                <i class="fas fa-edit"></i> Edit Data Destinasi Komprehensif
            </div>
            <form action="website.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="hidden" name="action" value="edit_destinasi">
                <input type="hidden" name="id_destinasi" id="edit-id">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Nama Tempat Wisata</label>
                    <input type="text" name="nama_destinasi" id="edit-nama" required class="w-full px-3 py-2 border rounded-xl text-sm outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Tag Kategori</label>
                    <input type="text" name="tag_destinasi" id="edit-tag" required class="w-full px-3 py-2 border rounded-xl text-sm outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Rating (1.0 - 5.0)</label>
                    <input type="number" step="0.1" name="rating" id="edit-rating" required class="w-full px-3 py-2 border rounded-xl text-sm outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Biaya Tiket Masuk (Rp)</label>
                    <input type="number" name="biaya" id="edit-biaya" required class="w-full px-3 py-2 border rounded-xl text-sm outline-none">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Waktu Terbaik Berkunjung</label>
                    <input type="text" name="waktu_terbaik" id="edit-waktu" required class="w-full px-3 py-2 border rounded-xl text-sm outline-none">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">URL Gambar</label>
                    <input type="url" name="gambar_destinasi" id="edit-gambar" required class="w-full px-3 py-2 border rounded-xl text-sm outline-none">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Fasilitas Destinasi</label>
                    <input type="text" name="fasilitas" id="edit-fasilitas" required class="w-full px-3 py-2 border rounded-xl text-sm outline-none">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Deskripsi Lokasi</label>
                    <textarea name="deskripsi_destinasi" id="edit-deskripsi" rows="3" required class="w-full px-3 py-2 border rounded-xl text-sm outline-none"></textarea>
                </div>
                <div class="md:col-span-2 flex justify-end gap-2 pt-4 border-t">
                    <button type="button" onclick="toggleModal('edit-destinasi-modal')" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-xl text-sm">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-sm shadow-md">Perbarui Data</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleModal(id) {
            const modal = document.getElementById(id);
            modal.classList.toggle('opacity-0');
            modal.classList.toggle('pointer-events-none');
            document.body.classList.toggle('modal-active');
        }

        function openEditModal(id, nama, tag, gambar, deskripsi, rating, biaya, fasilitas, waktu) {
            document.getElementById('edit-id').value = id;
            document.getElementById('edit-nama').value = nama;
            document.getElementById('edit-tag').value = tag;
            document.getElementById('edit-gambar').value = gambar;
            document.getElementById('edit-deskripsi').value = deskripsi;
            document.getElementById('edit-rating').value = rating;
            document.getElementById('edit-biaya').value = biaya;
            document.getElementById('edit-fasilitas').value = fasilitas;
            document.getElementById('edit-waktu').value = waktu;
            toggleModal('edit-destinasi-modal');
        }
    </script>
    <?php endif; ?>

</body>
</html>