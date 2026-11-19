<?php
namespace App\Http\Controllers;
use App\Models\Shift;
use App\Services\BillingService as B;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function __construct(private B $b) {}
    public function index(Request $r) {
        return view('pos.shift', ['shift' => Shift::where('user_id', $r->user()->id)->where('status', 'open')->first(), 'result' => session('result')]);
    }
    public function open(Request $r) {
        $this->b->openShift($r->user(), $r->validate(['start_cash' => 'required|numeric|min:0'])['start_cash']);
        return redirect()->route('pos');
    }
    public function close(Request $r) {
        $res = $this->b->closeShift($this->b->currentShift($r->user()), $r->validate(['actual_cash' => 'required|numeric|min:0'])['actual_cash']);
        return redirect()->route('shift')->with('result', $res);
    }
}
