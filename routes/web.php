<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ApprovalPrestasiController;
use App\Http\Controllers\ApprovalAdminController;
use App\Http\Controllers\ApprovalKatalogController;
use App\Http\Controllers\CatatanKepribadianController;
use App\Http\Controllers\TahfidController;
use App\Http\Controllers\PresensiSholatController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [AuthController::class, 'showLogin'])->name('login.form');
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/session/keep-alive', function () {
    \App\Support\PersistentLogin::restoreIntoSession(request());

    if (session('user.username')) {
        \App\Support\PersistentLogin::set(session('user'));
    }

    return response()->json([
        'ok' => true,
        'csrf' => csrf_token(),
        'authenticated' => (bool) session('user.username'),
    ]);
})->name('session.keep-alive');

Route::get('/home', function () {
    return redirect()->route('admin.index');
})->name('home');

Route::middleware(['check.auth'])->group(function () {
    Route::get('/admin', function () {
        $app = (string) session('user.app', '');
        $role = strtolower((string) session('user.role', ''));

        return match ($app) {
            'presensi-sholat' => redirect()->route('dashboard.presensi-sholat'),
            'approval-prestasi' => redirect()->route('approval.prestasi.index'),
            'catatan-kepribadian' => redirect()->route('catatan.kepribadian.index'),
            'tahfid' => $role === 'siswa'
                ? redirect()->route('tahfid.siswa.index')
                : redirect()->route('tahfid.jadwal.index'),
            default => redirect()->route('dashboard.presensi-sholat'),
        };
    })->name('admin.index');

    Route::get('/dashboard-presensi-sholat', function () {
        return view('dashboard_presensi_sholat');
    })->name('dashboard.presensi-sholat');

    Route::get('/approval-prestasi', [ApprovalPrestasiController::class, 'index'])->name('approval.prestasi.index');
    Route::post('/approval-prestasi/action', [ApprovalPrestasiController::class, 'action'])->name('approval.prestasi.action');
    Route::get('/approval-prestasi/admin', [ApprovalAdminController::class, 'index'])->name('approval.admin.index');
    Route::post('/approval-prestasi/admin', [ApprovalAdminController::class, 'store'])->name('approval.admin.store');
    Route::post('/approval-prestasi/admin/update', [ApprovalAdminController::class, 'update'])->name('approval.admin.update');
    Route::post('/approval-prestasi/admin/delete', [ApprovalAdminController::class, 'destroy'])->name('approval.admin.delete');
    Route::get('/approval-prestasi/katalog', [ApprovalKatalogController::class, 'index'])->name('approval.katalog.index');
    Route::post('/approval-prestasi/katalog', [ApprovalKatalogController::class, 'store'])->name('approval.katalog.store');
    Route::post('/approval-prestasi/katalog/update', [ApprovalKatalogController::class, 'update'])->name('approval.katalog.update');
    Route::post('/approval-prestasi/katalog/delete', [ApprovalKatalogController::class, 'destroy'])->name('approval.katalog.delete');

    Route::get('/catatan-kepribadian', [CatatanKepribadianController::class, 'index'])->name('catatan.kepribadian.index');
    Route::get('/catatan-kepribadian/tambah', [CatatanKepribadianController::class, 'create'])->name('catatan.kepribadian.create');
    Route::get('/catatan-kepribadian/siswa', [CatatanKepribadianController::class, 'searchSiswa'])->name('catatan.kepribadian.siswa');
    Route::get('/catatan-kepribadian/{id}/ubah', [CatatanKepribadianController::class, 'edit'])->name('catatan.kepribadian.edit')->whereNumber('id');
    Route::post('/catatan-kepribadian', [CatatanKepribadianController::class, 'store'])->name('catatan.kepribadian.store');
    Route::post('/catatan-kepribadian/update', [CatatanKepribadianController::class, 'update'])->name('catatan.kepribadian.update');
    Route::post('/catatan-kepribadian/delete', [CatatanKepribadianController::class, 'destroy'])->name('catatan.kepribadian.delete');
    Route::get('/catatan-kepribadian/admin', [ApprovalAdminController::class, 'index'])->name('catatan.admin.index');
    Route::post('/catatan-kepribadian/admin', [ApprovalAdminController::class, 'store'])->name('catatan.admin.store');
    Route::post('/catatan-kepribadian/admin/update', [ApprovalAdminController::class, 'update'])->name('catatan.admin.update');
    Route::post('/catatan-kepribadian/admin/delete', [ApprovalAdminController::class, 'destroy'])->name('catatan.admin.delete');

    Route::get('/tahfid', [TahfidController::class, 'index'])->name('tahfid.jadwal.index');
    Route::get('/tahfid/jadwal/tambah', [TahfidController::class, 'create'])->name('tahfid.jadwal.create');
    Route::get('/tahfid/jadwal/{id}/ubah', [TahfidController::class, 'edit'])->name('tahfid.jadwal.edit')->whereNumber('id');
    Route::post('/tahfid/jadwal', [TahfidController::class, 'store'])->name('tahfid.jadwal.store');
    Route::post('/tahfid/jadwal/update', [TahfidController::class, 'update'])->name('tahfid.jadwal.update');
    Route::post('/tahfid/jadwal/delete', [TahfidController::class, 'destroy'])->name('tahfid.jadwal.delete');
    Route::get('/tahfid/kelas', [TahfidController::class, 'kelas'])->name('tahfid.kelas');
    Route::get('/tahfid/jadwal-kelas', [TahfidController::class, 'jadwalKelas'])->name('tahfid.jadwal.kelas');
    Route::get('/tahfid/surat', [TahfidController::class, 'surat'])->name('tahfid.surat');
    Route::get('/tahfid/siswa-cari', [TahfidController::class, 'searchSiswa'])->name('tahfid.siswa.search');
    Route::get('/tahfid/percepatan', [TahfidController::class, 'percepatan'])->name('tahfid.percepatan.index');
    Route::post('/tahfid/percepatan', [TahfidController::class, 'storePercepatan'])->name('tahfid.percepatan.store');
    Route::post('/tahfid/percepatan/delete', [TahfidController::class, 'destroyPercepatan'])->name('tahfid.percepatan.delete');
    Route::get('/tahfid/progress', [TahfidController::class, 'progress'])->name('tahfid.progress.index');
    Route::post('/tahfid/progress', [TahfidController::class, 'saveProgress'])->name('tahfid.progress.store');
    Route::get('/tahfid/hafalan', [TahfidController::class, 'siswa'])->name('tahfid.siswa.index');
    Route::post('/tahfid/hafalan/setor', [TahfidController::class, 'setor'])->name('tahfid.siswa.setor');
    Route::get('/tahfid/admin', [ApprovalAdminController::class, 'index'])->name('tahfid.admin.index');
    Route::post('/tahfid/admin', [ApprovalAdminController::class, 'store'])->name('tahfid.admin.store');
    Route::post('/tahfid/admin/update', [ApprovalAdminController::class, 'update'])->name('tahfid.admin.update');
    Route::post('/tahfid/admin/delete', [ApprovalAdminController::class, 'destroy'])->name('tahfid.admin.delete');

    Route::get('/presensi-sholat/qr', [PresensiSholatController::class, 'showQr'])->name('presensi-sholat.qr');
    Route::post('/presensi-sholat/post-sholat', [PresensiSholatController::class, 'postSholat'])->name('presensi-sholat.post-sholat');

    Route::get('/presensi-haid/qr', [PresensiSholatController::class, 'showHaidQr'])->name('presensi-haid.qr');
    Route::post('/presensi-haid/post-haid', [PresensiSholatController::class, 'postHaid'])->name('presensi-haid.post-haid');

    Route::get('/log-marifah', [PresensiSholatController::class, 'showLogMarifah'])->name('presensi.log-marifah');
    Route::get('/log-presensi', [PresensiSholatController::class, 'showLogPresensi'])->name('presensi.log-presensi');
    Route::get('/log-presensi/export-excel', [PresensiSholatController::class, 'exportLogPresensiExcel'])->name('presensi.log-presensi.export-excel');
    Route::get('/log-presensi/export-pdf', [PresensiSholatController::class, 'exportLogPresensiPdf'])->name('presensi.log-presensi.export-pdf');

    Route::get('/kelola-presensi', [PresensiSholatController::class, 'showKelolaPresensi'])->name('presensi.kelola');
    Route::get('/kelola-presensi/data', [PresensiSholatController::class, 'kelolaPresensiData'])->name('presensi.kelola.data');
    Route::post('/kelola-presensi/update', [PresensiSholatController::class, 'updatePresensi'])->name('presensi.kelola.update');

    Route::get('/rekap-sholat', [PresensiSholatController::class, 'showRekapSholat'])->name('presensi.rekap-sholat');
    Route::get('/rekap-sholat/data', [PresensiSholatController::class, 'rekapSholatData'])->name('presensi.rekap-sholat.data');
    Route::get('/rekap-sholat/export-excel', [PresensiSholatController::class, 'exportRekapSholatExcel'])->name('presensi.rekap-sholat.export-excel');
    Route::get('/rekap-sholat/export-pdf', [PresensiSholatController::class, 'exportRekapSholatPdf'])->name('presensi.rekap-sholat.export-pdf');

    Route::get('/presensi/account/ganti-password', [AuthController::class, 'showGantiPasswordPresensi'])->name('presensi.account.ganti-password');
    Route::post('/presensi/account/ganti-password', [AuthController::class, 'gantiPassword'])->name('presensi.account.ganti-password.post');
});
