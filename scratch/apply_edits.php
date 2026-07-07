<?php
$file = 'index.php';
if (!file_exists($file)) {
    die("index.php not found.\n");
}

$content = file_get_contents($file);

// Helper function to replace exact string with validation
function applyReplace(&$content, $target, $replacement, $name) {
    // Standardize CRLF to LF for matching
    $target_norm = str_replace("\r\n", "\n", $target);
    $content_norm = str_replace("\r\n", "\n", $content);
    
    if (strpos($content_norm, $target_norm) === false) {
        echo "[WARNING] Target block for '$name' was not found in index.php!\n";
        return false;
    }
    
    // We do string replace on normalized content or match it specifically.
    // To preserve original line endings, we can replace the target directly if it matches exactly.
    // Otherwise, we do it on normalized content and then write it back.
    if (strpos($content, $target) !== false) {
        $content = str_replace($target, $replacement, $content);
        echo "[SUCCESS] Applied replacement for '$name' (exact CRLF match).\n";
        return true;
    } else {
        $content_norm = str_replace($target_norm, str_replace("\r\n", "\n", $replacement), $content_norm);
        $content = $content_norm; // Convert whole file to LF
        echo "[SUCCESS] Applied replacement for '$name' (normalized LF match).\n";
        return true;
    }
}

// 1. Particle Draw function (increase font size of background animation numbers)
$p_target = '        draw() {
          ctx.fillStyle = \'rgba(251, 191, 36, 0.3)\';
          if (this.isNumber) {
            ctx.font = \'10px Inter\';
            ctx.fillText(this.val, this.x, this.y);
          } else {
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
            ctx.closePath();
            ctx.fill();
          }
        }';

$p_repl = '        draw() {
          ctx.fillStyle = this.isNumber ? \'rgba(27, 201, 118, 0.22)\' : \'rgba(27, 201, 118, 0.08)\';
          if (this.isNumber) {
            ctx.font = \'bold 22px Inter\';
            ctx.fillText(this.val, this.x, this.y);
          } else {
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
            ctx.closePath();
            ctx.fill();
          }
        }';
applyReplace($content, $p_target, $p_repl, "Hero Particles Animation font/color");

// 2. Services style block replacement in <head>
$s_target = '.services-flex-container {
  display: flex;
  gap: 16px;
  height: 400px;
}
.service-flex-card-new {
  flex: 1;
  position: relative;
  border-radius: var(--radius-lg);
  overflow: hidden;
  transition: flex 0.5s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex;
  align-items: center;
  justify-content: flex-end; /* right align */
  padding: 40px;
  cursor: pointer;
}
.service-flex-card-new:hover {
  flex: 3;
}
.service-flex-card-new::before {
  content: \'\';
  position: absolute;
  inset: 0;
  background: linear-gradient(to bottom, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.3) 100%);
  z-index: 1;
}
.service-flex-card-new .service-content {
  position: relative;
  z-index: 2;
  text-align: right;
  align-items: flex-end;
  width: 100%;
  max-width: 300px;
  transition: all 0.5s ease;
  opacity: 0.8;
}
.service-flex-card-new:hover .service-content {
  opacity: 1;
}
.service-flex-card-new .icon-grad {
  font-size: 48px;
  margin-bottom: 24px;
  color: var(--primary);
  background: none;
  -webkit-text-fill-color: initial;
  transition: all 0.4s ease;
}
.service-flex-card-new:hover .icon-grad {
  color: #fff;
  transform: scale(1.2);
}
.service-flex-card-new h3 {
  font-size: 24px;
  font-weight: 800;
  color: #fff;
  margin-bottom: 12px;
}
.service-flex-card-new p {
  color: rgba(255,255,255,0.7);
  font-size: 15px;
  line-height: 1.6;
}';

$s_repl = '.services-flex-container {
  display: flex;
  gap: 24px;
  height: 440px;
}
.service-flex-card-new {
  flex: 1;
  position: relative;
  border-radius: 24px;
  overflow: hidden;
  transition: flex 0.6s cubic-bezier(0.25, 1, 0.5, 1), opacity 0.4s ease;
  display: flex;
  align-items: center;
  justify-content: flex-end; /* right align */
  padding: 44px;
  cursor: pointer;
  background: var(--bg-dark) !important;
}
.service-flex-card-new.service-card-2 { background: var(--bg-darker) !important; }
.service-flex-card-new.service-card-3 { background: #0f1520 !important; }

.services-flex-container:hover .service-flex-card-new {
  flex: 0.75;
  opacity: 0.6;
}
.services-flex-container .service-flex-card-new:hover {
  flex: 1.6;
  opacity: 1;
}
.service-flex-card-new::before {
  content: \'\';
  position: absolute;
  inset: 0;
  background: linear-gradient(to bottom, rgba(6, 12, 22, 0.8) 0%, rgba(6, 12, 22, 0.35) 100%);
  z-index: 1;
}
.service-flex-card-new::after {
  content: \'\';
  position: absolute;
  left: -200px;
  top: -200px;
  width: 480px;
  height: 480px;
  border-radius: 50%;
  opacity: 0.18;
  transition: all 0.5s cubic-bezier(0.25, 1, 0.5, 1);
  z-index: 1;
  pointer-events: none;
}
.service-flex-card-new:hover::after {
  opacity: 0.38;
  transform: scale(1.15);
}

.service-card-1::after {
  background: radial-gradient(circle, rgba(14, 165, 233, 0.75) 0%, rgba(27, 201, 118, 0.4) 40%, transparent 70%);
}
.service-card-2::after {
  background: radial-gradient(circle, rgba(14, 165, 233, 0.75) 0%, rgba(139, 92, 246, 0.4) 40%, transparent 70%);
}
.service-card-3::after {
  background: radial-gradient(circle, rgba(139, 92, 246, 0.75) 0%, rgba(27, 201, 118, 0.4) 40%, transparent 70%);
}

.service-flex-card-new .service-content {
  position: relative;
  z-index: 2;
  text-align: right;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  width: 100%;
  max-width: 320px;
  transition: all 0.5s ease;
  opacity: 0.85;
}
.service-flex-card-new:hover .service-content {
  opacity: 1;
}
.service-flex-card-new .icon-grad {
  font-size: 64px;
  margin-bottom: 24px;
  color: var(--primary);
  background: none;
  -webkit-text-fill-color: initial;
  transition: all 0.4s ease;
}
.service-flex-card-new:hover .icon-grad {
  color: #fff;
  transform: scale(1.15);
}
.service-flex-card-new h3 {
  font-size: 24px;
  font-weight: 800;
  color: #fff;
  margin-bottom: 12px;
}
.service-flex-card-new p {
  color: rgba(255,255,255,0.7);
  font-size: 15px;
  line-height: 1.6;
}

/* Stats feature card customizations */
.stat-feature-card {
  background: rgba(13, 20, 36, 0.6) !important;
  border: 1px solid var(--primary) !important;
  backdrop-filter: blur(12px) !important;
  transition: all 0.3s ease;
}
.stat-feature-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 20px rgba(27, 201, 118, 0.15);
}';
applyReplace($content, $s_target, $s_repl, "Head style definitions (Services & Stats cards)");

// 3. Bidding step image (photo-1600860548174-569420dd7b89 -> photo-1618042164219-62c820f10723)
$img_bid_target = '<img src="https://images.unsplash.com/photo-1600860548174-569420dd7b89?w=600&q=80" style="width:100%; border-radius: var(--radius-md); aspect-ratio: 4/3; object-fit: cover;" alt="المزايدة والفوز" loading="lazy">';
$img_bid_repl = '<img src="https://images.unsplash.com/photo-1618042164219-62c820f10723?w=600&q=80" style="width:100%; border-radius: var(--radius-md); aspect-ratio: 4/3; object-fit: cover;" alt="المزايدة والفوز" loading="lazy">';
applyReplace($content, $img_bid_target, $img_bid_repl, "Buyer Bidding step image");

// 4. Seller step image (photo-1486406146926-c627a92ad1ab -> photo-1450133064473-71024230f91b)
$img_sel_target = '<img src="https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=600&q=80" style="width:100%; border-radius: var(--radius-md); aspect-ratio: 4/3; object-fit: cover;" alt="توثيق الشركة" loading="lazy">';
$img_sel_repl = '<img src="https://images.unsplash.com/photo-1450133064473-71024230f91b?w=600&q=80" style="width:100%; border-radius: var(--radius-md); aspect-ratio: 4/3; object-fit: cover;" alt="توثيق الشركة" loading="lazy">';
applyReplace($content, $img_sel_target, $img_sel_repl, "Seller Authentication step image");

// 5. Sellers section shadow
$sel_shadow_target = '<div style="position: sticky; top: 0; min-height: 100vh; display: flex; align-items: center; background:#f1f5f9; color: var(--text-dark); z-index: 2; padding: 100px 0; border-radius: 40px 40px 0 0; box-shadow: none;">';
$sel_shadow_repl = '<div style="position: sticky; top: 0; min-height: 100vh; display: flex; align-items: center; background:#f1f5f9; color: var(--text-dark); z-index: 2; padding: 100px 0; border-radius: 40px 40px 0 0; box-shadow: 0 -20px 40px rgba(0, 0, 0, 0.08);">';
applyReplace($content, $sel_shadow_target, $sel_shadow_repl, "Sellers section wrapper top shadow");

// 6. Stats Section Parallax scroll fix (relocate reveal class and update background image & box-shadow)
$stats_sec_target = '<section class="stats-section reveal" style="background-image: url(\'https://images.unsplash.com/photo-1580273916550-e323be2ae537?w=1600&q=80\'); background-attachment: fixed; background-size: cover; background-position: center; padding: 100px 0 140px;">
  <div style="position: absolute; top:0; left:0; right:0; bottom:0; background: rgba(6, 12, 22, 0.9); z-index: 0;"></div>
  
  <div class="container" style="position: relative; z-index: 1;">';

$stats_sec_repl = '<section class="stats-section" style="background-image: url(\'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?w=1600&q=80\'); background-attachment: fixed; background-size: cover; background-position: center center; padding: 120px 0 160px; box-shadow: inset 0 0 100px rgba(0,0,0,0.8); position: relative; overflow: hidden;">
  <div style="position: absolute; top:0; left:0; right:0; bottom:0; background: rgba(6, 12, 22, 0.9); z-index: 0;"></div>
  
  <div class="container reveal" style="position: relative; z-index: 1;">';
applyReplace($content, $stats_sec_target, $stats_sec_repl, "Stats Section Parallax Fix");

// 7. Stats Items (add icons above statistics)
$stat1_target = '        <div style="text-align: center;">
          <div class="stats-number" style="color: #fff;">92.9%</div>';
$stat1_repl = '        <div style="text-align: center;">
          <i class="ph ph-seal-check" style="font-size: 44px; color: #fff; margin-bottom: 12px; display: inline-block;"></i>
          <div class="stats-number" style="color: #fff;">92.9%</div>';
applyReplace($content, $stat1_target, $stat1_repl, "92.9% Stats Icon");

$stat2_target = '        <div style="text-align: center;">
          <div class="stats-number text-gradient"><span class="font-en">13.5</span> <span style="font-family: var(--font-ar)">مليار</span></div>';
$stat2_repl = '        <div style="text-align: center;">
          <i class="ph ph-trend-up" style="font-size: 44px; color: var(--primary); margin-bottom: 12px; display: inline-block;"></i>
          <div class="stats-number text-gradient"><span class="font-en">13.5</span> <span style="font-family: var(--font-ar)">مليار</span></div>';
applyReplace($content, $stat2_target, $stat2_repl, "13.5B Stats Icon");


file_put_contents($file, $content);
echo "All edits applied successfully!\n";
?>
