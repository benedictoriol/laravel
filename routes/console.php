<?php

use App\Models\BargainingOffer;
use App\Models\Shop;
use App\Models\PlatformNotification;
use App\Services\SmartOpsService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('smart-ops:scan {--shop_id=*}', function (SmartOpsService $service) {
    $shopIds = collect($this->option('shop_id'))->filter()->map(fn ($id) => (int) $id);
    $shops = $shopIds->isNotEmpty() ? Shop::whereIn('id', $shopIds)->get() : Shop::all();
    foreach ($shops as $shop) {
        $created = $service->scanShop($shop->id);
        $this->info('Scanned shop #'.$shop->id.' and created '.$created->filter()->count().' alert(s).');
    }
})->purpose('Scan shops for operational alerts');

Artisan::command('bargaining:expire', function () {
    $offers = BargainingOffer::query()
        ->where('status', 'pending')
        ->whereNotNull('expires_at')
        ->where('expires_at', '<=', now())
        ->get();
    foreach ($offers as $offer) {
        $offer->update(['status' => 'withdrawn']);
        if ($offer->offered_by_user_id) {
            PlatformNotification::create([
                'user_id' => $offer->offered_by_user_id,
                'type' => 'bargaining_offer_expired',
                'title' => 'Bargaining offer expired',
                'message' => 'Offer #'.$offer->id.' expired without a response.',
                'reference_type' => 'bargaining_offer',
                'reference_id' => $offer->id,
                'channel' => 'web',
            ]);
        }
    }
    $this->info('Expired '.$offers->count().' bargaining offer(s).');
})->purpose('Expire pending bargaining offers past their expiry timestamp');

Schedule::command('smart-ops:scan')->hourly();
Schedule::command('bargaining:expire')->everyThirtyMinutes();
