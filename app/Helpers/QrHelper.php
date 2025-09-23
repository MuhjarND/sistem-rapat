/**
 * Generate QR code dari URL, lalu tempel logo di tengah (pakai GD).
 *
 * @param  string $qrContent     Isi QR (misalnya URL verifikasi)
 * @param  string $savePathAbs   Path absolut untuk simpan file PNG (contoh: public_path('qr/file.png'))
 * @param  string|null $logoAbsPath Path absolut logo (PNG/JPG). null = tanpa logo
 * @param  int    $size          Ukuran QR dasar (px)
 * @return bool
 */

private function makeQrWithLogo(string $qrContent, string $savePathAbs, ?string $logoAbsPath, int $size = 380): bool
{
    // 1. Ambil QR polos dari API
    $encoded = urlencode($qrContent);
    $src1 = "https://chart.googleapis.com/chart?chs={$size}x{$size}&cht=qr&chl={$encoded}";
    $png = @file_get_contents($src1);

    if ($png === false) {
        $src2 = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data={$encoded}&margin=2";
        $png  = @file_get_contents($src2);
        if ($png === false) return false;
    }

    // simpan sementara
    $tmp = sys_get_temp_dir().'/qr_'.uniqid().'.png';
    file_put_contents($tmp, $png);

    // 2. Buka dengan GD
    $qr = @imagecreatefrompng($tmp);
    @unlink($tmp);
    if (!$qr) return false;

    imagesavealpha($qr, true);
    imagealphablending($qr, true);

    // 3. Tempel logo kalau ada
    if ($logoAbsPath && is_file($logoAbsPath)) {
        $logo = null;
        $type = @exif_imagetype($logoAbsPath);

        if ($type === IMAGETYPE_PNG)      $logo = @imagecreatefrompng($logoAbsPath);
        elseif ($type === IMAGETYPE_JPEG) $logo = @imagecreatefromjpeg($logoAbsPath);
        elseif ($type === IMAGETYPE_GIF)  $logo = @imagecreatefromgif($logoAbsPath);

        if ($logo) {
            imagesavealpha($logo, true);
            imagealphablending($logo, true);

            $qrW = imagesx($qr);
            $qrH = imagesy($qr);
            $lgW = imagesx($logo);
            $lgH = imagesy($logo);

            // Skala logo ±22% dari lebar QR
            $targetW = (int) floor($qrW * 0.22);
            $scale   = $targetW / max(1, $lgW);
            $targetH = (int) floor($lgH * $scale);

            // Resize logo
            $logoRes = imagecreatetruecolor($targetW, $targetH);
            imagesavealpha($logoRes, true);
            $trans = imagecolorallocatealpha($logoRes, 0,0,0,127);
            imagefill($logoRes, 0,0, $trans);
            imagecopyresampled($logoRes, $logo, 0,0, 0,0, $targetW, $targetH, $lgW, $lgH);

            // Tempel di tengah QR
            $dstX = (int) floor(($qrW - $targetW)/2);
            $dstY = (int) floor(($qrH - $targetH)/2);
            imagecopy($qr, $logoRes, $dstX, $dstY, 0,0, $targetW, $targetH);

            imagedestroy($logoRes);
            imagedestroy($logo);
        }
    }

    // 4. Simpan hasil final
    $ok = imagepng($qr, $savePathAbs, 6);
    imagedestroy($qr);

    return (bool) $ok;
}
