<?php
session_start();
include "../koneksi.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$post_id = intval($_POST['post_id']);
$title = mysqli_real_escape_string($koneksi, $_POST['title']);
$content = mysqli_real_escape_string($koneksi, $_POST['content']);

// Pastikan post milik user
$post_check = mysqli_query($koneksi, "SELECT * FROM posts WHERE post_id='$post_id' AND user_id='$user_id'");
if (mysqli_num_rows($post_check) == 0) {
    echo "❌ Akses ditolak.";
    exit;
}

// Update teks
mysqli_query($koneksi, "
    UPDATE posts 
    SET title='$title', content='$content'
    WHERE post_id='$post_id'
");

// Hapus gambar yang dipilih
if (isset($_POST['hapus_gambar'])) {
    foreach ($_POST['hapus_gambar'] as $img_id) {
        $img_id = intval($img_id);
        $q = mysqli_query($koneksi, "SELECT image_url FROM post_images WHERE image_id='$img_id' AND post_id='$post_id'");
        if ($r = mysqli_fetch_assoc($q)) {
            $path = "../" . $r['image_url'];
            if (file_exists($path)) unlink($path);
        }
        mysqli_query($koneksi, "DELETE FROM post_images WHERE image_id='$img_id'");
    }
}

// Hitung jumlah gambar yang tersisa
$current_imgs = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM post_images WHERE post_id='$post_id'"));
$remaining_slots = max(0, 4 - $current_imgs);

// Upload gambar baru (jika masih bisa)
if ($remaining_slots > 0 && isset($_FILES['images'])) {
    $upload_dir_fs = "../Uploads/";
    $upload_dir_db = "Uploads/";
    if (!is_dir($upload_dir_fs)) mkdir($upload_dir_fs, 0777, true);

    $total = count($_FILES['images']['name']);
    $limit = min($total, $remaining_slots);
    $allowed = ['image/jpeg','image/png','image/gif','image/webp'];

    for ($i = 0; $i < $limit; $i++) {
        if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;
        $tmp = $_FILES['images']['tmp_name'][$i];
        $type = $_FILES['images']['type'][$i];
        if (!in_array($type, $allowed)) continue;

        $ext = pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION);
        $filename = time() . "_" . bin2hex(random_bytes(5)) . "." . $ext;
        $target = $upload_dir_fs . $filename;

        if (move_uploaded_file($tmp, $target)) {
            $path_db = $upload_dir_db . $filename;
            mysqli_query($koneksi, "
                INSERT INTO post_images (post_id, image_url, uploaded_at)
                VALUES ('$post_id', '$path_db', NOW())
            ");
        }
    }
}

header("Location: ../detail_post.php?id=$post_id");
exit;
?>
