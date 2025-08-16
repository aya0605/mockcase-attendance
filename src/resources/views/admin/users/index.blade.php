@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin_users.css') }}">
@endsection

@section('content')
<div class="container mt-5">
    <div class="card">
        <div class="card-header">
            <h4 class="mb-0">スタッフ一覧</h4>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">氏名</th>
                        <th scope="col">メールアドレス</th>
                        <th scope="col" class="text-center">月次勤怠</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td class="text-center">
                                <a href="{{ url('admin/users/' . $user->id . '/attendances') }}" class="btn btn-info btn-sm">詳細</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
