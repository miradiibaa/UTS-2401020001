
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Buku Perpustakaan</title>
</head>
<body>
    <h1>Data Buku Perpustakaan</h1>

    @if ($error)
        <p>{{ $error }}</p>
    @else
        <form action="{{ url('/buku') }}" method="GET">
            <label for="q">Cari buku:</label>
            <input
                type="text"
                id="q"
                name="q"
                value="{{ $q }}"
                placeholder="Kode buku, judul, atau ISBN"
            >

            <label for="kategori_buku_id">Kategori:</label>
            <select id="kategori_buku_id" name="kategori_buku_id">
                <option value="">Semua Kategori</option>
                @foreach ($kategori as $item)
                    <option
                        value="{{ $item['id'] }}"
                        @selected((string) $kategoriId === (string) $item['id'])
                    >
                        {{ $item['nama_kategori'] }}
                    </option>
                @endforeach
            </select>

            <button type="submit">Cari / Filter</button>
            <a href="{{ url('/buku') }}">Reset</a>
        </form>

        <p>
            <a href="{{ url('/buku/create') }}">Tambah Buku</a>
        </p>

        @if (count($buku) > 0)
            <table border="1" cellpadding="5" cellspacing="0">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Kode Buku</th>
                        <th>Judul</th>
                        <th>ISBN</th>
                        <th>Tahun Terbit</th>
                        <th>Status Tahun</th>
                        <th>Kategori</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($buku as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $item['kode_buku'] }}</td>
                            <td>{{ $item['judul'] }}</td>
                            <td>{{ $item['isbn'] }}</td>
                            <td>{{ $item['tahun_terbit'] }}</td>
                            <td>{{ statusTahunBuku((int) $item['tahun_terbit']) }}</td>
                            <td>{{ $item['nama_kategori'] }}</td>
                            <td>
                                <a href="{{ url('/buku/' . $item['id'] . '/edit') }}">
                                    Edit
                                </a>
                                |
                                <form
                                    action="{{ url('/buku/' . $item['id']) }}"
                                    method="POST"
                                    style="display: inline;"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="submit"
                                        onclick="return confirm('Yakin ingin menghapus buku ini?')"
                                    >
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p>Data buku tidak ditemukan.</p>
        @endif
    @endif
</body>
</html>
