
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

if (! function_exists('statusTahunBuku')) {
    function statusTahunBuku(int $tahun): string
    {
        return $tahun >= 2023 ? 'Baru' : 'Lama';
    }
}

Route::get('/', function () {
    return redirect('/buku');
});

Route::get('/buku', function (Request $request) {
    try {
        $pdo = DB::connection()->getPdo();

        $q = trim(strip_tags((string) $request->query('q', '')));
        $kategoriId = trim(
            strip_tags((string) $request->query('kategori_buku_id', ''))
        );

        $sql = "SELECT b.id, b.kode_buku, b.judul, b.isbn,
                       b.tahun_terbit, b.kategori_buku_id,
                       k.nama_kategori
                FROM buku AS b
                JOIN kategori_buku AS k
                    ON b.kategori_buku_id = k.id
                WHERE 1 = 1";

        $params = [];

        if ($q !== '') {
            $sql .= " AND (
                b.kode_buku LIKE :kode
                OR b.judul LIKE :judul
                OR b.isbn LIKE :isbn
            )";

            $params['kode'] = "%{$q}%";
            $params['judul'] = "%{$q}%";
            $params['isbn'] = "%{$q}%";
        }

        if ($kategoriId !== '' && ctype_digit($kategoriId)) {
            $sql .= " AND b.kategori_buku_id = :kategori";
            $params['kategori'] = (int) $kategoriId;
        }

        $sql .= " ORDER BY b.id ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $buku = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $kategoriStmt = $pdo->query(
            'SELECT id, nama_kategori
             FROM kategori_buku
             ORDER BY nama_kategori ASC'
        );

        $kategori = $kategoriStmt->fetchAll(\PDO::FETCH_ASSOC);

        return view('buku.index', [
            'buku' => $buku,
            'kategori' => $kategori,
            'q' => $q,
            'kategoriId' => $kategoriId,
            'error' => null,
        ]);
    } catch (\Throwable $e) {
        report($e);

        return response()->view('buku.index', [
            'buku' => [],
            'kategori' => [],
            'q' => '',
            'kategoriId' => '',
            'error' => 'Data buku belum dapat ditampilkan. Silakan coba kembali.',
        ], 500);
    }
});

Route::get('/buku/create', function () {
    try {
        $kategori = DB::table('kategori_buku')
            ->orderBy('nama_kategori')
            ->get();

        return view('buku.create', [
            'kategori' => $kategori,
            'errors' => [],
            'old' => [],
        ]);
    } catch (\Throwable $e) {
        report($e);

        return response(
            'Form tambah buku belum dapat ditampilkan. Silakan coba kembali.',
            500
        );
    }
});

Route::post('/buku', function (Request $request) {
    $kode = trim(strip_tags((string) $request->input('kode_buku', '')));
    $judul = trim(strip_tags((string) $request->input('judul', '')));
    $isbn = trim(strip_tags((string) $request->input('isbn', '')));
    $tahun = trim((string) $request->input('tahun_terbit', ''));
    $kategoriId = trim((string) $request->input('kategori_buku_id', ''));

    $data = [
        'kode_buku' => $kode,
        'judul' => $judul,
        'isbn' => $isbn,
        'tahun_terbit' => $tahun,
        'kategori_buku_id' => $kategoriId,
    ];

    $errors = [];

    if (!preg_match('/^[a-zA-Z0-9]{10}$/', $kode)) {
        $errors['kode_buku'] =
            'Kode buku harus tepat 10 karakter alfanumerik.';
    }

    if (mb_strlen($judul) < 3 || mb_strlen($judul) > 100) {
        $errors['judul'] =
            'Judul harus terdiri dari 3 sampai 100 karakter.';
    }

    if (!preg_match('/^[0-9]{13}$/', $isbn)) {
        $errors['isbn'] = 'ISBN harus terdiri dari tepat 13 digit angka.';
    }

    if (
        !preg_match('/^[0-9]{4}$/', $tahun) ||
        (int) $tahun < 2000 ||
        (int) $tahun > 2026
    ) {
        $errors['tahun_terbit'] =
            'Tahun terbit harus antara 2000 dan 2026.';
    }

    if ($kategoriId === '' || !ctype_digit($kategoriId)) {
        $errors['kategori_buku_id'] = 'Kategori buku wajib dipilih.';
    }

    if (!$errors) {
        try {
            $pdo = DB::connection()->getPdo();

            $cekKategori = $pdo->prepare(
                'SELECT id FROM kategori_buku WHERE id = :id'
            );
            $cekKategori->execute(['id' => (int) $kategoriId]);

            if (!$cekKategori->fetch(\PDO::FETCH_ASSOC)) {
                $errors['kategori_buku_id'] =
                    'Kategori yang dipilih tidak tersedia.';
            }

            $cekKode = $pdo->prepare(
                'SELECT id FROM buku WHERE kode_buku = :kode'
            );
            $cekKode->execute(['kode' => $kode]);

            if ($cekKode->fetch(\PDO::FETCH_ASSOC)) {
                $errors['kode_buku'] = 'Kode buku sudah digunakan.';
            }

            $cekIsbn = $pdo->prepare(
                'SELECT id FROM buku WHERE isbn = :isbn'
            );
            $cekIsbn->execute(['isbn' => $isbn]);

            if ($cekIsbn->fetch(\PDO::FETCH_ASSOC)) {
                $errors['isbn'] = 'ISBN sudah digunakan.';
            }

            if (!$errors) {
                $stmt = $pdo->prepare(
                    'INSERT INTO buku
                    (kode_buku, judul, isbn, tahun_terbit,
                     kategori_buku_id, created_at, updated_at)
                    VALUES
                    (:kode, :judul, :isbn, :tahun,
                     :kategori, NOW(), NOW())'
                );

                $stmt->execute([
                    'kode' => $kode,
                    'judul' => $judul,
                    'isbn' => $isbn,
                    'tahun' => (int) $tahun,
                    'kategori' => (int) $kategoriId,
                ]);

                return redirect('/buku')
                    ->with('success', 'Data buku berhasil ditambahkan.');
            }
        } catch (\Throwable $e) {
            report($e);

            $pesan =
                'Data buku gagal disimpan. Periksa data dan coba kembali.';

            if (
                $e instanceof \PDOException &&
                $e->getCode() === '23000'
            ) {
                $pesan = 'Kode buku atau ISBN sudah digunakan.';
            }

            $errors['database'] = $pesan;
        }
    }

    try {
        $kategori = DB::table('kategori_buku')
            ->orderBy('nama_kategori')
            ->get();

        return response()->view('buku.create', [
            'kategori' => $kategori,
            'errors' => $errors,
            'old' => $data,
        ], 422);
    } catch (\Throwable $e) {
        report($e);

        return response(
            'Data gagal diproses. Silakan coba kembali.',
            500
        );
    }
});

Route::get('/buku/{id}/edit', function (int $id) {
    try {
        $pdo = DB::connection()->getPdo();

        $stmt = $pdo->prepare(
            'SELECT id, kode_buku, judul, isbn, tahun_terbit,
                    kategori_buku_id
             FROM buku
             WHERE id = :id'
        );

        $stmt->execute(['id' => $id]);
        $buku = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$buku) {
            abort(404, 'Data buku tidak ditemukan.');
        }

        $kategori = DB::table('kategori_buku')
            ->orderBy('nama_kategori')
            ->get();

        return view('buku.edit', [
            'buku' => $buku,
            'kategori' => $kategori,
            'errors' => [],
        ]);
    } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
        throw $e;
    } catch (\Throwable $e) {
        report($e);

        return response(
            'Data buku gagal dimuat. Silakan coba kembali.',
            500
        );
    }
});

Route::put('/buku/{id}', function (Request $request, int $id) {
    $kodeBuku = strip_tags(trim($request->input('kode_buku', '')));
    $judul = strip_tags(trim($request->input('judul', '')));
    $isbn = strip_tags(trim($request->input('isbn', '')));
    $tahun = trim((string) $request->input('tahun_terbit', ''));
    $kategoriId = trim((string) $request->input('kategori_buku_id', ''));

    $errors = [];

    if (!preg_match('/^[A-Za-z0-9]{10}$/', $kodeBuku)) {
        $errors[] = 'Kode buku harus terdiri dari 10 karakter alfanumerik.';
    }

    if (strlen($judul) < 3 || strlen($judul) > 100) {
        $errors[] = 'Judul buku harus terdiri dari 3 sampai 100 karakter.';
    }

    if (!preg_match('/^[0-9]{13}$/', $isbn)) {
        $errors[] = 'ISBN harus terdiri dari 13 digit angka.';
    }

    if (!ctype_digit($tahun) || (int) $tahun < 2000 || (int) $tahun > 2026) {
        $errors[] = 'Tahun terbit harus antara 2000 dan 2026.';
    }

    if (!ctype_digit($kategoriId) || (int) $kategoriId < 1) {
        $errors[] = 'Kategori wajib dipilih.';
    }

    try {
        $pdo = DB::connection()->getPdo();

        $stmt = $pdo->prepare('SELECT id FROM buku WHERE id = :id');
        $stmt->execute(['id' => $id]);

        if (!$stmt->fetch(\PDO::FETCH_ASSOC)) {
            abort(404, 'Data buku tidak ditemukan.');
        }

        $stmt = $pdo->prepare(
            'SELECT id FROM kategori_buku WHERE id = :id'
        );
        $stmt->execute(['id' => $kategoriId]);

        if (!$stmt->fetch(\PDO::FETCH_ASSOC)) {
            $errors[] = 'Kategori buku tidak ditemukan.';
        }

        $stmt = $pdo->prepare(
            'SELECT id FROM buku
             WHERE (kode_buku = :kode OR isbn = :isbn)
             AND id <> :id'
        );
        $stmt->execute([
            'kode' => $kodeBuku,
            'isbn' => $isbn,
            'id' => $id,
        ]);

        if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
            $errors[] = 'Kode buku atau ISBN sudah digunakan buku lain.';
        }

        if ($errors) {
            $kategori = DB::table('kategori_buku')
                ->orderBy('nama_kategori')
                ->get();

            return response()->view('buku.edit', [
                'buku' => [
                    'id' => $id,
                    'kode_buku' => $kodeBuku,
                    'judul' => $judul,
                    'isbn' => $isbn,
                    'tahun_terbit' => $tahun,
                    'kategori_buku_id' => $kategoriId,
                ],
                'kategori' => $kategori,
                'errors' => $errors,
            ], 422);
        }

        $stmt = $pdo->prepare(
            'UPDATE buku
             SET kode_buku = :kode,
                 judul = :judul,
                 isbn = :isbn,
                 tahun_terbit = :tahun,
                 kategori_buku_id = :kategori,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );

        $stmt->execute([
            'kode' => $kodeBuku,
            'judul' => $judul,
            'isbn' => $isbn,
            'tahun' => (int) $tahun,
            'kategori' => (int) $kategoriId,
            'id' => $id,
        ]);

        return redirect('/buku');
    } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
        throw $e;
    } catch (\Throwable $e) {
        report($e);

        return response(
            'Data buku gagal diperbarui. Silakan coba kembali.',
            500
        );
    }
});

Route::delete('/buku/{id}', function (int $id) {
    try {
        $pdo = DB::connection()->getPdo();

        $stmt = $pdo->prepare(
            'DELETE FROM buku WHERE id = :id'
        );

        $stmt->execute(['id' => $id]);

        if ($stmt->rowCount() === 0) {
            abort(404, 'Data buku tidak ditemukan.');
        }

        return redirect('/buku');
    } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
        throw $e;
    } catch (\Throwable $e) {
        report($e);

        return response(
            'Data buku gagal dihapus. Silakan coba kembali.',
            500
        );
    }
});
