<?php
session_start();
include "../koneksi.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil data lama user
$q = mysqli_query($koneksi, "SELECT * FROM users WHERE user_id='$user_id'");
$u = mysqli_fetch_assoc($q);

// Update username & bio
$username = mysqli_real_escape_string($koneksi, $_POST['username']);
$bio = mysqli_real_escape_string($koneksi, $_POST['bio']);

mysqli_query($koneksi, "
    UPDATE users SET 
        username='$username',
        bio='$bio'
    WHERE user_id='$user_id'
");

// -----------------------------------------------
//  FUNCTION: Simpan Base64 (cropper) ke file
// -----------------------------------------------
function saveBase64Image($base64, $folder) {
    if (!$base64 || strpos($base64, "data:image") !== 0) return null;

    if (!is_dir($folder)) mkdir($folder, 0777, true);

    $image_parts = explode(";base64,", $base64);
    $image_data = base64_decode($image_parts[1]);

    $filename = $folder . time() . "_" . bin2hex(random_bytes(5)) . ".png";

    file_put_contents($filename, $image_data);
    return $filename;
}

// -----------------------------------------------
//  HANDLE FOTO PROFIL (HASIL CROP)
// -----------------------------------------------
if (!empty($_POST['profile_picture_cropped'])) {

    // hapus file lama jika bukan default
    if (!empty($u['profile_picture']) && $u['profile_picture'] != "default_pp.png") {
        if (file_exists("../" . $u['profile_picture'])) {
            unlink("../" . $u['profile_picture']);
        }
    }

    $file = saveBase64Image($_POST['profile_picture_cropped'], "../Uploads/Profile/");
    if ($file) {
        $db_path = str_replace("../", "", $file);
        mysqli_query($koneksi, "
            UPDATE users SET profile_picture='$db_path' WHERE user_id='$user_id'
        ");
    }
}

// -----------------------------------------------
//  HANDLE BANNER (HASIL CROP)
// -----------------------------------------------
if (!empty($_POST['banner_cropped'])) {

    if (!empty($u['banner']) && $u['banner'] != "default_banner.png") {
        if (file_exists("../" . $u['banner'])) {
            unlink("../" . $u['banner']);
        }
    }

    $file = saveBase64Image($_POST['banner_cropped'], "../Uploads/Banner/");
    if ($file) {
        $db_path = str_replace("../", "", $file);
        mysqli_query($koneksi, "
            UPDATE users SET banner='$db_path' WHERE user_id='$user_id'
        ");
    }
}

header("Location: ../profile.php?id=" . $user_id);
exit;
?>
