<table>
    <thead>
        <tr>
            <th>No</th>
            <th>Date</th>
            <th>Employee</th>
            <th>User</th>
            <th>Description</th>
            <th>Amount</th>
        </tr>
    </thead>
    <tbody>
        @foreach($cashAdvances as $i => $ca)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $ca->date ? \Carbon\Carbon::parse($ca->date)->format('d-m-Y') : '' }}</td>
                <td>{{ $ca->employee->name ?? '-' }}</td>
                <td>{{ $ca->user->name ?? '-' }}</td>
                <td>{{ $ca->description }}</td>
                <td>{{ number_format($ca->amount, 2, ',', '.') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
<table>
    <tr>
        <td colspan="5"><strong>Total Amount</strong></td>
        <td><strong>{{ number_format($totalAmount, 2, ',', '.') }}</strong></td>
    </tr>
</table>
