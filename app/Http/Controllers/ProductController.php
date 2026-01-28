<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index() {
        return Inertia::render('Products/Index', [
            'products' => Product::join('stores', 'products.store_id', '=', 'stores.id')
                ->select('products.*', 'stores.name as store_name')
                ->latest('products.created_at')
                ->paginate(10),
            'stores' => Store::all(['id', 'name']),
        ]);
    }

    public function store(Request $request) {
        $data = $request->validate([
            'id'       => 'nullable|numeric',
            'store_id' => 'required|exists:stores,id',
            'name'     => 'required|string|max:150',
            'sku'      => 'nullable|string|max:50',
            'selling_price'    => 'required|numeric|min:0',
            'stock'    => 'required|integer',
        ]);

        Product::updateOrCreate(['id' => $request->id], $data);

        return back()->with('message', 'Product saved!');
    }

    public function destroy($id) {
        Product::findOrFail($id)->delete();
        return back();
    }
}