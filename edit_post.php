<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$post_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Ambil postingan
$post = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM posts WHERE post_id='$post_id'"));
if (!$post) exit("Post tidak ditemukan!");

if ($post['user_id'] != $user_id) exit("Kamu tidak berhak mengedit ini!");

// Ambil gambar lama
$img_query = mysqli_query($koneksi, "SELECT * FROM post_images WHERE post_id='$post_id'");
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Edit Postingan</title>

    <link rel="stylesheet" href="Styles/global_style.css">

    <style>
        .page-container {
            width: 95%;
            max-width: 800px;
            margin: 40px auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
        }

        .old-img {
            width: 130px;
            height: 130px;
            object-fit: cover;
            border-radius: 10px;
            border: 1px solid #ddd;
            margin-bottom: 3px;
        }

        .preview-wrapper {
            position: relative;
            display: inline-block;
            margin: 5px;
        }

        .new-preview {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ccc;
        }

        .btn-del-preview {
            position:absolute;
            top:-5px;
            right:-5px;
            background:red;
            color:white;
            width:20px;
            height:20px;
            border-radius:50%;
            border:none;
            cursor:pointer;
            font-size:12px;
        }
    </style>
</head>
<body>

<?php include "Components/navbar.php"; ?>

<div class="page-container">

    <h2>Edit Postingan</h2>

    <form action="Proses/proses_edit_post.php" method="POST" enctype="multipart/form-data" id="editForm">

        <input type="hidden" name="post_id" value="<?= $post_id ?>">

        <!-- JUDUL -->
        <label>Judul:</label>
        <input type="text" name="title" value="<?= htmlspecialchars($post['title']) ?>" required style="width:100%;">
        <br><br>

        <!-- ISI -->
        <label>Isi:</label>
        <textarea name="content" rows="5" style="width:100%;" required><?= htmlspecialchars($post['content']) ?></textarea>
        <br><br>

        <!-- GAMBAR LAMA -->
        <p><b>Gambar Lama:</b></p>

        <?php while ($img = mysqli_fetch_assoc($img_query)) : ?>
            <div style="margin-bottom:10px;">
                <img src="<?= htmlspecialchars($img['image_url']) ?>" class="old-img"><br>
                <label>
                    <input type="checkbox" name="hapus_gambar[]" value="<?= $img['image_id'] ?>">
                    Hapus gambar ini
                </label>
            </div>
        <?php endwhile; ?>

        <hr>

        <!-- GAMBAR BARU -->
        <p><b>Tambah Gambar Baru:</b></p>

        <button type="button" onclick="document.getElementById('newImages').click()">Pilih Gambar</button>
        <input type="file" id="newImages" accept="image/*" multiple hidden>

        <p style="font-size:14px;color:#555;">Gambar baru akan ditambahkan tanpa menghapus gambar sebelumnya.</p>

        <!-- PREVIEW -->
        <div id="previewContainer" style="margin-top:10px;"></div>
        <div id="fileContainer"></div>

        <br><br>
        <button type="submit">💾 Simpan Perubahan</button>
        <a href="detail_post.php?id=<?= $post_id ?>">Batal</a>

    </form>

</div>

<script>
// ============================================
// SISTEM PREVIEW GAMBAR BARU (PERSIS SEPERTI BERANDA)
// ============================================
let selectedFiles = [];
let preview = document.getElementById("previewContainer");
let fileContainer = document.getElementById("fileContainer");
let inputNew = document.getElementById("newImages");

inputNew.addEventListener("change", function() {

    let newFiles = Array.from(this.files);

    // Gabungkan dengan file yang sudah dipilih sebelumnya
    selectedFiles = selectedFiles.concat(newFiles);

    updatePreview();
    updateHiddenInputs();

    // reset input agar bisa pilih file yang sama lagi
    this.value = "";
});

function updatePreview() {
    preview.innerHTML = "";

    selectedFiles.forEach((file, index) => {
        let wrap = document.createElement("div");
        wrap.className = "preview-wrapper";

        let img = document.createElement("img");
        img.src = URL.createObjectURL(file);
        img.className = "new-preview";

        let del = document.createElement("button");
        del.className = "btn-del-preview";
        del.innerText = "x";

        del.onclick = () => {
            selectedFiles.splice(index, 1);
            updatePreview();
            updateHiddenInputs();
        };

        wrap.appendChild(img);
        wrap.appendChild(del);
        preview.appendChild(wrap);
    });
}

function updateHiddenInputs() {
    fileContainer.innerHTML = "";

    selectedFiles.forEach(file => {
        const dt = new DataTransfer();
        dt.items.add(file);

        let input = document.createElement("input");
        input.type = "file";
        input.name = "images[]";
        input.files = dt.files;
        input.hidden = true;

        fileContainer.appendChild(input);
    });
}
</script>

</body>
</html>
