<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/menu', function (Request $request) {
    $katakunci = trim((string) $request->query('q', ''));
    $kategoriMenuId = $request->query('kategori_menu_id', '');

    try {
        $pdo = DB::connection()->getPdo();

        $sql = 'SELECT m.id, m.kode_menu, m.nama_menu, m.barcode, m.harga, m.kategori_menu_id, k.nama_kategori
                FROM menu AS m
                JOIN kategori_menu AS k ON k.id = m.kategori_menu_id
                WHERE 1=1';
        $parameter = [];

        if ($katakunci !== '') {
            $sql .= ' AND (m.kode_menu LIKE :q_kode OR m.nama_menu LIKE :q_nama OR m.barcode LIKE :q_barcode)';
            $nilaiPencarian = '%' . $katakunci . '%';
            $parameter['q_kode'] = $nilaiPencarian;
            $parameter['q_nama'] = $nilaiPencarian;
            $parameter['q_barcode'] = $nilaiPencarian;
        }

        if ($kategoriMenuId !== '') {
            $sql .= ' AND m.kategori_menu_id = :kategori_id';
            $parameter['kategori_id'] = (int) $kategoriMenuId;
        }

        $sql .= ' ORDER BY m.kode_menu';

        $ambilMenu = $pdo->prepare($sql);
        $ambilMenu->execute($parameter);
        $daftarMenu = $ambilMenu->fetchAll(\PDO::FETCH_ASSOC);

        $ambilKategori = $pdo->prepare('SELECT id, nama_kategori FROM kategori_menu ORDER BY nama_kategori');
        $ambilKategori->execute();
        $daftarKategori = $ambilKategori->fetchAll(\PDO::FETCH_ASSOC);

        return view('menu', compact('daftarMenu', 'daftarKategori', 'katakunci', 'kategoriMenuId'));
    } catch (\Throwable $error) {
        report($error);
        return response('Koneksi atau query basis data gagal. Periksa file .env dan layanan MySQL.', 500);
    }
})->name('menu.index');

Route::post('/menu', function (Request $request) {
    $request->merge([
        'kode_menu' => strip_tags(trim((string) $request->input('kode_menu'))),
        'nama_menu' => strip_tags(trim((string) $request->input('nama_menu'))),
        'barcode'   => strip_tags(trim((string) $request->input('barcode'))),
        'harga'     => trim((string) $request->input('harga')),
    ]);

    $data = $request->validate([
        'kode_menu'        => ['required', 'string', 'size:10', 'alpha_num'],
        'nama_menu'        => ['required', 'string', 'min:3', 'max:100'],
        'barcode'          => ['required', 'string', 'min:5', 'max:30'],
        'harga'            => ['required', 'integer', 'between:1000,100000'],
        'kategori_menu_id' => ['required', 'integer'],
    ], [
        'kode_menu.required' => 'Kode menu wajib diisi.',
        'kode_menu.size'     => 'Kode menu harus tepat 10 karakter.',
        'kode_menu.alpha_num'=> 'Kode menu harus berupa karakter alfanumerik.',
        'nama_menu.required' => 'Nama menu wajib diisi.',
        'nama_menu.min'      => 'Nama menu minimal 3 karakter.',
        'nama_menu.max'      => 'Nama menu maksimal 100 karakter.',
        'barcode.required'   => 'Barcode wajib diisi.',
        'barcode.min'        => 'Barcode minimal 5 karakter.',
        'barcode.max'        => 'Barcode maksimal 30 karakter.',
        'harga.required'     => 'Harga wajib diisi.',
        'harga.integer'      => 'Harga harus berupa angka.',
        'harga.between'      => 'Harga harus di antara 1.000 dan 100.000.',
        'kategori_menu_id.required' => 'Kategori menu wajib dipilih.',
    ]);

    try {
        $pdo = DB::connection()->getPdo();

        $cekDuplikat = $pdo->prepare('SELECT COUNT(*) FROM menu WHERE kode_menu = :kode OR barcode = :barcode');
        $cekDuplikat->execute(['kode' => $data['kode_menu'], 'barcode' => $data['barcode']]);

        if ((int) $cekDuplikat->fetchColumn() > 0) {
            return back()->withInput()->with('gagal', 'Kode menu atau barcode sudah digunakan.');
        }

        $simpan = $pdo->prepare('INSERT INTO menu (kode_menu, nama_menu, barcode, harga, kategori_menu_id) 
                                 VALUES (:kode_menu, :nama_menu, :barcode, :harga, :kategori_menu_id)');
        $simpan->execute([
            'kode_menu'        => $data['kode_menu'],
            'nama_menu'        => $data['nama_menu'],
            'barcode'          => $data['barcode'],
            'harga'            => $data['harga'],
            'kategori_menu_id' => $data['kategori_menu_id'],
        ]);

        return redirect()->route('menu.index')->with('berhasil', 'Data menu berhasil ditambahkan.');
    } catch (\Throwable $error) {
        report($error);
        return back()->withInput()->with('gagal', 'Data gagal disimpan. Periksa koneksi dan data.');
    }
})->name('menu.store');

Route::get('/menu/{id}/edit', function (int $id) {
    try {
        $pdo = DB::connection()->getPdo();

        $ambilMenu = $pdo->prepare('SELECT id, kode_menu, nama_menu, barcode, harga, kategori_menu_id FROM menu WHERE id = :id');
        $ambilMenu->execute(['id' => $id]);
        $menu = $ambilMenu->fetch(\PDO::FETCH_ASSOC);

        if (!$menu) {
            return redirect()->route('menu.index')->with('gagal', 'Data menu tidak ditemukan.');
        }

        $ambilKategori = $pdo->prepare('SELECT id, nama_kategori FROM kategori_menu ORDER BY nama_kategori');
        $ambilKategori->execute();
        $daftarKategori = $ambilKategori->fetchAll(\PDO::FETCH_ASSOC);

        return view('menu-edit', compact('menu', 'daftarKategori'));
    } catch (\Throwable $error) {
        report($error);
        return redirect()->route('menu.index')->with('gagal', 'Data gagal dibuka untuk diubah.');
    }
})->name('menu.edit');

Route::put('/menu/{id}', function (Request $request, int $id) {
    $request->merge([
        'kode_menu' => strip_tags(trim((string) $request->input('kode_menu'))),
        'nama_menu' => strip_tags(trim((string) $request->input('nama_menu'))),
        'barcode'   => strip_tags(trim((string) $request->input('barcode'))),
        'harga'     => trim((string) $request->input('harga')),
    ]);

    $data = $request->validate([
        'kode_menu'        => ['required', 'string', 'size:10', 'alpha_num'],
        'nama_menu'        => ['required', 'string', 'min:3', 'max:100'],
        'barcode'          => ['required', 'string', 'min:5', 'max:30'],
        'harga'            => ['required', 'integer', 'between:1000,100000'],
        'kategori_menu_id' => ['required', 'integer'],
    ]);

    try {
        $pdo = DB::connection()->getPdo();

        // Cek konflik duplikat dengan id lain
        $cek = $pdo->prepare('SELECT COUNT(*) FROM menu WHERE (kode_menu = :kode OR barcode = :barcode) AND id <> :id');
        $cek->execute(['kode' => $data['kode_menu'], 'barcode' => $data['barcode'], 'id' => $id]);

        if ((int) $cek->fetchColumn() > 0) {
            return back()->withInput()->with('gagal', 'Kode menu atau barcode sudah digunakan menu lain.');
        }

        $ubah = $pdo->prepare('UPDATE menu 
                               SET kode_menu = :kode_menu, nama_menu = :nama_menu, barcode = :barcode, 
                                   harga = :harga, kategori_menu_id = :kategori_menu_id 
                               WHERE id = :id');
        $ubah->execute([
            'kode_menu'        => $data['kode_menu'],
            'nama_menu'        => $data['nama_menu'],
            'barcode'          => $data['barcode'],
            'harga'            => $data['harga'],
            'kategori_menu_id' => $data['kategori_menu_id'],
            'id'               => $id,
        ]);

        return redirect()->route('menu.index')->with('berhasil', 'Data menu berhasil diperbarui.');
    } catch (\Throwable $error) {
        report($error);
        return back()->withInput()->with('gagal', 'Data gagal diperbarui. Periksa koneksi dan data.');
    }
})->name('menu.update');

Route::delete('/menu/{id}', function (int $id) {
    try {
        $pdo = DB::connection()->getPdo();

        $hapus = $pdo->prepare('DELETE FROM menu WHERE id = :id');
        $hapus->execute(['id' => $id]);

        if ($hapus->rowCount() === 0) {
            return redirect()->route('menu.index')->with('gagal', 'Data menu tidak ditemukan.');
        }

        return redirect()->route('menu.index')->with('berhasil', 'Data menu berhasil dihapus.');
    } catch (\Throwable $error) {
        report($error);
        return redirect()->route('menu.index')->with('gagal', 'Data gagal dihapus. Periksa relasi atau koneksi.');
    }
})->name('menu.destroy');