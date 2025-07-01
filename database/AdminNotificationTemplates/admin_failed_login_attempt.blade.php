<style>
    .info-table {
        width: 50%;
        margin: auto;
        border-collapse: collapse;
        background-color: #f8f9fa;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }
    .info-table th, .info-table td {
        padding: 12px;
        border: 1px solid #dee2e6;
        text-align: left;
    }
    .info-table th {
        background-color: #6320bf;
        color: white;
        font-weight: bold;
    }
    .info-table tr:nth-child(even) {
        background-color: #f2f2f2;
    }
</style>
<table class="info-table">
    <tr>
        <th>Date/Time</th>
        <td>{{ $date }}</td>
    </tr>
    <tr>
        <th>Email</th>
        <td>{{ $email }}</td>
    </tr>
    <tr>
        <th>IP</th>
        <td>{{ $ip }}</td>
    </tr>
    <tr>
        <th>rDNS</th>
        <td>{{ $r_dns }}</td>
    </tr>
</table>
