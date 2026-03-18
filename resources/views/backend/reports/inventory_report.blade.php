<!DOCTYPE html>
<html>
<head>
    <title>Inventory Report</title>
</head>
<body>
    <h1>Inventory Report</h1>
    <table border="1">
        <thead>
            <tr>
                @foreach ($columns as $column)
                    <th>{{ ucfirst(str_replace('_', ' ', $column)) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($records as $record)
                <tr>
                    @foreach ($columns as $column)
                        <td>{{ $record->$column }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>