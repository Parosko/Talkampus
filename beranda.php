<?php
session_start();
include "koneksi.php";
include "Components/verified_badge.php"; // fungsi badge

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$user_query = mysqli_query($koneksi, "SELECT * FROM users WHERE user_id='$user_id'");
$user = mysqli_fetch_assoc($user_query);

// Ambil PP (fallback ke default)
$pp = $user["profile_picture"] ? $user["profile_picture"] : "default_pp.png";
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Beranda</title>

    <!-- GLOBAL STYLE -->
    <link rel="stylesheet" href="Styles/global_style.css">

    <style>
        /* Tambahan kecil spesifik beranda */

        .page-container {
            width: 95%;
            max-width: 800px;
            margin: 50px auto 40px;
        }

        .welcome-box {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .welcome-pp {
            width: 65px;
            height: 65px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #ddd;
        }

        #preview img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        #preview div {
            display: inline-block;
            margin: 5px;
            position: relative;
        }

        #preview button {
            position: absolute;
            top: -5px;
            right: -5px;
            background: red;
            border: none;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        #loader {
            text-align: center;
            padding: 10px;
            color: #666;
            display: none;
        }

        textarea,
        input[type="text"] {
            width: 100%;
        }
    </style>
</head>

<body class="with-navbar">
<?php include "Components/navbar.php"; ?>
<div class="page-container">

<!-- WELCOME BOX -->
<div class="welcome-box">
    <a href="profile.php?id=<?= $user_id ?>" class="welcome-pp-link">
        <img src="<?= htmlspecialchars($pp); ?>" class="welcome-pp">
    </a>

    <div class="welcome-text">
        <h1 style="display:flex; align-items:center; gap:6px;">
            Selamat datang, <?= htmlspecialchars($user['username']); ?>!
            <?= renderVerified($user['is_verified'], 20); ?>
        </h1>
        <p class="welcome-caption">Mau lihat apa hari ini?</p>
    </div>
</div>

<hr>

<!-- FORM POSTINGAN -->
<h3>Buat Postingan Baru</h3>

<form action="Proses/proses_post.php" method="POST" enctype="multipart/form-data" id="postForm">

    <label>Judul:</label>
    <input type="text" name="title" required>

    <br><br>

    <label>Isi Post:</label>
    <textarea name="content" rows="4" required></textarea>

    <br><br>

    <label>Upload Gambar (maks 4):</label><br>
    <input type="file" id="imageInput" accept="image/*" hidden>

    <button type="button" onclick="document.getElementById('imageInput').click()">Pilih Gambar</button>
    <p>Maksimal 4 gambar. Kamu bisa pilih satu per satu.</p>

    <div id="preview"></div>
    <div id="fileContainer"></div>

    <button type="submit">Post</button>
</form>

<hr>

<!-- POSTINGAN -->
<h3>Postingan Terbaru</h3>

<div id="post-container"></div>

<div id="loader">Loading...</div>

</div> <!-- END PAGE CONTAINER -->


<script>
/* ============================================================
   PREVIEW GAMBAR
============================================================ */
let selectedFiles = [];
const imageInput = document.getElementById("imageInput");
const preview = document.getElementById("preview");
const fileContainer = document.getElementById("fileContainer");

imageInput.addEventListener("change", function() {
    const newFiles = Array.from(this.files);

    if (selectedFiles.length + newFiles.length > 4) {
        alert("Maksimal 4 gambar!");
        return;
    }

    selectedFiles = selectedFiles.concat(newFiles);
    updatePreview();
    updateHiddenInputs();
    this.value = "";
});

function updatePreview() {
    preview.innerHTML = "";
    selectedFiles.forEach((file, i) => {
        const wrapper = document.createElement("div");
        const img = document.createElement("img");
        img.src = URL.createObjectURL(file);

        const del = document.createElement("button");
        del.innerText = "x";
        del.onclick = () => {
            selectedFiles.splice(i, 1);
            updatePreview();
            updateHiddenInputs();
        };

        wrapper.appendChild(img);
        wrapper.appendChild(del);
        preview.appendChild(wrapper);
    });
}

function updateHiddenInputs() {
    fileContainer.innerHTML = "";

    selectedFiles.forEach(file => {
        const dt = new DataTransfer();
        dt.items.add(file);

        const input = document.createElement("input");
        input.type = "file";
        input.name = "images[]";
        input.files = dt.files;
        input.hidden = true;

        fileContainer.appendChild(input);
    });
}


/* ============================================================
   LIKE SYSTEM (AJAX)
============================================================ */
function toggleLike(postId, btn) {
    fetch("Proses/proses_like_ajax.php?post_id=" + postId)
        .then(r => r.text())
        .then(res => {
            const countEl = document.getElementById("like-count-" + postId);
            let count = parseInt(countEl.innerText);

            if (res === "liked") {
                btn.innerText = "❤️ Unlike";
                countEl.innerText = count + 1;
            } else {
                btn.innerText = "🤍 Like";
                countEl.innerText = count - 1;
            }
        });
}


/* ============================================================
   INFINITE SCROLL BERANDA
============================================================ */
let offset = 0;
let loading = false;
let noMore = false;
const limit = 5;

const scrollKey = "homeScroll_<?= $user_id ?>";
let restoreScroll = localStorage.getItem(scrollKey);

window.addEventListener("beforeunload", () => {
    localStorage.setItem(scrollKey, window.scrollY);
});

function loadMore() {
    if (loading || noMore) return;

    loading = true;
    document.getElementById("loader").style.display = "block";

    fetch(`Proses/proses_load_more_beranda.php?offset=${offset}&limit=${limit}`)
        .then(r => r.text())
        .then(html => {
            document.getElementById("loader").style.display = "none";

            if (!html.trim()) {
                noMore = true;
                return;
            }

            document.getElementById("post-container")
                .insertAdjacentHTML("beforeend", html);

            offset += limit;
            loading = false;

            if (restoreScroll !== null) {
                window.scrollTo(0, parseInt(restoreScroll));
                restoreScroll = null;
            }
        });
}

function handleScroll() {
    if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 700) {
        loadMore();
    }
}

loadMore();
window.addEventListener("scroll", handleScroll);
</script>

</body>
</html>
