<?php

namespace App\Http\Controllers;

use App\Models\Categorie;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CategoriesController extends Controller
{
    public function getProductCategories() {
        return Categorie::all();
    }

    public function addCategory(Request $request) {
        $validation = Validator::make($request->all(), [
            'category' => ['required', 'string', 'max:30']
        ]);

        if ($validation->fails())
            return response($validation->errors(), 400);

        $capitalizedCategory = ucwords($request->category);
        
        Categorie::create([
            'category' => $capitalizedCategory
        ]);

        return ['status' => 'ok'];
    }

    public function deleteCategory($id) {
        $category = Categorie::find($id);

        if(!$category)
            return response(['err' => 'category not found'], 400);

        $category->delete();

        return ['status' => 'ok'];
    }
}
