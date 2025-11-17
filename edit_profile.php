<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$q = mysqli_query($koneksi, "SELECT * FROM users WHERE user_id='$user_id'");
$u = mysqli_fetch_assoc($q);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Edit Profil</title>
<link rel="stylesheet" href="Styles/global_style.css">
<!-- CropperJS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

<style>
     .page-container {
            width: 95%;
            max-width: 800px;
            margin: 40px auto 40px; /* beri jarak dari navbar */
        }

    .preview-box img {
        width: 180px;
        border-radius: 10px;
        border: 1px solid #ccc;
        margin-bottom: 10px;
        object-fit: cover;
    }

    /* Banner preview fix rasio */
    #banner_preview {
        width: 350px;
        height: 100px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid #ccc;
    }

    /* Fullscreen modal */
    #crop-modal {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.7);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    }

    /* Crop box */
    #crop-box {
        background: white;
        padding: 20px;
        border-radius: 10px;
        max-width: 95%;
        max-height: 90vh;
        overflow: auto;
    }

    #crop-area img {
        max-width: 100%;
    }

    .crop-buttons {
        margin-top: 10px;
        text-align: center;
    }

    .crop-buttons button {
        padding: 8px 18px;
        margin: 5px;
        border-radius: 6px;
        cursor: pointer;
    }
</style>

</head>
<body>
<?php include "Components/navbar.php"; ?>

<div class="page-container">
<h2>Edit Profil</h2>

<form action="Proses/proses_edit_profile.php" method="POST" enctype="multipart/form-data">

    <label>Username:</label><br>
    <input type="text" name="username" value="<?= htmlspecialchars($u['username']) ?>" required><br><br>

    <label>Bio:</label><br>
    <textarea name="bio" rows="3" cols="40"><?= htmlspecialchars($u['bio'] ?? '') ?></textarea><br><br>

    <!-- FOTO PROFIL -->
    <h3>Foto Profil</h3>

    <div class="preview-box">
        <img id="profile_preview" 
             src="<?= $u['profile_picture'] ? htmlspecialchars($u['profile_picture']) : 'default_pp.png' ?>">
    </div>

    <input type="file" id="profile_input" accept="image/*"><br><br>
    <input type="hidden" name="profile_picture_cropped" id="profile_cropped">

    <!-- BANNER -->
    <h3>Banner</h3>

    <div class="preview-box">
        <img id="banner_preview"
             src="<?= $u['banner'] ? htmlspecialchars($u['banner']) : 'default_banner.png' ?>">
    </div>

    <input type="file" id="banner_input" accept="image/*"><br><br>
    <input type="hidden" name="banner_cropped" id="banner_cropped">

    <button type="submit">💾 Simpan Perubahan</button>
</form>


<!-- AREA CROP -->
<div id="crop-modal">
    <div id="crop-box">
        <h3>Crop Gambar</h3>
        <div id="crop-area"></div>

        <div class="crop-buttons">
            <button onclick="applyCrop()">✔ Terapkan</button>
            <button onclick="closeCrop()">✖ Batal</button>
        </div>
    </div>
</div>


<script>

let cropper = null;
let currentType = ""; // "profile" atau "banner"

function openCropModal(imageSrc, type) {
    currentType = type;
    document.getElementById('crop-modal').style.display = "flex";

    const cropArea = document.getElementById('crop-area');
    cropArea.innerHTML = `<img id='crop-image' src='${imageSrc}'>`;

    setTimeout(() => {
        const aspect = type === "profile" ? 1 : 6/1;

        cropper = new Cropper(document.getElementById('crop-image'), {
            aspectRatio: aspect,
            viewMode: 2,
            autoCropArea: 1,
            responsive: true
        });
    }, 200);
}

function closeCrop() {
    document.getElementById('crop-modal').style.display = "none";
    cropper?.destroy();
    cropper = null;
}

function applyCrop() {
    if (!cropper) return;

    cropper.getCroppedCanvas().toBlob(blob => {
        let reader = new FileReader();
        reader.readAsDataURL(blob);

        reader.onloadend = () => {
            let base64 = reader.result;

            if (currentType === "profile") {
                document.getElementById('profile_preview').src = base64;
                document.getElementById('profile_cropped').value = base64;
            } else {
                document.getElementById('banner_preview').src = base64;
                document.getElementById('banner_cropped').value = base64;
            }

            closeCrop();
        };
    });
}

document.getElementById('profile_input').addEventListener('change', e => {
    if (e.target.files[0]) {
        openCropModal(URL.createObjectURL(e.target.files[0]), "profile");
    }
});

document.getElementById('banner_input').addEventListener('change', e => {
    if (e.target.files[0]) {
        openCropModal(URL.createObjectURL(e.target.files[0]), "banner");
    }
});

</script>

</body>
</html>
