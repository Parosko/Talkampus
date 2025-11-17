<?php
session_start();
include "../koneksi.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if (empty($_POST['title']) || empty($_POST['content'])) {
    header("Location: ../beranda.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$title = mysqli_real_escape_string($koneksi, $_POST['title']);
$content = mysqli_real_escape_string($koneksi, $_POST['content']);

// Simpan postingan dulu
$insertPost = mysqli_query($koneksi, "
    INSERT INTO posts (user_id, title, content, created_at)
    VALUES ('$user_id', '$title', '$content', NOW())
");

if ($insertPost) {
    $post_id = mysqli_insert_id($koneksi);

    // --- Konfigurasi upload ---
    $upload_dir_fs = "../Uploads/"; // lokasi penyimpanan fisik
    $upload_dir_db = "Uploads/";    // path relatif untuk database
    if (!is_dir($upload_dir_fs)) mkdir($upload_dir_fs, 0777, true);

    // --- Proses upload banyak gambar ---
    if (!empty($_FILES['images']['name'][0])) {
        $total = count($_FILES['images']['name']);
        $limit = min($total, 4); // maksimal 4 gambar
        $allowed = ['image/jpeg','image/png','image/gif','image/webp'];

        for ($i = 0; $i < $limit; $i++) {
            if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;

            $tmp = $_FILES['images']['tmp_name'][$i];
            $name = basename($_FILES['images']['name'][$i]);
            $type = $_FILES['images']['type'][$i];

            // validasi tipe file
            if (!in_array($type, $allowed)) continue;

            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $filename = time() . "_" . bin2hex(random_bytes(5)) . "." . $ext;
            $target = $upload_dir_fs . $filename;

            if (move_uploaded_file($tmp, $target)) {
                $image_path_db = $upload_dir_db . $filename;
                $imgEsc = mysqli_real_escape_string($koneksi, $image_path_db);

                mysqli_query($koneksi, "
                    INSERT INTO post_images (post_id, image_url, uploaded_at)
                    VALUES ('$post_id', '$imgEsc', NOW())
                ");
            }
        }
    }

    // redirect ke detail post
    header("Location: ../detail_post.php?id=$post_id");
    exit;
} else {
    echo "Gagal menyimpan postingan!";
}
?>
