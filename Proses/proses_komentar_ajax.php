<?php
session_start();
include "../koneksi.php";
include "../Components/verified_badge.php"; // ✔ penting untuk badge

if (!isset($_SESSION['user_id'])) {
    exit("forbidden");
}

$user_id = $_SESSION['user_id'];
$post_id = intval($_POST['post_id']);
$comment_text = trim($_POST['comment_text']);

if ($comment_text === "") {
    exit("empty");
}

// SIMPAN KOMENTAR
$stmt = mysqli_prepare($koneksi, "
    INSERT INTO comments (post_id, user_id, comment_text) 
    VALUES (?, ?, ?)
");
mysqli_stmt_bind_param($stmt, "iis", $post_id, $user_id, $comment_text);
mysqli_stmt_execute($stmt);

$comment_id = mysqli_insert_id($koneksi);

// AMBIL DATA KOMENTAR + USER
$q = mysqli_query($koneksi, "
    SELECT comments.*, users.username, users.profile_picture, users.is_verified, users.user_id
    FROM comments 
    JOIN users ON comments.user_id = users.user_id 
    WHERE comments.comment_id = '$comment_id'
");
$c = mysqli_fetch_assoc($q);

// AMBIL OWNER POST UTK IZIN HAPUS
$postOwner = mysqli_fetch_assoc(mysqli_query(
    $koneksi, "SELECT user_id FROM posts WHERE post_id='$post_id'"
))['user_id'];

// Default profile picture
$pp = $c['profile_picture'] ?: "default_pp.png";
?>

<div class="comment-box" id="comment-<?php echo $c['comment_id']; ?>">

    <div class="comment-header">
        <a href="profile.php?id=<?= $c['user_id']; ?>">
            <img src="<?= $pp; ?>">
        </a>

        <a href="profile.php?id=<?= $c['user_id']; ?>" 
           class="comment-username" style="text-decoration:none;color:black;">
            <?= htmlspecialchars($c['username']); ?>
            <?= renderVerified($c['is_verified'], 15); ?>
        </a>

        <small style="color:#777;"><?= $c['created_at']; ?></small>
    </div>

    <p id="comment-text-<?= $c['comment_id']; ?>">
        <?= nl2br(htmlspecialchars($c['comment_text'])); ?>
    </p>

    <?php if ($c['user_id'] == $_SESSION['user_id']): ?>
        <button onclick="editKomentar(<?= $c['comment_id']; ?>)">✏️ Edit</button>
    <?php endif; ?>

    <?php if ($c['user_id'] == $_SESSION['user_id'] || $postOwner == $_SESSION['user_id']): ?>
        <button onclick="hapusKomentar(<?= $c['comment_id']; ?>)">🗑️ Hapus</button>
    <?php endif; ?>
</div>
