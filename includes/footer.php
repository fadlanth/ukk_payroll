<?php
declare(strict_types=1);

/**
 * Template Footer Bersama (Clean Code - DRY)
 * 
 * Variabel yang dapat dikirim sebelum require:
 * @var string $footerText Teks keterangan di bagian bawah halaman
 * @var bool   $loadHtml2Pdf Memuat pustaka html2pdf.js (khusus halaman cetak/slip)
 * @var bool   $noPrintFooter Menambahkan class 'no-print' agar tidak ikut tercetak
 */

$footerText = $footerText ?? 'GajiHub · Sistem Penggajian Bulanan';
$loadHtml2Pdf = $loadHtml2Pdf ?? false;
$noPrintFooter = $noPrintFooter ?? false;
?>

        <footer class="footer <?= $noPrintFooter ? 'no-print' : '' ?>">
            <div class="container"><?= e($footerText) ?></div>
        </footer>
    </div> <!-- .content-wrapper -->
</div> <!-- .layout-wrapper -->

<!-- Library JavaScript Pihak Ketiga -->
<?php if ($loadHtml2Pdf): ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.5/dist/sweetalert2.all.min.js"></script>

<!-- Skrip Interaktivitas Utama -->
<script src="<?= baseUrl('assets/js/app.js') ?>"></script>
</body>
</html>
