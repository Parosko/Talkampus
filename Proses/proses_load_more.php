<?php
session_start();
include "../koneksi.php";

if (!isset($_GET['uid']) || !isset($_GET['offset'])) {
    echo "";
    exit;
}

$profile_id = intval($_GET['uid']);
$offset     = intval($_GET['offset']);
$limit      = 5; // jumlah posting per load

// Ambil posting berikutnya
$post_q = mysqli_query($koneksi, "
    SELECT posts.*, users.username 
    FROM posts
    JOIN users ON posts.user_id = users.user_id
    WHERE posts.user_id = '$profile_id'
    ORDER BY posts.created_at DESC
    LIMIT $limit OFFSET $offset
");

// Kalau tidak ada postingan lagi
if (mysqli_num_rows($post_q) == 0) {
    echo "";
    exit;
}

// Template post
while ($p = mysqli_fetch_assoc($post_q)) {

    $pid = $p['post_id'];

    // Ambil gambar
    $imgs = mysqli_query($koneksi, "SELECT * FROM post_images WHERE post_id='$pid'");

    // Hitung like
    $likes = mysqli_fetch_assoc(mysqli_query($koneksi, "
        SELECT COUNT(*) AS jml FROM likes WHERE post_id='$pid'
    "))['jml'];

    // Hitung komentar
    $comments = mysqli_fetch_assoc(mysqli_query($koneksi, "
        SELECT COUNT(*) AS jml FROM comments WHERE post_id='$pid'
    "))['jml'];

    // Cetak HTML postingan
    ?>

<div class="post-box">

    <strong><?= htmlspecialchars($p['title']) ?></strong><br>
    <small><?= $p['created_at'] ?></small>

    <p><?= nl2br(htmlspecialchars($p['content'])) ?></p>

    <?php while ($img = mysqli_fetch_assoc($imgs)) : ?>
        <img src="<?= htmlspecialchars($img['image_url']) ?>" style="width:180px; border-radius:8px; margin-right:5px;">
    <?php endwhile; ?>


    <!-- 🔥 TOMBOL LIKE -->
    <button onclick="toggleLike(<?= $pid ?>, this)">
        <?= (mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM likes WHERE user_id='{$_SESSION['user_id']}' AND post_id='$pid'")) > 0)
            ? "❤️ Unlike"
            : "🤍 Like"
        ?>
    </button>

    &nbsp;
    ❤️ <span id="like-count-<?= $pid ?>"><?= $likes ?></span>

    &nbsp;·&nbsp; 💬 <?= $comments ?>


    <br><br>
    <a href="/Talkampus/detail_post.php?id=<?= $pid ?>&from=profile&uid=<?= $profile_id ?>">🔍 Lihat Detail</a>

</div>


<?php
}
?>
