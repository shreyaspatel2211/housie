<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Game;
use App\Models\GameUser;
use App\Models\User;
use Carbon\Carbon;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Kreait\Laravel\Firebase\Facades\Firebase;

class SendGameStartNotification extends Command
{
    protected $signature = 'notify:upcoming-games';
    protected $description = 'Send notifications 10 minutes before game start to joined users';

    public function handle()
    {
        $now = Carbon::now();
        $inTenMinutes = $now->copy()->addMinutes(10);

        // Combine date and time from Game
        $games = Game::whereDate('date', $inTenMinutes->toDateString())
            ->get()
            ->filter(function ($game) use ($inTenMinutes) {
                $gameDateTime = Carbon::parse($game->date . ' ' . $game->time);
                return $gameDateTime->greaterThanOrEqualTo(now()) &&
                       $gameDateTime->lessThanOrEqualTo($inTenMinutes);
            });

        if ($games->isEmpty()) {
            $this->info('No upcoming games found within the next 10 minutes.');
            return;
        }

        $messaging = Firebase::messaging();

        foreach ($games as $game) {
            $userIds = GameUser::where('game_id', $game->id)->pluck('user_id');
            $tokens = User::whereIn('id', $userIds)
                ->whereNotNull('device_token')
                ->pluck('device_token')
                ->toArray();

            if (empty($tokens)) {
                $this->info("No tokens found for Game ID {$game->id}");
                continue;
            }

            $notificationTitle = 'Game Reminder';
            $notificationBody = 'Your game starts in less than 10 minutes!';

            $firebaseNotification = FirebaseNotification::create($notificationTitle, $notificationBody);
            $message = CloudMessage::new()->withNotification($firebaseNotification);

            try {
                $report = $messaging->sendMulticast($message, $tokens);
                $this->info("Game ID {$game->id}: Notification sent to " . $report->successes()->count() . ' users');
            } catch (\Exception $e) {
                \Log::error("FCM Error for game {$game->id}: " . $e->getMessage());
            }
        }
    }

}
