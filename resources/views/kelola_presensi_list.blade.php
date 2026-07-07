@forelse($entries as $entry)
    @php
        $title = $entry['NamaCust'] ?? $entry['NAMA'] ?? $entry['NAMASISWA'] ?? 'Siswa';
        $unitVal = $entry['UNIT'] ?? $entry['Unit'] ?? null;
        $subtitle = $unitVal ? 'Unit: ' . $unitVal : null;
        $tanggalItem = $entry['TRXDATE'] ?? $entry['TANGGAL'] ?? $entry['DATE'] ?? $tanggal;
        $nisVal = $entry['NIS'] ?? $entry['NOKARTU'] ?? '';
        $searchText = strtolower($title . ' ' . $nisVal);
        $rowId = $entry['IDPRESENSI'] ?? $entry['ID'] ?? $entry['id'] ?? $entry['ID_PRESENSI'] ?? $entry['ID_TRX'] ?? '';
        if ($rowId === '') {
            $nokartu = $entry['NOKARTU'] ?? '';
            if ($nokartu !== '' && $tanggalItem !== '') {
                $rowId = $nokartu . '|' . $tanggalItem;
            } elseif ($title !== '' && $tanggalItem !== '') {
                $rowId = $title . '|' . $tanggalItem;
            }
        }

        // Musyrifah: pakai kolom khusus jika ada, fallback ke USER_1..5 selain "System"
        $musyrifah = $entry['Musrifah'] ?? $entry['MUSRIFAH'] ?? $entry['Musftr'] ?? '';
        if ($musyrifah === '' || strtoupper($musyrifah) === 'SYSTEM') {
            for ($i = 1; $i <= 5; $i++) {
                $k = 'USER_' . $i;
                $v = $entry[$k] ?? null;
                if ($v !== null && $v !== '' && strtoupper((string) $v) !== 'SYSTEM') {
                    $musyrifah = (string) $v;
                    break;
                }
            }
        }
    @endphp
    <div class="card card-student" data-search="{{ $searchText }}" data-unit="{{ $unitVal ?? '' }}" data-musyrifah="{{ $musyrifah }}">
        <input type="checkbox" class="card-checkbox" aria-label="Pilih siswa">
        <div class="card-header">
            <div>
                <div class="card-title">{{ $title }}</div>
                @if($subtitle)<div class="card-sub">{{ $subtitle }}</div>@endif
            </div>
            <div class="chip-date">{{ $tanggalItem }}</div>
        </div>
        @php
            $sholatRows = [];
            for ($i = 1; $i <= 5; $i++) {
                $jadwalKey = 'JADWAL_' . $i;
                $jamKey = 'JAM_' . $i;
                $userKey = 'USER_' . $i;
                $statusVal = $entry[$jadwalKey] ?? null;
                $jamVal = $entry[$jamKey] ?? null;
                $userVal = $entry[$userKey] ?? null;
                if ($statusVal !== null) {
                    $sholatRows[] = [
                        'index' => $i,
                        'status' => $statusVal,
                        'jam' => $jamVal,
                        'user' => $userVal,
                    ];
                }
            }
        @endphp
        @if(count($sholatRows))
            @php $canEdit = !empty($rowId); @endphp
            <div class="sholat-chips" aria-label="Status sholat 1-5">
                @foreach($sholatRows as $row)
                    @php
                        $s = strtoupper((string) $row['status']);
                        if (in_array($s, ['SHOLAT', 'HAID'])) {
                            $cls = 'chip chip-sholat';
                        } elseif ($s === 'IZIN' || $s === 'TIDAK HADIR') {
                            $cls = 'chip chip-izin';
                        } elseif ($s === 'ALPA') {
                            $cls = 'chip chip-alpa';
                        } elseif ($s === 'SAKIT') {
                            $cls = 'chip chip-sakit';
                        } elseif ($s === 'BELUM PRESENSI') {
                            $cls = 'chip chip-belum';
                        } else {
                            $cls = 'chip chip-sholat';
                        }
                        $subLine = $row['jam'] ? trim($row['jam'] . ' ' . ($row['status'] ?? '') . ' ' . ($row['user'] ?? '')) : ($row['status'] . ($row['user'] ? ' ' . $row['user'] : ''));
                        $titleTip = 'Sholat ' . $row['index'] . ' - ' . ($subLine ?: '-');
                    @endphp
                    <button
                        type="button"
                        class="{{ $cls }} {{ $canEdit ? 'chip-edit' : 'chip-disabled' }}"
                        title="{{ $titleTip }}"
                        {{ $canEdit ? '' : 'disabled' }}
                        data-id="{{ $rowId }}"
                        data-session="{{ $row['index'] }}"
                        data-name="{{ $title }}"
                        data-unit="{{ $subtitle ?? '' }}"
                    >
                        <span class="chip-top">S{{ $row['index'] }}</span>
                        <span class="chip-bottom">{{ strtoupper((string) ($row['status'] ?: '-')) }}</span>
                    </button>
                @endforeach
            </div>
        @endif
    </div>
@empty
    <div class="empty"><i class="fas fa-clipboard-list"></i>Tidak ada data presensi untuk tanggal ini.</div>
@endforelse
@if(!empty($entries) && !empty($hasMore))
    <div class="load-more-wrap" data-page="{{ $page }}" data-total="{{ $total }}">
        <button type="button" class="btn-load-more" id="btnLoadMore">
            <i class="fas fa-chevron-down"></i> Muat lebih banyak
        </button>
    </div>
@endif
@if(isset($allUnits) || isset($allMuslist))
    <div id="meta-filters"
         data-units="{{ isset($allUnits) ? implode('||', $allUnits) : '' }}"
         data-musyrifah="{{ isset($allMuslist) ? implode('||', $allMuslist) : '' }}"
         style="display:none;"></div>
@endif
