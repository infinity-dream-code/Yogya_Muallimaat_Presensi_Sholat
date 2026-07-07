@php
    $sholatNames = ['Subuh' => 'SUBUH', 'Dhuhur' => 'DHUHUR', 'Ashar' => 'ASHAR', 'Maghrib' => 'MAGHRIB', 'Isya' => 'ISYA'];
    $badges = [
        ['key' => 'TOTAL_HARI', 'label' => 'TOTAL HARI', 'class' => 'badge-total', 'altKeys' => ['TOT_HARI', 'TOTALHARI']],
        ['key' => 'SHOLAT', 'label' => 'SHOLAT', 'class' => 'badge-sholat'],
        ['key' => 'SAKIT', 'label' => 'SAKIT', 'class' => 'badge-sakit'],
        ['key' => 'IZIN', 'label' => 'IZIN', 'class' => 'badge-izin'],
        ['key' => 'ALPA', 'label' => 'ALPA', 'class' => 'badge-alpa'],
        ['key' => 'HAID', 'label' => 'HAID', 'class' => 'badge-haid'],
        ['key' => 'BELUM_PRESENSI', 'label' => 'BELUM PRESENSI', 'class' => 'badge-belum'],
    ];
@endphp
@forelse($entries as $entry)
    @php
        $title = $entry['NamaCust'] ?? $entry['NAMA'] ?? $entry['NAMASISWA'] ?? $entry['Nama'] ?? 'Siswa';
        $unitVal = $entry['UNIT'] ?? $entry['Unit'] ?? $entry['unit'] ?? null;
        $nisVal = $entry['NOCUST'] ?? $entry['nocust'] ?? $entry['NIS'] ?? $entry['NOKARTU'] ?? $entry['nis'] ?? '';
        $bulanParts = explode('-', $bulan);
        $bulanDisplay = count($bulanParts) >= 2 ? (int) $bulanParts[1] . '/' . $bulanParts[0] : $bulan;
        $searchText = strtolower($title . ' ' . $nisVal);
    @endphp
    <div class="card card-rekap card-rekap-student" data-search="{{ $searchText }}" data-unit="{{ $unitVal ?? '' }}">
        <div class="card-header">
            <div>
                <div class="card-title">{{ strtoupper($title) }}</div>
                <div class="card-sub card-sub-nis">NIS: {{ $nisVal ?: '-' }}</div>
                @if($unitVal)<div class="card-sub">Unit: {{ $unitVal }}</div>@endif
            </div>
            <div class="chip-date">{{ $bulanDisplay }}</div>
        </div>

        <div class="badge-wrap">
            @foreach($badges as $b)
                @php
                    $val = $entry[$b['key']] ?? $entry[str_replace('_', ' ', $b['key'])] ?? null;
                    if (($val === null || $val === '') && !empty($b['altKeys'])) {
                        foreach ($b['altKeys'] as $ak) {
                            $v = $entry[$ak] ?? null;
                            if ($v !== null && $v !== '') { $val = $v; break; }
                        }
                    }
                    $val = $val !== null && $val !== '' ? $val : 0;
                @endphp
                <span class="badge {{ $b['class'] }}">{{ $b['label'] }} {{ $val }}</span>
            @endforeach
        </div>

        @foreach($sholatNames as $label => $prefix)
            @php
                $subBadges = [];
                foreach ($badges as $b) {
                    if ($b['key'] === 'TOTAL_HARI') continue;
                    $k = $prefix . '_' . $b['key'];
                    $k2 = $prefix . $b['key'];
                    $num = array_search($prefix, array_values($sholatNames)) + 1;
                    $k3 = 'SHOLAT_' . $num . '_' . $b['key'];
                    $v = $entry[$k] ?? $entry[$k2] ?? $entry[$k3] ?? 0;
                    $subBadges[] = ['label' => $b['label'], 'class' => $b['class'], 'val' => $v !== null && $v !== '' ? $v : 0];
                }
            @endphp
            <div class="sholat-section">
                <div class="sholat-section-title">{{ $label }}</div>
                <div class="badge-wrap">
                    @foreach($subBadges as $sb)
                        <span class="badge {{ $sb['class'] }}">{{ $sb['label'] }} {{ $sb['val'] }}</span>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@empty
    <div class="empty"><i class="fas fa-clipboard-list"></i>Tidak ada data rekap untuk bulan {{ $bulan }}.</div>
@endforelse
@if(!empty($entries) && !empty($hasMore))
    <div class="load-more-wrap" data-page="{{ $page }}">
        <button type="button" class="btn-load-more"><i class="fas fa-chevron-down"></i> Muat lebih banyak</button>
    </div>
@endif
