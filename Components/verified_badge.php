<?php
function renderVerified($is_verified, $size = 18, $tooltip = "Pengguna Ini Terferivikasi Loh!") {
    if ($is_verified) {
        return '<span 
                    title="'.htmlspecialchars($tooltip).'"
                    style="color:blue; font-size:'.$size.'px; margin-left:4px; cursor:pointer;">
                    ✔️
                </span>';
    }
    return '';
}
?>
