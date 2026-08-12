<table>
    <thead>
        <tr>
            <th>No</th>
            <th>Date</th>
            <th>Description</th>
            <th>Income</th>
            <th>Expense</th>
            <th>Balance</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $row)
            <tr>
                <td>{{ $row['no'] }}</td>
                <td>{{ $row['date'] ? \Carbon\Carbon::parse($row['date'])->format('d-m-Y') : '' }}</td>
                <td>{{ $row['description'] }}</td>
                <td>{{ $row['income'] !== '' ? number_format($row['income'], 2, ',', '.') : '' }}</td>
                <td>{{ $row['expense'] !== '' ? number_format($row['expense'], 2, ',', '.') : '' }}</td>
                <td>{{ number_format($row['balance'], 2, ',', '.') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
<table>
    <tr>
        <td colspan="3"><strong>Total Income</strong></td>
        <td><strong>{{ number_format($totalIncome, 2, ',', '.') }}</strong></td>
        <td colspan="2"></td>
    </tr>
    <tr>
        <td colspan="4"><strong>Total Expense</strong></td>
        <td><strong>{{ number_format($totalExpense, 2, ',', '.') }}</strong></td>
        <td></td>
    </tr>
</table>
