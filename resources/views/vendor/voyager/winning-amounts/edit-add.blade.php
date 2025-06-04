@extends('voyager::master')

@section('content')
<div class="page-content container-fluid">
    <form role="form" class="form-edit-add" action="{{ isset($dataTypeContent->id) ? route('voyager.winning-amounts.update', $dataTypeContent->id) : route('voyager.winning-amounts.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @if(isset($dataTypeContent->id))
            @method('PUT')
        @endif

        <div class="panel panel-bordered">
            <div class="panel-body">
                <!-- Dropdown for selecting a game -->
                <div class="form-group">
                    <label for="gameSelect">Select Game</label>
                    <select id="gameSelect" name="game_id" class="form-control">
                        <option value="">-- Select a Game --</option>
                        @foreach(\App\Models\Game::all() as $game)
                            <option value="{{ $game->id }}" {{ (isset($dataTypeContent->game_id) && $dataTypeContent->game_id == $game->id) ? 'selected' : '' }}>
                                {{ $game->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Dynamic winner fields -->
                <div id="winnerInputs" class="form-group"></div>

                <!-- Hidden field to store combined JSON -->
                <input type="hidden" name="amount_json" id="amount_json" />

                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </div>
    </form>
</div>
@endsection

@section('javascript')
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const gameSelect = document.getElementById("gameSelect");
        const winnerInputs = document.getElementById("winnerInputs");
        const form = document.querySelector('.form-edit-add');
        const amountJsonInput = document.getElementById("amount_json");

        gameSelect.addEventListener("change", function () {
            const gameId = this.value;
            if (!gameId) return;

            fetch(`/api/game-winners/${gameId}`)
                .then(res => res.json())
                .then(data => {
                    winnerInputs.innerHTML = "";

                    // Full house winners
                    for (let i = 1; i <= data.winner_for_full_house; i++) {
                        winnerInputs.innerHTML += `
                            <div class="form-group">
                                <label>Full House Winner ${i}</label>
                                <input type="text" name="full_house_winners[]" data-index="${i}" class="form-control" />
                            </div>`;
                    }

                    // Other category winners
                    for (let i = 1; i <= data.winner_for_other_categories; i++) {
                        winnerInputs.innerHTML += `
                            <div class="form-group">
                                <label>Other Category Winner ${i}</label>
                                <input type="text" name="other_category_winners[]" data-index="${i}" class="form-control" />
                            </div>`;
                    }
                })
                .catch(err => {
                    winnerInputs.innerHTML = "<p class='text-danger'>Failed to load winner fields.</p>";
                    console.error(err);
                });
        });

        // On form submit, create custom JSON format
        form.addEventListener("submit", function (e) {
            const data = {};

            const fullHouseInputs = document.querySelectorAll('input[name="full_house_winners[]"]');
            fullHouseInputs.forEach((input, index) => {
                const key = `full_house_winners_${index + 1}`;
                data[key] = [input.value];
            });

            const otherCatInputs = document.querySelectorAll('input[name="other_category_winners[]"]');
            otherCatInputs.forEach((input, index) => {
                const key = `other_category_winners_${index + 1}`;
                data[key] = [input.value];
            });

            amountJsonInput.value = JSON.stringify(data);
        });

        // Trigger change event if editing an existing item
        @if(isset($dataTypeContent->game_id))
            gameSelect.dispatchEvent(new Event('change'));
        @endif
    });
</script>
@endsection

