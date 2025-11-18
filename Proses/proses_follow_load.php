<?php
session_start();
include "../koneksi.php";
include "../Components/verified_badge.php";

if (!isset($_SESSION['user_id'])) {
    exit;
}

$viewer = intval($_SESSION['user_id']);
$profile_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$tab = isset($_GET['tab']) ? $_GET['tab'] : "followers";
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$limit = 10;

if (!in_array($tab, ["followers", "following"])) {
    $tab = "followers";
}

if ($tab == "followers") {
    // Siapa saja yang mem-follow user ini
    $sql = "
        SELECT 
            f.follower_id AS uid,
            u.username,
            u.profile_picture,
            u.is_verified,
            (
                SELECT COUNT(*) FROM follows 
                WHERE follower_id = $viewer 
                  AND following_id = f.follower_id
            ) AS is_following
        FROM follows f
        JOIN users u ON f.follower_id = u.user_id
        WHERE f.following_id = $profile_id
        ORDER BY f.follow_id DESC
        LIMIT $offset, $limit
    ";
} else {
    // Siapa saja yang user ini follow
    $sql = "
        SELECT 
            f.following_id AS uid,
            u.username,
            u.profile_picture,
            u.is_verified,
            (
                SELECT COUNT(*) FROM follows 
                WHERE follower_id = $viewer 
                  AND following_id = f.following_id
            ) AS is_following
        FROM follows f
        JOIN users u ON f.following_id = u.user_id
        WHERE f.follower_id = $profile_id
        ORDER BY f.follow_id DESC
        LIMIT $offset, $limit
    ";
}

$q = mysqli_query($koneksi, $sql);

while ($row = mysqli_fetch_assoc($q)) {
    $uid   = intval($row['uid']);
    $uname = htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8');
    $ver   = intval($row['is_verified']);

    // --- Build profile picture src for browser, but check filesystem correctly ---
    $dbPic = isset($row['profile_picture']) ? trim($row['profile_picture']) : '';

    // default src to use in HTML (relative to follow_list.php which is in project root)
    $default_src = "uploads/Profile/default.png";

    if ($dbPic !== '') {
        // If DB already stores a path beginning with 'uploads/' use it directly
        if (stripos($dbPic, 'uploads/') === 0) {
            $candidate_src = $dbPic;
            $candidate_fs = realpath(__DIR__ . "/../" . $dbPic) ?: (__DIR__ . "/../" . $dbPic);
        } else {
            // otherwise assume it's just filename and lives under uploads/Profile/
            $candidate_src = "uploads/Profile/" . $dbPic;
            $candidate_fs = realpath(__DIR__ . "/../uploads/Profile/" . $dbPic) ?: (__DIR__ . "/../uploads/Profile/" . $dbPic);
        }

        // if the file actually exists on disk, use it; otherwise fallback to default
        if (file_exists($candidate_fs)) {
            $pp_src = $candidate_src;
        } else {
            $pp_src = $default_src;
        }
    } else {
        $pp_src = $default_src;
    }

    $pp_esc = htmlspecialchars($pp_src, ENT_QUOTES, 'UTF-8');

    // apakah viewer sudah follow dia? (nilai subquery is_following)
    $is_following = (isset($row['is_following']) && intval($row['is_following']) > 0);

    // --- OUTPUT HTML ---
    // Note: href harus "profile.php?id=..." (relatif ke follow_list.php yang memuat HTML)
    echo "
    <div style='display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid #ddd;'>
        <img src='$pp_esc' 
             style='width:45px;height:45px;border-radius:50%;object-fit:cover;' 
             alt='profile picture'>
        <div style='flex:1;'>
            <a href='profile.php?id=$uid' style='font-weight:bold;color:#000;text-decoration:none;'>
                @$uname
            </a> " . renderVerified($ver, 16) . "
        </div>";

    // tombol follow tidak muncul kalau itu dirinya sendiri
    if ($uid !== $viewer) {
        $btnText = $is_following ? "Following" : "Follow";
        echo "
        <button onclick='toggleFollow($uid, this)' 
                data-uid='$uid'
                style='padding:6px 12px;border-radius:6px;cursor:pointer;'>
            $btnText
        </button>";
    }

    echo "</div>";
}
?>
