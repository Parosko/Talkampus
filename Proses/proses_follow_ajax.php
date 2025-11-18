<?php
// Proses/proses_follow_ajax.php
session_start();
include "../koneksi.php"; // sesuaikan kalau path beda

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status'=>'error','msg'=>'not_logged_in']);
    exit;
}

$follower_id = intval($_SESSION['user_id']);
$following_id = isset($_POST['following_id']) ? intval($_POST['following_id']) : 0;

if ($following_id <= 0 || $follower_id <= 0) {
    echo json_encode(['status'=>'error','msg'=>'invalid_input']);
    exit;
}

if ($follower_id === $following_id) {
    echo json_encode(['status'=>'error','msg'=>"can't_follow_self"]);
    exit;
}

// Cek apakah sudah follow
$q = mysqli_prepare($koneksi, "SELECT follow_id FROM follows WHERE follower_id=? AND following_id=? LIMIT 1");
mysqli_stmt_bind_param($q, "ii", $follower_id, $following_id);
mysqli_stmt_execute($q);
mysqli_stmt_store_result($q);
$exists = mysqli_stmt_num_rows($q) > 0;
mysqli_stmt_close($q);

if ($exists) {
    // lakukan unfollow (hapus baris)
    $del = mysqli_prepare($koneksi, "DELETE FROM follows WHERE follower_id=? AND following_id=?");
    mysqli_stmt_bind_param($del, "ii", $follower_id, $following_id);
    $ok = mysqli_stmt_execute($del);
    mysqli_stmt_close($del);

    if ($ok) {
        // ambil jumlah followers baru
        $row = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS jml FROM follows WHERE following_id='$following_id'"));
        echo json_encode(['status'=>'ok','action'=>'unfollow','followers'=>intval($row['jml'])]);
    } else {
        echo json_encode(['status'=>'error','msg'=>'db_error_delete']);
    }
} else {
    // lakukan follow (insert)
    $ins = mysqli_prepare($koneksi, "INSERT INTO follows (follower_id, following_id) VALUES (?, ?)");
    mysqli_stmt_bind_param($ins, "ii", $follower_id, $following_id);
    $ok = mysqli_stmt_execute($ins);
    mysqli_stmt_close($ins);

    if ($ok) {
        $row = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS jml FROM follows WHERE following_id='$following_id'"));
        echo json_encode(['status'=>'ok','action'=>'follow','followers'=>intval($row['jml'])]);
    } else {
        // jika duplicate karena race condition, tangani
        if (mysqli_errno($koneksi) == 1062) {
            $row = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS jml FROM follows WHERE following_id='$following_id'"));
            echo json_encode(['status'=>'ok','action'=>'follow','followers'=>intval($row['jml'])]);
        } else {
            echo json_encode(['status'=>'error','msg'=>'db_error_insert']);
        }
    }
}
