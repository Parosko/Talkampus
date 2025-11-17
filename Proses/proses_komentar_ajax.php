<?php
session_start();
include "../koneksi.php";

if (!isset($_SESSION['user_id'])) {
    exit("forbidden");
}

$user_id = $_SESSION['user_id'];
$post_id = $_POST['post_id'];
$comment_text = trim($_POST['comment_text']);
if ($comment_text === "") {
    exit("empty");
}

// Simpan ke DB
$stmt = mysqli_prepare($koneksi, "
    INSERT INTO comments (post_id, user_id, comment_text) 
    VALUES (?, ?, ?)
");
mysqli_stmt_bind_param($stmt, "iis", $post_id, $user_id, $comment_text);
mysqli_stmt_execute($stmt);

// Ambil comment_id yang baru
$comment_id = mysqli_insert_id($koneksi);

// Ambil data lengkap komentar untuk dikirim kembali
$q = mysqli_query($koneksi, "
    SELECT comments.*, users.username 
    FROM comments 
    JOIN users ON comments.user_id = users.user_id 
    WHERE comment_id='$comment_id'
");
$c = mysqli_fetch_assoc($q);

?>

<div id="comment-<?php echo $c['comment_id']; ?>" 
     style="border-bottom:1px solid #ddd; margin-bottom:5px; padding-bottom:5px;">

    <strong><?php echo htmlspecialchars($c['username']); ?></strong><br>
    <small><?php echo $c['created_at']; ?></small>

    <p id="comment-text-<?php echo $c['comment_id']; ?>">
        <?php echo nl2br(htmlspecialchars($c['comment_text'])); ?>
    </p>

    <!-- TOMBOL EDIT hanya untuk pemilik komentar -->
    <?php if ($c['user_id'] == $_SESSION['user_id']) : ?>
        <button onclick="editKomentar(<?php echo $c['comment_id']; ?>)">✏️ Edit</button>
    <?php endif; ?>

    <!-- TOMBOL HAPUS untuk pemilik komentar & pemilik postingan -->
    <?php 
    // Ambil owner post
    $postOwner = mysqli_fetch_assoc(mysqli_query(
        $koneksi, "SELECT user_id FROM posts WHERE post_id='$post_id'"
    ))['user_id'];

    if ($c['user_id'] == $_SESSION['user_id'] || $postOwner == $_SESSION['user_id']) : ?>
        <button onclick="hapusKomentar(<?php echo $c['comment_id']; ?>)">🗑️ Hapus</button>
    <?php endif; ?>

</div>
