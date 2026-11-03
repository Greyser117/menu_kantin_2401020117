<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Menu Kantin</title>
</head>
<body>
    <h1>Sistem Menu Kantin</h1>

    {{-- Feedback Operasi --}}
    @if (session('berhasil'))
        <p>{{ session('berhasil') }}</p>
    @endif
    @if (session('gagal'))
        <p>{{ session('gagal') }}</p>
    @endif

    {{-- Pesan Errors Validasi Server-Side --}}
    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    {{-- Form Tambah Data (Create) --}}
    <h2>Tambah Menu Kantin</h2>
    <form action="{{ route('menu.store') }}" method="POST">
        @csrf
        <p>Kode Menu (10 Karakter)<br>
            <input type="text" name="kode_menu" value="{{ old('kode_menu') }}">
        </p>
        <p>Nama Menu<br>
            <input type="text" name="nama_menu" value="{{ old('nama_menu') }}">
        </p>
        <p>Barcode<br>
            <input type="text" name="barcode" value="{{ old('barcode') }}">
        </p>
        <p>Harga (Rp 1.000 - Rp 100.000)<br>
            <input type="number" name="harga" value="{{ old('harga') }}">
        </p>
        <p>Kategori Menu<br>
            <select name="kategori_menu_id">
                <option value="">-- Pilih Kategori --</option>
                @foreach ($daftarKategori as $kategori)
                    <option value="{{ $kategori['id'] }}" @selected(old('kategori_menu_id') == $kategori['id'])>
                        {{ $kategori['nama_kategori'] }}
                    </option>
                @endforeach
            </select>
        </p>
        <button type="submit">Tambah Menu</button>
    </form>

    {{-- Form Pencarian dan Filter --}}
    <h2>Pencarian dan Filter</h2>
    <form action="{{ route('menu.index') }}" method="GET">
        <p>Kata Kunci (Kode / Nama / Barcode)<br>
            <input type="text" name="q" value="{{ $katakunci }}">
        </p>
        <p>Kategori Menu<br>
            <select name="kategori_menu_id">
                <option value="">-- Semua Kategori --</option>
                @foreach ($daftarKategori as $kategori)
                    <option value="{{ $kategori['id'] }}" @selected((string) $kategoriMenuId === (string) $kategori['id'])>
                        {{ $kategori['nama_kategori'] }}
                    </option>
                @endforeach
            </select>
        </p>
        <button type="submit">Cari</button>
        <a href="{{ route('menu.index') }}">Reset</a>
    </form>

    {{-- Fungsi PHP Khusus Tipe E: statusHarga --}}
    @php
        function statusHarga(int $harga): string {
            return $harga >= 20000 ? 'Premium' : 'Reguler';
        }
    @endphp

    {{-- Tabel Read Data --}}
    <h2>Daftar Menu Kantin</h2>
    <table border="1" cellpadding="6" cellspacing="0">
        <thead>
            <tr>
                <th>Kode Menu</th>
                <th>Nama Menu</th>
                <th>Barcode</th>
                <th>Harga (Rp)</th>
                <th>Kategori</th>
                <th>Status Harga</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($daftarMenu as $item)
                <tr>
                    <td>{{ $item['kode_menu'] }}</td>
                    <td>{{ $item['nama_menu'] }}</td>
                    <td>{{ $item['barcode'] }}</td>
                    <td>{{ number_format($item['harga'], 0, ',', '.') }}</td>
                    <td>{{ $item['nama_kategori'] }}</td>
                    <td>{{ statusHarga((int) $item['harga']) }}</td>
                    <td>
                        <a href="{{ route('menu.edit', $item['id']) }}">Ubah</a>
                        <form action="{{ route('menu.destroy', $item['id']) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" onclick="return confirm('Hapus data menu ini?')">Hapus</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">Data menu tidak ditemukan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>