<?php
function renderVerified($is_verified, $size = 18) {
    if ($is_verified) {
        return '<span style="color:blue; font-size:'.$size.'px; margin-left:4px;">✔️</span>';
    }
    return '';
}
?>
