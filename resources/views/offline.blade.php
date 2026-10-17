@extends('layouts.app')
@section('title', 'Offline')
@section('content')
    <div class="mx-auto max-w-md py-10">
        <x-empty title="You are offline" icon="spark">
            Check your connection and try again. Pages you open while online will work again as soon as you reconnect.
            <div class="mt-4"><button type="button" class="btn-primary" onclick="location.reload()" data-allow-resubmit>Try again</button></div>
        </x-empty>
    </div>
@endsection
