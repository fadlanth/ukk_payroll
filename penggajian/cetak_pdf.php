<?php
declare(strict_types=1);

session_start();
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/functions.php';

// Memeriksa autentikasi
requireAuth();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    flash('error', 'ID slip gaji tidak valid.');
    redirect('index.php');
}

// Mengambil data slip gaji dan profil karyawan
$stmt = $pdo->prepare("
    SELECT p.*, k.nama, k.email, k.no_hp, k.jabatan
    FROM penggajian p
    JOIN karyawan k ON p.karyawan_id = k.id
    WHERE p.id = :id
");
$stmt->execute(['id' => $id]);
$row = $stmt->fetch();

if (!$row) {
    flash('error', 'Data slip gaji tidak ditemukan.');
    redirect('index.php');
}

// Memuat Autoload Composer untuk Dompdf
require __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Kalkulasi rincian gaji
$calc = hitungGaji(
    (float)$row['gaji_pokok'],
    (float)$row['lembur'],
    (float)$row['pinjaman']
);

$periodeFormatted = formatPeriode($row['periode']);
$tanggalFormatted = !empty($row['tanggal_bayar']) ? date('d-m-Y', strtotime($row['tanggal_bayar'])) : date('d-m-Y');
$noSlip = '#SLIP-' . str_pad((string)$row['id'], 4, '0', STR_PAD_LEFT);
$cleanNama = preg_replace('/[^a-zA-Z0-9_-]/', '_', $row['nama']);
$filename = "Slip_Gaji_{$cleanNama}_{$row['periode']}.pdf";

// Template HTML Khusus Cetak Dokumen PDF Resmi
$html = '
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Slip Gaji - ' . e($row['nama']) . '</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #1e293b;
            line-height: 1.5;
            margin: 0;
            padding: 10px;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #312e81;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #312e81;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .company-address {
            font-size: 10px;
            color: #64748b;
        }
        .doc-title {
            text-align: right;
            font-size: 16px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
        }
        .doc-subtitle {
            text-align: right;
            font-size: 10px;
            color: #059669;
            font-weight: bold;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        .meta-table td {
            padding: 5px 8px;
            font-size: 11px;
            border: 1px solid #e2e8f0;
        }
        .meta-label {
            background-color: #f8fafc;
            color: #475569;
            font-weight: bold;
            width: 20%;
        }
        .meta-val {
            width: 30%;
            color: #0f172a;
        }
        .section-header {
            background-color: #e0e7ff;
            color: #1e1b4b;
            font-weight: bold;
            padding: 6px 10px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 15px;
            margin-bottom: 5px;
        }
        .salary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .salary-table th {
            background-color: #f1f5f9;
            color: #475569;
            padding: 7px 10px;
            text-align: left;
            font-size: 11px;
            border: 1px solid #cbd5e1;
        }
        .salary-table td {
            padding: 7px 10px;
            border: 1px solid #e2e8f0;
            font-size: 11px;
        }
        .text-right { text-align: right; }
        .row-subtotal {
            background-color: #f8fafc;
            font-weight: bold;
        }
        .highlight-box {
            background-color: #eef2ff;
            border: 2px solid #a5b4fc;
            padding: 12px 16px;
            margin-top: 15px;
            margin-bottom: 25px;
        }
        .highlight-table {
            width: 100%;
        }
        .net-text {
            font-size: 13px;
            font-weight: bold;
            color: #1e1b4b;
        }
        .net-amount {
            font-size: 18px;
            font-weight: bold;
            color: #3730a3;
            text-align: right;
        }
        .signature-table {
            width: 100%;
            margin-top: 30px;
            page-break-inside: avoid;
        }
        .signature-table td {
            text-align: center;
            font-size: 11px;
            width: 50%;
        }
        .signature-space {
            height: 60px;
        }
        .footer-note {
            margin-top: 30px;
            border-top: 1px dashed #cbd5e1;
            padding-top: 10px;
            font-size: 9px;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>
<body>

    <!-- Kop Dokumen Resmi -->
    <table class="header-table">
        <tr>
            <td style="width:60%;">
                <div class="company-name">PT. GajiHub Indonesia</div>
                <div class="company-address">Jl. Prof. Sudarto No. 12, Gedung Graha Informatika Lt. 3</div>
                <div class="company-address">Email: payroll@gajihub.co.id | Telp: (021) 8879-1234</div>
            </td>
            <td style="width:40%;">
                <div class="doc-title">SLIP GAJI BULANAN</div>
                <div class="doc-subtitle">[ DOKUMEN SAH / LUNAS ]</div>
            </td>
        </tr>
    </table>

    <!-- Informasi Identitas Karyawan & Dokumen -->
    <table class="meta-table">
        <tr>
            <td class="meta-label">Nama Karyawan</td>
            <td class="meta-val"><strong>' . e($row['nama']) . '</strong></td>
            <td class="meta-label">Nomor Slip</td>
            <td class="meta-val"><strong>' . e($noSlip) . '</strong></td>
        </tr>
        <tr>
            <td class="meta-label">Jabatan / Posisi</td>
            <td class="meta-val">' . e($row['jabatan']) . '</td>
            <td class="meta-label">Periode Gaji</td>
            <td class="meta-val"><strong>' . e($periodeFormatted) . '</strong></td>
        </tr>
        <tr>
            <td class="meta-label">Email</td>
            <td class="meta-val">' . e($row['email'] ?? '-') . '</td>
            <td class="meta-label">Tgl Pembayaran</td>
            <td class="meta-val">' . e($tanggalFormatted) . '</td>
        </tr>
        <tr>
            <td class="meta-label">No. WhatsApp</td>
            <td class="meta-val">' . e($row['no_hp'] ?? '-') . '</td>
            <td class="meta-label">Status</td>
            <td class="meta-val" style="color:#059669; font-weight:bold;">TERBAYARKAN</td>
        </tr>
    </table>

    <!-- Rincian Penghasilan & Potongan -->
    <table class="salary-table">
        <thead>
            <tr>
                <th style="width:55%;">DESKRIPSI / KOMPONEN GAJI</th>
                <th style="width:45%;" class="text-right">JUMLAH (RP)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="2" style="background:#f8fafc; font-weight:bold; color:#059669;">A. PENGHASILAN</td>
            </tr>
            <tr>
                <td>• Gaji Pokok</td>
                <td class="text-right">' . rupiah((float)$row['gaji_pokok']) . '</td>
            </tr>
            <tr>
                <td>• Uang Lembur</td>
                <td class="text-right">' . rupiah((float)$row['lembur']) . '</td>
            </tr>
            <tr class="row-subtotal">
                <td>TOTAL PENGHASILAN (A)</td>
                <td class="text-right" style="color:#059669;">' . rupiah($calc['total_penghasilan']) . '</td>
            </tr>
            <tr>
                <td colspan="2" style="background:#f8fafc; font-weight:bold; color:#dc2626;">B. POTONGAN</td>
            </tr>
            <tr>
                <td>• Pinjaman / Potongan Karyawan</td>
                <td class="text-right">' . rupiah((float)$row['pinjaman']) . '</td>
            </tr>
            <tr class="row-subtotal">
                <td>TOTAL POTONGAN (B)</td>
                <td class="text-right" style="color:#dc2626;">' . rupiah($calc['total_potongan']) . '</td>
            </tr>
        </tbody>
    </table>

    <!-- Kotak Highlight Gaji Bersih (Take Home Pay) -->
    <div class="highlight-box">
        <table class="highlight-table">
            <tr>
                <td class="net-text">
                    TOTAL GAJI BERSIH (A - B)<br>
                    <span style="font-size:10px; font-weight:normal; color:#475569;">Take Home Pay Periode ' . e($periodeFormatted) . '</span>
                </td>
                <td class="net-amount">
                    ' . rupiah($calc['gaji_bersih']) . '
                </td>
            </tr>
        </table>
    </div>';

if (!empty($row['catatan'])) {
    $html .= '
    <div style="background:#f8fafc; border:1px solid #e2e8f0; padding:8px 12px; margin-bottom:20px; font-size:10px;">
        <strong>Catatan:</strong> ' . e($row['catatan']) . '
    </div>';
}

$html .= '
    <!-- Tanda Tangan Dokumen -->
    <table class="signature-table">
        <tr>
            <td>
                Diterima oleh,<br>
                <div class="signature-space"></div>
                <strong>( ' . e($row['nama']) . ' )</strong><br>
                Karyawan
            </td>
            <td>
                Disetujui oleh,<br>
                <div class="signature-space"></div>
                <strong>( ' . e($_SESSION['user_name'] ?? 'HRD Manager') . ' )</strong><br>
                Staff Administrasi / HRD
            </td>
        </tr>
    </table>

    <div class="footer-note">
        Dokumen ini diterbitkan secara otomatis dan sah oleh Sistem GajiHub.<br>
        Dicetak pada: ' . date('d-m-Y H:i:s') . ' WIB
    </div>
</body>
</html>';

// Konfigurasi Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Arial');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Mode: 'download' (attachment unduh langsung) atau 'preview' (pratinjau inline di tab baru)
$action = $_GET['action'] ?? 'download';
$attachment = ($action === 'preview') ? false : true;

$dompdf->stream($filename, ['Attachment' => $attachment]);
exit;
