<table>
    <thead>
        <tr>
            <th>No</th>
            <th>Date</th>
            <th>Description</th>
            <th>Employee</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Paid At</th>
            <th>Reference</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $row)
            <tr>
                <td>{{ $row['no'] }}</td>
                <td>{{ $row['date'] ? \Carbon\Carbon::parse($row['date'])->format('d-m-Y') : '' }}</td>
                <td>{{ $row['description'] }}</td>
                <td>{{ $row['employee'] }}</td>
                <td>{{ number_format($row['amount'], 2, ',', '.') }}</td>
                <td>{{ $row['status'] }}</td>
                <td>{{ $row['paid_at'] ? \Carbon\Carbon::parse($row['paid_at'])->format('d-m-Y') : '' }}</td>
                <td>{{ $row['reference'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
