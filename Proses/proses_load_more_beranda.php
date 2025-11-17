<?php
session_start();
include "../koneksi.php";

$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;
$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

// Ambil posting + PP + verified
$post_q = mysqli_query($koneksi, "
    SELECT posts.*, 
           users.username, 
           users.profile_picture, 
           users.is_verified,
           users.user_id AS poster_id
    FROM posts
    JOIN users ON posts.user_id = users.user_id
    ORDER BY posts.created_at DESC
    LIMIT $limit OFFSET $offset
");

if (!$post_q || mysqli_num_rows($post_q) == 0) {
    echo "";
    exit;
}

while ($post = mysqli_fetch_assoc($post_q)) {
    $post_id = $post['post_id'];
    $poster_id = $post['poster_id'];
    $pp = $post['profile_picture'] ?: "default_pp.png";

    // gambar
    $img_q = mysqli_query($koneksi, "SELECT * FROM post_images WHERE post_id='$post_id'");

    // like count
    $like_count = mysqli_fetch_assoc(
        mysqli_query($koneksi, "SELECT COUNT(*) AS jml FROM likes WHERE post_id='$post_id'")
    )['jml'] ?? 0;

    $liked = false;
    if ($user_id)
        $liked = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM likes WHERE user_id='$user_id' AND post_id='$post_id'")) > 0;

    // comment count
    $comment_count = mysqli_fetch_assoc(
        mysqli_query($koneksi, "SELECT COUNT(*) AS jml FROM comments WHERE post_id='$post_id'")
    )['jml'] ?? 0;

    ?>

    <div style="border:1px solid #ccc; padding:10px; margin-bottom:10px;" id="post-<?php echo $post_id; ?>">

        <!-- FOTO PROFIL + USERNAME + VERIF BADGE -->
        <a href="profile.php?id=<?php echo $poster_id; ?>" 
           style="display:flex; align-items:center; text-decoration:none; color:black;">

            <img src="<?php echo htmlspecialchars($pp); ?>" 
                 style="width:40px; height:40px; border-radius:50%; object-fit:cover; margin-right:10px;">

            <strong>
                @<?php echo htmlspecialchars($post['username']); ?>
                <?php if ($post['is_verified']) : ?>
                    <span style="color:blue; font-size:18px; margin-left:3px;">✔️</span>
                <?php endif; ?>
            </strong>

        </a>

        <small><?php echo $post['created_at']; ?></small>

        <h4><?php echo htmlspecialchars($post['title']); ?></h4>
        <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>

        <?php while ($img = mysqli_fetch_assoc($img_q)) : ?>
            <img src="<?php echo htmlspecialchars($img['image_url']); ?>" width="200" style="margin-right:5px;">
        <?php endwhile; ?>

        <div class="meta-inline">
            <a href="javascript:void(0);" onclick="toggleLike(<?php echo $post_id; ?>, this)">
                <?php echo $liked ? "❤️ Unlike" : "🤍 Like"; ?>
            </a>
            (<span id="like-count-<?php echo $post_id; ?>"><?php echo $like_count; ?></span>)

            |
            <a href="detail_post.php?id=<?php echo $post_id; ?>">
                💬 <?php echo $comment_count; ?> Komentar
            </a>

            |
            <a href="detail_post.php?id=<?php echo $post_id; ?>">Lihat Detail</a>
        </div>
    </div>

    <?php
}
?>
