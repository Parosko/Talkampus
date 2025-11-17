<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$post_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Ambil data postingan utama
$query = mysqli_query($koneksi, "SELECT * FROM posts WHERE post_id='$post_id'");
$post = mysqli_fetch_assoc($query);
if (!$post) {
    echo "❌ Postingan tidak ditemukan!";
    exit;
}

// Pastikan user pemilik postingan
if ($post['user_id'] != $user_id) {
    echo "⚠️ Kamu tidak berhak mengedit postingan ini!";
    exit;
}

// Ambil semua gambar yang terkait dengan postingan ini
$img_query = mysqli_query($koneksi, "SELECT * FROM post_images WHERE post_id='$post_id'");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Postingan</title>

      <!-- GLOBAL STYLE -->
    <link rel="stylesheet" href="Styles/global_style.css">

</head>
<style>

        .page-container {
            width: 95%;
            max-width: 800px;
            margin: 40px auto 40px; /* beri jarak dari navbar */
        }

    </style>
<body>
    <?php include "Components/navbar.php"; ?>

    
    <div class="page-container">
    <h2>Edit Postingan</h2>

    <form action="Proses/proses_edit_post.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">

        <label>Judul:</label><br>
        <input type="text" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" required><br><br>

        <label>Isi Postingan:</label><br>
        <textarea name="content" rows="5" cols="50" required><?php echo htmlspecialchars($post['content']); ?></textarea><br><br>

        <p><strong>Gambar yang sudah ada:</strong></p>
        <?php
        $img_count = 0;
        while ($img = mysqli_fetch_assoc($img_query)) :
            $img_count++;
        ?>
            <div style="margin-bottom:10px;">
                <img src="<?php echo htmlspecialchars($img['image_url']); ?>" width="150"><br>
                <label>
                    <input type="checkbox" name="hapus_gambar[]" value="<?php echo $img['image_id']; ?>">
                    Hapus gambar ini
                </label>
            </div>
        <?php endwhile; ?>

        <p><strong>Tambah Gambar (opsional, maksimal total 4 termasuk yang sudah ada):</strong></p>
        <input type="file" name="images[]" accept="image/*" multiple><br><br>

        <button type="submit">💾 Simpan Perubahan</button>
        <a href="detail_post.php?id=<?php echo $post_id; ?>">Batal</a>
    </form>
        </div>
</body>
</html>
