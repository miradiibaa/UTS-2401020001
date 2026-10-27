<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Buku</title>
</head>
<body>
    <h1>Edit Buku</h1>

    @if (!empty($errors))
        <ul>
            @foreach ($errors as $pesan)
                <li>{{ $pesan }}</li>
            @endforeach
        </ul>
    @endif

    <form action="{{ url('/buku/' . $buku['id']) }}" method="POST">
        @csrf
        @method('PUT')

        <p>
            <label for="kode_buku">Kode Buku:</label><br>
            <input
                type="text"
                id="kode_buku"
                name="kode_buku"
                maxlength="10"
                required
                value="{{ $buku['kode_buku'] }}"
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
                value="{{ $buku['judul'] }}"
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
                value="{{ $buku['isbn'] }}"
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
                value="{{ $buku['tahun_terbit'] }}"
            >
        </p>

        <p>
            <label for="kategori_buku_id">Kategori:</label><br>
            <select id="kategori_buku_id" name="kategori_buku_id" required>
                @foreach ($kategori as $item)
                    <option
                        value="{{ $item->id }}"
                        @selected((string) $buku['kategori_buku_id'] === (string) $item->id)
                    >
                        {{ $item->nama_kategori }}
                    </option>
                @endforeach
            </select>
        </p>

        <button type="submit">Simpan Perubahan</button>
        <a href="{{ url('/buku') }}">Kembali</a>
    </form>
</body>
</html>
