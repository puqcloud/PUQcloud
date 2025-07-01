<style>
    body {
        font-family: Arial, sans-serif;
    }

    .group-table {
        width: 100%;
        margin-bottom: 20px;
        border-collapse: collapse;
        background-color: #f8f9fa;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    .group-table th, .group-table td {
        padding: 12px;
        border: 1px solid #dee2e6;
        text-align: left;
    }

    .group-header {
        background-color: #6320bf;
        color: white;
        font-weight: bold;
        text-align: left;
        padding: 12px;
    }

    .group-table tr:nth-child(even) {
        background-color: #f2f2f2;
    }
</style>

<p>Your permissions have been changed.</p>

@php
    $permissionsByGroup = [];
    foreach ($admin->permissions() as $permission) {
        $permissionsByGroup[$permission['key_group']][] = $permission;
    }
@endphp

@foreach ($permissionsByGroup as $group => $permissions)
<div>
    <div class="group-header">{{ $group }}</div>
    <table class="group-table">
        <thead>
        <tr>
            <th>Name</th>
            <th>Description</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($permissions as $permission)
        <tr>
            <td>{{ $permission['name'] }}</td>
            <td>{{ $permission['description'] }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endforeach

<a href="{{ route('admin.web.dashboard') }}">Login to Dashboard</a>
