@extends('voyager::master')

@section('content')
<div class="container">
    <h2>Game #{{ $game->id }}</h2>

    <h4>Numbers Already Pushed:</h4>
    <div style="margin-bottom: 10px;">
        @foreach($queue as $num)
            <span class="badge badge-success" style="font-size: 18px;">{{ $num }}</span>
        @endforeach
    </div>

    <h4>Remaining Numbers:</h4>
    <form method="POST" action="{{ route('admin.games.pushNumber', $game->id) }}">
        @csrf
        <div style="display: flex; flex-wrap: wrap; gap: 10px;">
            @foreach($remaining as $num)
                <button name="number" value="{{ $num }}" class="btn btn-outline-primary">
                    {{ $num }}
                </button>
            @endforeach
        </div>
    </form>
</div>
@endsection
