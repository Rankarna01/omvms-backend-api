<!DOCTYPE html>
<html>
<head>
    <title>Report Lembur</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        th { background-color: #f4f4f5; color: #333; }
        .text-left { text-align: left; }
        .over-limit { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <h2>Laporan Lembur Karyawan</h2>
    <p><strong>Departemen:</strong> {{ $dept_name }}</p>
    <p><strong>Dicetak pada:</strong> {{ $date }}</p>

    <table>
        <thead>
            <tr>
                <th class="text-left">Nama Karyawan</th>
                <th>Lembur Hari Ini (Maks 4j)</th>
                <th>Lembur Minggu Ini (Maks 18j)</th>
                <th>Voucher Mingguan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $item)
            <tr>
                <td class="text-left">{{ $item['name'] }}</td>
                <td class="{{ $item['dailyDuration'] > 4 ? 'over-limit' : '' }}">{{ $item['dailyDuration'] }} Jam</td>
                <td class="{{ $item['weeklyDuration'] > 18 ? 'over-limit' : '' }}">{{ $item['weeklyDuration'] }} Jam</td>
                <td>{{ $item['vouchersIssued'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html> 