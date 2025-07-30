<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CustomAd;
use TCG\Voyager\Models\Setting;

class CustomAdsController extends Controller
{
    public function index(){
        $custom_ads = CustomAd::get();

        return response()->json($custom_ads);
    }

    public function getInfoScreen()
    {
        // $infoScreen = setting('info_screen'); 
        $infoScreen = Setting::where('key', 'infoscreen.info_screen')->value('value');

        return response()->json([
            'success' => true,
            'info_screen' => $infoScreen,
        ]);
    }
}
