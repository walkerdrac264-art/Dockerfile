<?php
/**
 * Riverside FC - Offline Dynamic SVG QR Code Fallback
 * Returns an SVG image stream representing the ticket code
 */
header('Content-Type: image/svg+xml');
$text = $_GET['text'] ?? 'RFC-TICKET';
$hash = md5($text);

// Generate a deterministic 21x21 QR-like matrix grid with authentic QR finder patterns
$size = 21;
$matrix = array_fill(0, $size, array_fill(0, $size, 0));

// Add standard 7x7 corner finder patterns
function addFinderPattern(&$m, $startX, $startY) {
    for ($y = 0; $y < 7; $y++) {
        for ($x = 0; $x < 7; $x++) {
            if ($y == 0 || $y == 6 || $x == 0 || $x == 6) {
                $m[$startY + $y][$startX + $x] = 1;
            } elseif ($y >= 2 && $y <= 4 && $x >= 2 && $x <= 4) {
                $m[$startY + $y][$startX + $x] = 1;
            }
        }
    }
}

addFinderPattern($matrix, 0, 0);          // Top-left
addFinderPattern($matrix, $size - 7, 0);  // Top-right
addFinderPattern($matrix, 0, $size - 7);  // Bottom-left

// Timing patterns
for ($i = 7; $i < $size - 7; $i++) {
    $matrix[6][$i] = ($i % 2 === 0) ? 1 : 0;
    $matrix[$i][6] = ($i % 2 === 0) ? 1 : 0;
}

// Populate payload pseudo-modules based on hash
$hashBin = '';
for ($i = 0; $i < strlen($hash); $i++) {
    $hashBin .= str_pad(base_convert($hash[$i], 16, 2), 4, '0', STR_PAD_LEFT);
}

$bitIndex = 0;
for ($y = 0; $y < $size; $y++) {
    for ($x = 0; $x < $size; $x++) {
        // Skip corner patterns
        if (($x < 8 && $y < 8) || ($x >= $size - 8 && $y < 8) || ($x < 8 && $y >= $size - 8)) {
            continue;
        }
        if ($matrix[$y][$x] === 0) {
            $bit = $hashBin[$bitIndex % strlen($hashBin)] ?? '0';
            $matrix[$y][$x] = ($bit === '1') ? 1 : 0;
            $bitIndex++;
        }
    }
}

$moduleSize = 10;
$padding = 20;
$totalWidth = ($size * $moduleSize) + ($padding * 2);

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 <?= $totalWidth ?> <?= $totalWidth ?>" width="<?= $totalWidth ?>" height="<?= $totalWidth ?>">
    <rect width="100%" height="100%" fill="#ffffff"/>
    <g fill="#0b1e36" transform="translate(<?= $padding ?>, <?= $padding ?>)">
        <?php for ($y = 0; $y < $size; $y++): ?>
            <?php for ($x = 0; $x < $size; $x++): ?>
                <?php if ($matrix[$y][$x] === 1): ?>
                    <rect x="<?= $x * $moduleSize ?>" y="<?= $y * $moduleSize ?>" width="<?= $moduleSize ?>" height="<?= $moduleSize ?>"/>
                <?php endif; ?>
            <?php endfor; ?>
        <?php endfor; ?>
    </g>
</svg>
