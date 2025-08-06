@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('管理者ダッシュボード') }}</div>

                <div class="card-body">
                    {{ __('管理者としてログインしました。') }}
                    <p>ここには管理者専用のコンテンツが表示されます。</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection