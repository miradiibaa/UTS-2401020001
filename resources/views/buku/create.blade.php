
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Buku</title>
</head>
<body>
    <h1>Tambah Buku</h1>

    @if (!empty($errors))
        <ul>
            @foreach ($errors as $pesan)
                <li>{{ $pesan }}</li>
            @endforeach
        </ul>
    @endif

    <form action="{{ url('/buku') }}" method="POST">
        @csrf

        <p>
            <label for="kode_buku">Kode Buku:</label><br>
            <input
                type="text"
                id="kode_buku"
                name="kode_buku"
                maxlength="10"
                required
                value="{{ $old['kode_buku'] ?? '' }}"
            >
        </p>

        <p>
            <label for="judul">Judul Buku:</label><br>
            <input
                type="text"
                id="judul"
                name="judul"
                minlength="3"
                maxlength="100"
                required
                value="{{ $old['judul'] ?? '' }}"
            >
        </p>

        <p>
            <label for="isbn">ISBN:</label><br>
            <input
                type="text"
                id="isbn"
                name="isbn"
                minlength="13"
                maxlength="13"
                required
                value="{{ $old['isbn'] ?? '' }}"
            >
        </p>

        <p>
            <label for="tahun_terbit">Tahun Terbit:</label><br>
            <input
                type="number"
                id="tahun_terbit"
                name="tahun_terbit"
                min="2000"
                max="2026"
                required
                value="{{ $old['tahun_terbit'] ?? '' }}"
            >
        </p>

        <p>
            <label for="kategori_buku_id">Kategori:</label><br>
            <select id="kategori_buku_id" name="kategori_buku_id" required>
                <option value="">-- Pilih Kategori --</option>
                @foreach ($kategori as $item)
                    <option
                        value="{{ $item->id }}"
                        @selected((string) ($old['kategori_buku_id'] ?? '') === (string) $item->id)
                    >
                        {{ $item->nama_kategori }}
                    </option>
                @endforeach
            </select>
        </p>

        <button type="submit">Simpan Buku</button>
        <a href="{{ url('/buku') }}">Kembali</a>
    </form>
</body>
</html>
