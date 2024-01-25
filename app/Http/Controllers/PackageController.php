<?php

namespace App\Http\Controllers;

use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PackageController extends Controller
{

    public function getPackages() {
        return Package::all();
    }

    public function addPackage(Request $request, $id) {
        $validation = Validator::make($request->all(), [
            'duration' => ['numeric', 'min:1'],
            'rate' => ['numeric', 'min:1.0']
        ]);

        if ($validation->fails())
            return response($validation->errors(), 400);

        Package::create([
            'duration_year' => $request->duration,
            'rate' => $request->rate
        ]);

        return ['status' => 'ok'];
    }

    public function deletePackage($id) {
        Package::find($id)->delete();
        return ['status' => 'ok'];
    }
}
