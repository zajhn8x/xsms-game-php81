
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LotteryController extends Controller
{
    public function index()
    {
        $path = storage_path('../attached_assets/xsmb.csv');
        $data = array_map('str_getcsv', file($path));
        $headers = array_shift($data);
        
        return view('lottery.index', compact('data', 'headers'));
    }
}
