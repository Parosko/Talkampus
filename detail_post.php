<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if (!isset($_GET['id'])) {
    echo "Post tidak ditemukan!";
    exit;
}
$post_id = intval($_GET['id']);

$post_query = mysqli_query($koneksi, "
    SELECT posts.*, users.username, users.profile_picture, users.is_verified
    FROM posts 
    JOIN users ON posts.user_id = users.user_id 
    WHERE posts.post_id = '$post_id'
");
$post = mysqli_fetch_assoc($post_query);
if (!$post) {
    echo "Postingan tidak ada.";
    exit;
}

$img_query = mysqli_query($koneksi, "SELECT * FROM post_images WHERE post_id='$post_id'");

$liked = mysqli_num_rows(mysqli_query($koneksi, "
    SELECT * FROM likes WHERE user_id='$user_id' AND post_id='$post_id'
")) > 0;

$like_count = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT COUNT(*) AS jml FROM likes WHERE post_id='$post_id'
"))['jml'];

$comments_query = mysqli_query($koneksi, "
    SELECT comments.*, users.username 
    FROM comments 
    JOIN users ON comments.user_id = users.user_id 
    WHERE comments.post_id='$post_id' 
    ORDER BY comments.created_at ASC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Postingan</title>

    <!-- GLOBAL STYLE -->
    <link rel="stylesheet" href="Styles/global_style.css">

<style>
/* Tidak ada margin body supaya navbar tidak rusak */
body {
    font-family: Arial, sans-serif;
    background: #fafafa;
}

/* Kontainer halaman */
.page-container {
    width: 95%;
    max-width: 900px;
    margin: 40px auto 40px; /* kasih jarak supaya navbar tidak ketiban */
}

/* Gambar posting */
.image-gallery {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 10px;
}
.image-gallery img {
    width: 200px;
    border-radius: 10px;
    border: 1px solid #ddd;
    transition: transform 0.2s ease;
    cursor: pointer;
}
.image-gallery img:hover {
    transform: scale(1.05);
}

/* LIGHTBOX */
.lightbox {
    display: none;
    position: fixed;
    z-index: 9999;
    padding-top: 50px;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.9);
    text-align: center;
}
.lightbox img {
    max-width: 90%;
    max-height: 80vh;
    border-radius: 10px;
}
.lightbox-close {
    position: absolute;
    top: 20px;
    right: 40px;
    color: white;
    font-size: 40px;
    cursor: pointer;
}

/* Tombol */
button { cursor: pointer; }

.delete-btn {
    background-color: red;
    color: white;
    border: none;
    padding: 6px 10px;
    border-radius: 6px;
}
.edit-btn {
    background-color: #007bff;
    color: white;
    border: none;
    padding: 6px 10px;
    border-radius: 6px;
}
.delete-btn:hover { background-color: darkred; }
.edit-btn:hover { background-color: #0056b3; }
</style>
</head>
<body class="with-navbar">

<?php include "Components/navbar.php"; ?>


<div class="page-container">

<?php
// Tombol kembali
if (isset($_GET['from']) && $_GET['from'] === "profile" && isset($_GET['uid'])) {
    echo '<a href="profile.php?id='.$_GET['uid'].'">⬅ Kembali ke Profil</a>';
} else {
    echo '<a href="beranda.php">⬅ Kembali ke Beranda</a>';
}
?>

<hr>

<!-- ======================= -->
<!--  USER INFO SECTION     -->
<!-- ======================= -->
<div style="
    display:flex;
    align-items:center;
    gap:12px;
    margin-bottom:15px;
">

    <!-- Foto Profil -->
    <a href="profile.php?id=<?= $post['user_id']; ?>">
        <img src="<?= $post['profile_picture'] ? $post['profile_picture'] : 'default_pp.png'; ?>"
             style="width:55px; height:55px; border-radius:50%; object-fit:cover; border:2px solid #eee;">
    </a>

    <!-- Username + Tanggal -->
    <div>
        <a href="profile.php?id=<?= $post['user_id']; ?>" 
           style="text-decoration:none; color:black; font-weight:bold; font-size:17px;">
           <?= htmlspecialchars($post['username']); ?>
            <?php if ($post['is_verified']) : ?>
            <span style="color:blue; font-size:18px; margin-left:4px;">✔️</span>
            <?php endif; ?>

        </a>
        <div style="font-size:13px; color:#777;">
            Diposting pada <?= $post['created_at']; ?>
        </div>
    </div>
</div>

<h2><?= htmlspecialchars($post['title']); ?></h2>


<p><?= nl2br(htmlspecialchars($post['content'])); ?></p>

<?php if ($post['user_id'] == $_SESSION['user_id']) : ?>
    <div class="post-actions">
        <a href="edit_post.php?id=<?= $post_id; ?>" class="edit-btn">✏️ Edit Postingan</a>
        <form action="Proses/proses_delete_post.php" method="POST" style="display:inline;" 
              onsubmit="return confirm('Yakin ingin menghapus postingan ini?');">
            <input type="hidden" name="post_id" value="<?= $post_id; ?>">
            <button type="submit" class="delete-btn">🗑️ Hapus</button>
        </form>
    </div>
<?php endif; ?>

<!-- GALERI GAMBAR -->
<?php if (mysqli_num_rows($img_query) > 0): ?>
<div class="image-gallery">
    <?php while ($img = mysqli_fetch_assoc($img_query)) : ?>
        <img src="<?= htmlspecialchars($img['image_url']); ?>" 
             onclick="openLightbox('<?= htmlspecialchars($img['image_url']); ?>')">
    <?php endwhile; ?>
</div>
<?php endif; ?>

<br>

<button id="like-btn" onclick="toggleLike(<?= $post_id; ?>, this)">
    <?= $liked ? "❤️ Unlike" : "🤍 Like"; ?>
</button>
(<span id="like-count"><?= $like_count; ?></span>)

<hr>

<h3>Komentar</h3>

<div id="comments-container">
<?php while ($c = mysqli_fetch_assoc($comments_query)) : ?>
    <div id="comment-<?= $c['comment_id']; ?>" 
         style="border-bottom:1px solid #ddd; margin-bottom:5px; padding-bottom:5px;">

        <strong><?= htmlspecialchars($c['username']); ?></strong><br>
        <small><?= $c['created_at']; ?></small>

        <p id="comment-text-<?= $c['comment_id']; ?>">
            <?= nl2br(htmlspecialchars($c['comment_text'])); ?>
        </p>

        <?php if ($c['user_id'] == $_SESSION['user_id']) : ?>
            <button onclick="editKomentar(<?= $c['comment_id']; ?>)">✏️ Edit</button>
        <?php endif; ?>

        <?php if ($c['user_id'] == $_SESSION['user_id'] || $post['user_id'] == $_SESSION['user_id']) : ?>
            <button onclick="hapusKomentar(<?= $c['comment_id']; ?>)">🗑️ Hapus</button>
        <?php endif; ?>

    </div>
<?php endwhile; ?>
</div>

<h4>Tulis Komentar</h4>
<textarea id="comment-text" rows="3" cols="50" placeholder="Tulis komentar..."></textarea><br>
<button onclick="kirimKomentar()">Kirim</button>

</div> <!-- /page-container -->

<!-- LIGHTBOX -->
<div id="lightbox" class="lightbox" onclick="closeLightbox()">
    <span class="lightbox-close">&times;</span>
    <img id="lightbox-img">
</div>

<script>
// LIGHTBOX
function openLightbox(src) {
    document.getElementById("lightbox-img").src = src;
    document.getElementById("lightbox").style.display = "block";
}
function closeLightbox() {
    document.getElementById("lightbox").style.display = "none";
}

// LIKE
function toggleLike(postId, button) {
    fetch("Proses/proses_like_ajax.php?post_id=" + postId)
        .then(res => res.text())
        .then(result => {
            let count = parseInt(document.getElementById("like-count").innerText);

            if (result === "liked") {
                button.innerText = "❤️ Unlike";
                document.getElementById("like-count").innerText = count + 1;
            } else if (result === "unliked") {
                button.innerText = "🤍 Like";
                document.getElementById("like-count").innerText = count - 1;
            }
        });
}

// KIRIM KOMENTAR
function kirimKomentar() {
    const text = document.getElementById('comment-text').value.trim();
    if (!text) return alert("Komentar tidak boleh kosong!");

    const fd = new FormData();
    fd.append('post_id', <?= $post_id ?>);
    fd.append('comment_text', text);

    fetch('Proses/proses_komentar_ajax.php', { method: 'POST', body: fd })
        .then(res => res.text())
        .then(html => {
            document.getElementById('comments-container').innerHTML += html;
            document.getElementById('comment-text').value = "";
        });
}

// EDIT KOMENTAR
function editKomentar(id) {
    const p = document.getElementById("comment-text-" + id);
    const old = p.innerText.trim();

    p.innerHTML = `
        <textarea id="edit-input-${id}" rows="3" style="width:100%;">${old}</textarea><br>
        <button onclick="simpanEditKomentar(${id})">💾 Simpan</button>
        <button onclick="batalEditKomentar(${id}, '${old.replace(/'/g, "\\'")}')">Batal</button>
    `;
}
function batalEditKomentar(id, old) {
    document.getElementById("comment-text-" + id).innerHTML = old.replace(/\n/g, "<br>");
}
function simpanEditKomentar(id) {
    const newText = document.getElementById("edit-input-" + id).value.trim();
    if (!newText) return alert("Komentar tidak boleh kosong!");

    const fd = new FormData();
    fd.append('comment_id', id);
    fd.append('comment_text', newText);

    fetch('Proses/proses_edit_komentar_ajax.php', { method: 'POST', body: fd })
        .then(res => res.text())
        .then(ok => {
            if (ok === "ok") {
                document.getElementById("comment-text-" + id).innerHTML = newText.replace(/\n/g, "<br>");
            }
        });
}

// HAPUS KOMENTAR
function hapusKomentar(id) {
    if (!confirm("Yakin ingin menghapus komentar ini?")) return;

    const fd = new FormData();
    fd.append('comment_id', id);

    fetch('Proses/proses_delete_komentar_ajax.php', { method: 'POST', body: fd })
        .then(res => res.text())
        .then(ok => {
            if (ok === "ok") {
                const el = document.getElementById('comment-' + id);
                if (el) el.remove();
            }
        });
}
</script>

</body>
</html>
