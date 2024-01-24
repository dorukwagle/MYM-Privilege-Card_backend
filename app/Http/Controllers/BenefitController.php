<?php

namespace App\Http\Controllers;

use App\Models\Benefit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BenefitController extends Controller
{
    public function addBenefit(Request $request) {
        $validation = Validator::make($request->all(), [
            'title' => ['string', 'min:5'],
            'body' => ['string', 'min:10']
        ]);

        if ($validation->fails())
            return response($validation->errors(), 404);

        Benefit::create([
            'title' => $request->title,
            'body' => $request->body
        ]);

        return ['status' => 'ok'];
    }

    public function deleteBenefit($id) {
        Benefit::find($id)->delete();
        return ['status' => 'ok'];
    }

    public function getBenefits() {
        return Benefit::all();
    }
}
