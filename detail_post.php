<?php
session_start();
include "koneksi.php";
include "Components/verified_badge.php"; // ✔ WAJIB UNTUK BADGE

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

// =============================
//  AMBIL DATA POST
// =============================
$post_query = mysqli_query($koneksi, "
    SELECT posts.*, users.username, users.profile_picture, users.is_verified, users.user_id AS poster_id
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

// LIKE INFO
$liked = mysqli_num_rows(mysqli_query($koneksi, "
    SELECT * FROM likes WHERE user_id='$user_id' AND post_id='$post_id'
")) > 0;

$like_count = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT COUNT(*) AS jml FROM likes WHERE post_id='$post_id'
"))['jml'];

// KOMENTAR
$comments_query = mysqli_query($koneksi, "
    SELECT comments.*, users.username, users.profile_picture, users.is_verified, users.user_id
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
body {
    background: #fafafa;
    font-family: Arial, sans-serif;
}

/* Container */
.page-container {
    width: 95%;
    max-width: 900px;
    margin: 40px auto;
}

/* Gambar */
.image-gallery img {
    width: 200px;
    border-radius: 10px;
    cursor: pointer;
    border: 1px solid #ddd;
    transition: .2s;
}
.image-gallery img:hover {
    transform: scale(1.05);
}

/* Lightbox */
.lightbox {
    display:none;
    position:fixed;
    z-index:9999;
    left:0;top:0;
    width:100%;height:100%;
    background:rgba(0,0,0,0.9);
    text-align:center;
    padding-top:50px;
}
.lightbox img {
    max-width:90%;
    max-height:80vh;
    border-radius:10px;
}
.lightbox-close {
    position:absolute;
    top:20px;
    right:40px;
    font-size:40px;
    color:white;
    cursor:pointer;
}

/* Komentar */
.comment-box {
    border-bottom:1px solid #ddd;
    padding:10px 0;
}
.comment-header {
    display:flex;
    align-items:center;
    gap:8px;
}
.comment-header img {
    width:32px;
    height:32px;
    border-radius:50%;
    object-fit:cover;
}
.comment-username {
    font-weight:bold;
    display:flex;
    align-items:center;
    gap:4px;
}
</style>
</head>

<body class="with-navbar">

<?php include "Components/navbar.php"; ?>

<div class="page-container">

<?php 
// tombol kembali
if (isset($_GET['from']) && $_GET['from'] === "profile" && isset($_GET['uid'])) {
    echo '<a href="profile.php?id='.$_GET['uid'].'">⬅ Kembali ke Profil</a>';
} else {
    echo '<a href="beranda.php">⬅ Kembali ke Beranda</a>';
}
?>
<hr>

<!-- ============================= -->
<!--   POSTER INFO                -->
<!-- ============================= -->
<div style="display:flex; align-items:center; gap:12px; margin-bottom:15px;">
    <a href="profile.php?id=<?= $post['poster_id']; ?>">
        <img src="<?= $post['profile_picture'] ?: 'default_pp.png'; ?>"
             style="width:55px;height:55px;border-radius:50%;object-fit:cover;border:2px solid #eee;">
    </a>

    <div>
        <a href="profile.php?id=<?= $post['poster_id']; ?>" 
           style="font-size:17px;font-weight:bold;color:black;display:flex;align-items:center;gap:6px;text-decoration:none;">
            <?= htmlspecialchars($post['username']); ?>
            <?= renderVerified($post['is_verified'], 18); ?>
        </a>

        <div style="color:#777;font-size:13px;">
            Diposting pada <?= $post['created_at']; ?>
        </div>
    </div>
</div>


<h2><?= htmlspecialchars($post['title']); ?></h2>

<p><?= nl2br(htmlspecialchars($post['content'])); ?></p>

<?php if ($post['poster_id'] == $user_id): ?>
    <div style="margin-bottom:10px;">
        <a href="edit_post.php?id=<?= $post_id; ?>" class="edit-btn">✏️ Edit Postingan</a>
        <form action="Proses/proses_delete_post.php" method="POST" style="display:inline;"
              onsubmit="return confirm('Hapus postingan ini?')">
            <input type="hidden" name="post_id" value="<?= $post_id; ?>">
            <button type="submit" class="delete-btn">🗑️ Hapus</button>
        </form>
    </div>
<?php endif; ?>

<!-- GAMBAR -->
<?php if (mysqli_num_rows($img_query) > 0): ?>
<div class="image-gallery">
<?php while ($img = mysqli_fetch_assoc($img_query)) : ?>
    <img src="<?= htmlspecialchars($img['image_url']); ?>"
         onclick="openLightbox('<?= htmlspecialchars($img['image_url']); ?>')">
<?php endwhile; ?>
</div>
<?php endif; ?>

<br>

<button id="like-btn" onclick="toggleLike(<?= $post_id ?>, this)">
    <?= $liked ? "❤️ Unlike" : "🤍 Like" ?>
</button>
(<span id="like-count"><?= $like_count ?></span>)

<hr>

<!-- ============================= -->
<!--           KOMENTAR           -->
<!-- ============================= -->
<h3>Komentar</h3>

<div id="comments-container">
<?php while ($c = mysqli_fetch_assoc($comments_query)) : ?>
<div class="comment-box" id="comment-<?= $c['comment_id']; ?>">

    <div class="comment-header">
        <a href="profile.php?id=<?= $c['user_id']; ?>">
            <img src="<?= $c['profile_picture'] ?: 'default_pp.png'; ?>">
        </a>

        <!-- Username + Verified -->
        <a href="profile.php?id=<?= $c['user_id']; ?>" 
           class="comment-username" style="text-decoration:none;color:black;">
            <?= htmlspecialchars($c['username']); ?>
            <?= renderVerified($c['is_verified'], 15); ?>
        </a>

        <small style="color:#777;"><?= $c['created_at']; ?></small>
    </div>

    <!-- Isi komentar -->
    <p id="comment-text-<?= $c['comment_id']; ?>">
        <?= nl2br(htmlspecialchars($c['comment_text'])); ?>
    </p>

    <!-- Tombol edit / hapus -->
    <?php if ($c['user_id'] == $user_id): ?>
        <button onclick="editKomentar(<?= $c['comment_id']; ?>)">✏️ Edit</button>
    <?php endif; ?>

    <?php if ($c['user_id'] == $user_id || $post['poster_id'] == $user_id): ?>
        <button onclick="hapusKomentar(<?= $c['comment_id']; ?>)">🗑️ Hapus</button>
    <?php endif; ?>

</div>
<?php endwhile; ?>
</div>

<h4>Tulis Komentar</h4>
<textarea id="comment-text" rows="3" style="width:100%;" placeholder="Tulis komentar..."></textarea><br>
<button onclick="kirimKomentar()">Kirim</button>


</div> <!-- page-container -->


<!-- LIGHTBOX -->
<div id="lightbox" class="lightbox" onclick="closeLightbox()">
    <span class="lightbox-close">&times;</span>
    <img id="lightbox-img">
</div>

<script>
// ================= LIGHTBOX =================
function openLightbox(src){
    document.getElementById("lightbox-img").src = src;
    document.getElementById("lightbox").style.display = "block";
}
function closeLightbox(){
    document.getElementById("lightbox").style.display = "none";
}

// ================= LIKE =================
function toggleLike(postId, btn){
    fetch("Proses/proses_like_ajax.php?post_id="+postId)
    .then(r=>r.text())
    .then(res=>{
        let count = parseInt(document.getElementById("like-count").innerText);

        if(res === "liked"){
            btn.innerText = "❤️ Unlike";
            document.getElementById("like-count").innerText = count + 1;
        } else {
            btn.innerText = "🤍 Like";
            document.getElementById("like-count").innerText = count - 1;
        }
    });
}

// ================= KOMENTAR: TAMBAH =================
function kirimKomentar(){
    let txt = document.getElementById("comment-text").value.trim();
    if(!txt) return alert("Komentar tidak boleh kosong!");

    let fd = new FormData();
    fd.append('post_id', <?= $post_id ?>);
    fd.append('comment_text', txt);

    fetch("Proses/proses_komentar_ajax.php",{method:'POST',body:fd})
    .then(r=>r.text())
    .then(html=>{
        document
            .getElementById("comments-container")
            .insertAdjacentHTML("beforeend", html);

        document.getElementById("comment-text").value = "";
    });
}

// ================= KOMENTAR: EDIT =================
function editKomentar(id){
    let p = document.getElementById("comment-text-"+id);
    let old = p.innerText.trim();

    p.innerHTML = `
        <textarea id="edit-input-${id}" style="width:100%;" rows="3">${old}</textarea>
        <br>
        <button onclick="simpanEditKomentar(${id})">💾 Simpan</button>
        <button onclick="batalEditKomentar(${id}, \`${old.replace(/`/g,"\\`")}\`)">Batal</button>
    `;
}

function batalEditKomentar(id, old){
    document.getElementById("comment-text-"+id).innerHTML = old.replace(/\n/g,"<br>");
}

function simpanEditKomentar(id){
    let newText = document.getElementById("edit-input-"+id).value.trim();
    if(!newText) return alert("Komentar tidak boleh kosong!");

    let fd = new FormData();
    fd.append("comment_id", id);
    fd.append("comment_text", newText);

    fetch("Proses/proses_edit_komentar_ajax.php",{method:"POST",body:fd})
    .then(r=>r.text())
    .then(res=>{
        if(res === "ok"){
            document.getElementById("comment-text-"+id).innerHTML = newText.replace(/\n/g,"<br>");
        }
    });
}

// ================= KOMENTAR: DELETE =================
function hapusKomentar(id){
    if(!confirm("Hapus komentar ini?")) return;

    let fd = new FormData();
    fd.append("comment_id", id);

    fetch("Proses/proses_delete_komentar_ajax.php",{method:"POST",body:fd})
    .then(r=>r.text())
    .then(res=>{
        if(res === "ok"){
            document.getElementById("comment-"+id).remove();
        }
    });
}
</script>

</body>
</html>
