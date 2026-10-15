@extends('kelola_musyrifah.layout')

@section('title', 'Upload Excel Musyrifah')

@section('content')
    @php($result = session('import_result'))

    @if($result)
        <div class="alert {{ empty($result['errors']) ? 'alert-success' : 'alert-error' }}">
            <strong>Hasil upload:</strong>
            {{ $result['updated'] }} siswa diperbarui,
            {{ $result['unchanged'] }} sudah sesuai (tidak berubah),
            {{ count($result['errors']) }} baris gagal.
            @if(!empty($result['errors']))
                <ul style="margin:8px 0 0; padding-left:18px; max-height:260px; overflow:auto;">
                    @foreach($result['errors'] as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    <div class="card" style="max-width:640px;">
        <p style="margin-top:0;">
            Upload file Excel untuk mengisi musyrifah siswa.
            Format kolom (baris pertama boleh berisi judul):
        </p>
        <div class="table-wrap" style="margin-bottom:14px;">
            <table>
                <thead><tr><th>NIS</th><th>NAMA</th><th>NAMA MUSYRIFAH</th></tr></thead>
                <tbody><tr><td>1301154344</td><td>Farrelep Muallimaat</td><td>Musrifah</td></tr></tbody>
            </table>
        </div>
        <ul class="muted" style="margin:0 0 16px; padding-left:18px;">
            <li>Siswa dicocokkan berdasarkan <strong>NIS</strong>; kolom NAMA hanya untuk keterangan.</li>
            <li><strong>NAMA MUSYRIFAH</strong> dicocokkan dengan nama atau username di Data Musyrifah.</li>
            <li>Format file: .xlsx, .xls, atau .csv (maks. 5 MB).</li>
        </ul>

        <form method="POST" action="{{ route('kelola-musyrifah.upload.import') }}" enctype="multipart/form-data" class="toolbar" style="margin-bottom:0;">
            @csrf
            <input type="file" name="file" accept=".xlsx,.xls,.csv" class="input" required>
            <button type="submit" class="btn btn-primary"><i class="fas fa-file-arrow-up"></i> Upload</button>
            <a href="{{ route('kelola-musyrifah.upload.template') }}" class="btn btn-light"><i class="fas fa-download"></i> Template</a>
        </form>
    </div>
@endsection
