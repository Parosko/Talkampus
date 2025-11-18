<?php
session_start();
include "koneksi.php";
include "Components/verified_badge.php"; // ← WAJIB untuk badge

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$profile_id = isset($_GET['id']) ? intval($_GET['id']) : $_SESSION['user_id'];
$viewer_id  = $_SESSION['user_id'];

$q = mysqli_query($koneksi, "SELECT * FROM users WHERE user_id='$profile_id'");
$u = mysqli_fetch_assoc($q);

if (!$u) {
    echo "<h2>Profil tidak ditemukan!</h2>";
    exit;
}

// Hitung statistik follow
$followers = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT COUNT(*) AS jml FROM follows WHERE following_id='$profile_id'
"))['jml'];

$following = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT COUNT(*) AS jml FROM follows WHERE follower_id='$profile_id'
"))['jml'];

// Cek apakah viewer sudah follow
$isFollowing = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT follow_id FROM follows
    WHERE follower_id='$viewer_id' AND following_id='$profile_id'
"));
$isFollowing = $isFollowing ? true : false;

$post_count = mysqli_fetch_assoc(
    mysqli_query($koneksi, "SELECT COUNT(*) AS jml FROM posts WHERE user_id='$profile_id'")
)['jml'];

?>
<!DOCTYPE html>
<html>
<head>
    <title>Profil Pengguna</title>
    <link rel="stylesheet" href="Styles/global_style.css">

    <style>
        body { font-family: Arial; background:#f5f5f5; margin:0; }

        .banner {
            width:100%;
            height:200px;
            background-size:cover;
            background-position:center;
            background-repeat:no-repeat;
        }

        .profile-container { width:90%; max-width:800px; margin:20px auto; }
        .profile-pic {
            width:130px; height:130px; border-radius:50%;
            border:4px solid white; margin-top:-70px;
            object-fit:cover; background:#fff;
        }
        .username { font-size:24px; font-weight:bold; margin-top:10px; }
        .bio { color:#555; margin-top:5px; white-space:pre-line; }
        .joined { color:#777; font-size:14px; margin-bottom:5px; }
        .stats { color:#333; font-size:15px; margin-bottom:10px; }

        .follow-btn {
            padding:8px 15px;
            border-radius:6px;
            border:none;
            cursor:pointer;
            font-weight:bold;
            margin-top:10px;
        }
        .follow {
            background:#1d9bf0;
            color:white;
        }
        .unfollow {
            background:#ddd;
            color:#333;
        }

        button { cursor:pointer; }
    </style>
</head>
<body>

<?php include "Components/navbar.php"; ?>
<!-- Banner -->
<div class="banner"
     style="background-image:url('<?= htmlspecialchars($u['banner'] ?: 'default_banner.png') ?>');">
</div>

<div class="profile-container">

    <img class="profile-pic"
         src="<?= htmlspecialchars($u['profile_picture'] ?: 'default_pp.png') ?>">

    <div class="username" style="display:flex; align-items:center; gap:6px;">
    @<?= htmlspecialchars($u['username']) ?>
    <?= renderVerified($u['is_verified'], 20); ?>
    </div>

    <!-- FOLLOW BUTTON (HANYA JIKA BUKAN PROFIL SENDIRI) -->
    <?php if ($viewer_id != $profile_id): ?>
        <button id="followBtn"
                class="follow-btn <?= $isFollowing ? 'unfollow' : 'follow' ?>"
                onclick="toggleFollow(<?= $profile_id ?>)">
            <?= $isFollowing ? '✓ Mengikuti' : '+ Ikuti' ?>
        </button>
    <?php endif; ?>

    <div class="bio">
        <?= $u['bio'] ? nl2br(htmlspecialchars($u['bio'])) : "<i>Belum ada bio</i>"; ?>
    </div>

    <div class="joined">
        Bergabung sejak: <?= date("d M Y", strtotime($u['created_at'])) ?>
    </div>

    <div class="stats">
    <a href="follow_list.php?id=<?= $profile_id ?>&tab=followers" 
       style="text-decoration:none; color:#333; font-weight:bold;">
        Pengikut: <b id="followersCount"><?= $followers ?></b>
    </a>
    ·
    <a href="follow_list.php?id=<?= $profile_id ?>&tab=following" 
       style="text-decoration:none; color:#333; font-weight:bold;">
        Mengikuti: <b><?= $following ?></b>
    </a>
    <br>

    Total Postingan: <b><?= $post_count ?></b>
    </div>


    <?php if ($viewer_id == $profile_id) : ?>
        <a href="edit_profile.php">✏️ Edit Profil</a>
    <?php endif; ?>

    <hr>
    <h3>Postingan</h3>

    <div id="post-container"></div>

    <div id="loader" style="text-align:center; padding:10px; color:#666; display:none;">
        Loading...
    </div>

</div>


<script>
/* -----------------------------------------
   FOLLOW / UNFOLLOW (AJAX)
------------------------------------------ */
function toggleFollow(targetId) {
    const btn = document.getElementById("followBtn");

    const fd = new FormData();
    fd.append("following_id", targetId);

    fetch("Proses/proses_follow_ajax.php", {
        method: "POST",
        body: fd
    })
    .then(r => r.json())
    .then(res => {
        if (res.status !== "ok") return;

        document.getElementById("followersCount").innerText = res.followers;

        // Ubah tombol
        if (res.action === "follow") {
            btn.classList.remove("follow");
            btn.classList.add("unfollow");
            btn.innerText = "✓ Mengikuti";
        } else {
            btn.classList.remove("unfollow");
            btn.classList.add("follow");
            btn.innerText = "+ Ikuti";
        }
    });
}


/* -----------------------------------------
   LIKE SYSTEM (AJAX)
------------------------------------------ */
function toggleLike(postId, button) {
    fetch("Proses/proses_like_ajax.php?post_id=" + postId)
        .then(response => response.text())
        .then(result => {
            let el = document.getElementById("like-count-" + postId);
            let count = parseInt(el.innerText);

            if (result === "liked") {
                button.innerText = "❤️ Unlike";
                el.innerText = count + 1;
            } else {
                button.innerText = "🤍 Like";
                el.innerText = count - 1;
            }
        });
}


/* -----------------------------------------
   SCROLL RESTORE (TIDAK DIUBAH)
------------------------------------------ */
const scrollKey = "profileScroll_<?= $profile_id ?>";
let restoreScroll = null;

if (localStorage.getItem(scrollKey)) {
    restoreScroll = parseInt(localStorage.getItem(scrollKey));
    localStorage.removeItem(scrollKey);
}

window.addEventListener("beforeunload", () => {
    localStorage.setItem(scrollKey, window.scrollY);
});


/* -----------------------------------------
   INFINITE SCROLL (TIDAK DIUBAH)
------------------------------------------ */
let offset = 0;
let loading = false;
const limit = 5;
const profile_id = <?= $profile_id ?>;

function loadMorePosts() {
    if (loading) return;
    loading = true;

    document.getElementById("loader").style.display = "block";

    fetch(`Proses/proses_load_more.php?uid=${profile_id}&offset=${offset}`)
        .then(res => res.text())
        .then(html => {

            document.getElementById("loader").style.display = "none";

            if (html.trim() === "") {
                window.removeEventListener("scroll", handleScroll);
                return;
            }

            document.getElementById("post-container").innerHTML += html;
            offset += limit;
            loading = false;

            if (restoreScroll !== null) {
                window.scrollTo(0, restoreScroll);
                restoreScroll = null;
            }
        });
}

function handleScroll() {
    let scrollPosition = window.innerHeight + window.scrollY;
    let threshold = document.body.offsetHeight - 700;

    if (scrollPosition >= threshold) {
        loadMorePosts();
    }
}

loadMorePosts();
window.addEventListener("scroll", handleScroll);
</script>

</body>
</html>
