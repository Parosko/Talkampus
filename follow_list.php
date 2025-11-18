<?php
session_start();
include "koneksi.php";
include "Components/verified_badge.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$viewer = $_SESSION['user_id'];
$profile_id = isset($_GET['id']) ? intval($_GET['id']) : $viewer;

$tab = isset($_GET['tab']) ? $_GET['tab'] : "followers";
if (!in_array($tab, ["followers", "following"])) {
    $tab = "followers";
}

// Ambil data user
$q = mysqli_query($koneksi, "SELECT * FROM users WHERE user_id='$profile_id'");
$u = mysqli_fetch_assoc($q);

if (!$u) {
    echo "<h2>User tidak ditemukan!</h2>";
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Daftar Follow</title>
    <link rel="stylesheet" href="Styles/global_style.css">
</head>
<body>

<?php include "Components/navbar.php"; ?>

<div style="width:90%;max-width:600px;margin:20px auto;">

    <!-- NAMA USER -->
    <h2>
        @<?= htmlspecialchars($u['username']) ?>
        <?= renderVerified($u['is_verified'], 18); ?>
    </h2>

    <!-- TAB -->
    <div style="display:flex;gap:20px;margin-bottom:15px;">
        <a href="follow_list.php?id=<?= $profile_id ?>&tab=followers"
           style="font-weight:<?= $tab=='followers'?'bold':'normal' ?>;">
           Followers
        </a>

        <a href="follow_list.php?id=<?= $profile_id ?>&tab=following"
           style="font-weight:<?= $tab=='following'?'bold':'normal' ?>;">
           Following
        </a>
    </div>

    <hr>

    <!-- LIST FOLLOW -->
    <div id="follow-container"></div>

    <!-- LOADING -->
    <div id="loader" style="text-align:center;color:#555;display:none;">Loading...</div>

</div>

<script>
let offset = 0;
let loading = false;
const limit = 10;
const uid = <?= $profile_id ?>;
const tab = "<?= $tab ?>";

function loadMore() {
    if (loading) return;
    loading = true;

    document.getElementById("loader").style.display = "block";

    fetch(`Proses/proses_follow_load.php?id=${uid}&tab=${tab}&offset=${offset}`)
        .then(r => r.text())
        .then(html => {
            document.getElementById("loader").style.display = "none";

            if (html.trim() === "") {
                window.removeEventListener("scroll", handleScroll);
                return;
            }

            document.getElementById("follow-container").innerHTML += html;
            offset += limit;
            loading = false;
        });
}

function handleScroll() {
    let scrollPos = window.innerHeight + window.scrollY;
    let threshold = document.body.offsetHeight - 600;

    if (scrollPos >= threshold) {
        loadMore();
    }
}

// FOLLOW / UNFOLLOW AJAX
function toggleFollow(targetId, btn) {

    fetch("Proses/proses_follow_ajax.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "following_id=" + targetId
    })
    .then(r => r.json())
    .then(res => {

        if (res.status === "ok") {
            if (res.action === "follow") {
                btn.innerText = "Following";
            } else if (res.action === "unfollow") {
                btn.innerText = "Follow";
            }
        }
    });
}


loadMore();
window.addEventListener("scroll", handleScroll);
</script>

</body>
</html>
