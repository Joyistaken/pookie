<?php
namespace App\Http\Controllers\Web;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use DB;

use App\Http\Controllers\Controller;
use App\Models\Product;

class ProductsController extends Controller {

	use ValidatesRequests;

	public function __construct()
    {
        $this->middleware('auth:web')->except('list');
    }

	public function list(Request $request) {

		$query = Product::select("products.*");

		$query->when($request->keywords, 
		fn($q)=> $q->where("name", "like", "%$request->keywords%"));

		$query->when($request->min_price, 
		fn($q)=> $q->where("price", ">=", $request->min_price));
		
		$query->when($request->max_price, fn($q)=> 
		$q->where("price", "<=", $request->max_price));
		
		$query->when($request->order_by, 
		fn($q)=> $q->orderBy($request->order_by, $request->order_direction??"ASC"));

		$products = $query->get();

		return view('products.list', compact('products'));
	}

	public function edit(Request $request, Product $product = null) {

		if(!auth()->user()) return redirect('/');

		$product = $product??new Product();

		return view('products.edit', compact('product'));
	}

	public function save(Request $request, Product $product = null) {

		$this->validate($request, [
	        'code' => ['required', 'string', 'max:32'],
	        'name' => ['required', 'string', 'max:128'],
	        'model' => ['required', 'string', 'max:256'],
	        'description' => ['required', 'string', 'max:1024'],
	        'price' => ['required', 'numeric', 'min:0.01'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
	    ]);

		// Ensure user has permission to edit products
        if($product->id && !auth()->user()->hasPermissionTo('edit_products')) abort(401);
        // Ensure user has permission to add products
        if(!$product->id && !auth()->user()->hasPermissionTo('add_products')) abort(401);

		$product = $product??new Product();
		$product->fill($request->all());
		$product->save();

		return redirect()->route('products_list');
	}

	public function delete(Request $request, Product $product) {

		if(!auth()->user()->hasPermissionTo('delete_products')) abort(401);

		$product->delete();

		return redirect()->route('products_list');
	}

    public function buy(Request $request, Product $product) 
    {
        // Ensure user is authenticated
        if(!auth()->check()) {
            return redirect()->route('login')->withErrors('You must be logged in to purchase products.');
        }
        
        // Check if user has Customer role
        try {
            if(!auth()->user()->hasRole('Customer')) {
                return redirect()->route('products_list')->withErrors('Only customers can purchase products.');
            }
        } catch(\Exception $e) {
            \Log::error("Role check failed: " . $e->getMessage());
            return redirect()->route('products_list')->withErrors('There was an error with your account. Please contact support.');
        }
        
        $user = auth()->user();
        
        // Check if product is in stock
        if(($product->stock_quantity ?? 0) <= 0) {
            return redirect()->route('products_list')->withErrors('This product is out of stock.');
        }
        
        // Check if user has sufficient credit
        if(($user->credit ?? 0) < $product->price) {
            return redirect()->route('products_list')->withErrors('You do not have sufficient credit to purchase this product.');
        }
        
        try {
            // Process the purchase
            \DB::transaction(function() use ($user, $product) {
                // Deduct credit from user
                $user->credit = ($user->credit ?? 0) - $product->price;
                $user->save();
                
                // Reduce stock quantity
                $product->stock_quantity = ($product->stock_quantity ?? 0) - 1;
                $product->save();
                
                // Create purchase record
                \App\Models\Purchase::create([
                    'user_id' => $user->id,
                    'product_id' => $product->id,
                    'price_paid' => $product->price
                ]);
            });
            
            return redirect()->route('profile')->with('success', 'Product purchased successfully!');
        } catch(\Exception $e) {
            \Log::error("Purchase failed: " . $e->getMessage());
            return redirect()->route('products_list')->withErrors('There was an error processing your purchase. Please try again.');
        }
    }
} 