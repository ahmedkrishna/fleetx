<?php
/**
 * Adaptive FleetX logo — correct variant for light vs dark backgrounds.
 * Optional before include: $fx_logo_bg (light|dark|auto), $fx_logo_class, $fx_logo_link, $fx_logo_height
 */
$fx_logo_bg = $fx_logo_bg ?? 'auto';
$fx_logo_class = $fx_logo_class ?? '';
$fx_logo_link = $fx_logo_link ?? '';
$fx_logo_height = (int)($fx_logo_height ?? 36);

if ($fx_logo_bg === 'auto') {
    $fx_logo_bg = fleetx_logo_bg_context();
}

$bg_class = ($fx_logo_bg === 'light') ? 'fx-logo--on-light' : 'fx-logo--on-dark';
$wrap_class = trim('fx-logo ' . $bg_class . ' ' . $fx_logo_class);
$style = 'height:' . max(24, $fx_logo_height) . 'px;width:auto;object-fit:contain;display:block';

$img_html = '<span class="' . htmlspecialchars($wrap_class, ENT_QUOTES, 'UTF-8') . '">'
    . '<img class="fx-logo__img fx-logo__img--light" src="' . fleetx_logo_light_src() . '" alt="FleetX" style="' . $style . '">'
    . '<img class="fx-logo__img fx-logo__img--dark" src="' . fleetx_logo_dark_src() . '" alt="FleetX" style="' . $style . '">'
    . '</span>';

if ($fx_logo_link !== '') {
    echo '<a href="' . htmlspecialchars($fx_logo_link, ENT_QUOTES, 'UTF-8') . '" class="fx-logo-link">', $img_html, '</a>';
} else {
    echo $img_html;
}

unset($fx_logo_bg, $fx_logo_class, $fx_logo_link, $fx_logo_height, $bg_class, $wrap_class, $style, $img_html);