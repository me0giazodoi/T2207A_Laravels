<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Srmklive\PayPal\Services\PayPal as PayPalClient;
class WebController extends Controller
{
    public function home(){
        // lay sp trong db
        $new_products = Product::orderBy("id","desc")->limit(6)->get();
        $categories = Category::limit(10)->get();
        $products = Product::paginate(12);
        return view("welcome",[
            "new_products"=>$new_products,
            "categories"=>$categories,
            "products"=>$products
        ]);
    }

    public function shop(){
        return view("shop");
    }

    public function product(Product $product){
        $categories = Category::limit(10)->get();
        return view("product",[
            "categories"=>$categories,
            "product"=>$product
        ]);
    }

    public function search(Request $request){
        $q = $request->get("q");
        $limit = $request->has("limit")?$request->get("limit"):18;
        $categories = Category::limit(10)->get();
//        $products = Product::where("name",$q)->paginate(18);
        $products = Product::where("name",'like',"%$q%")->paginate($limit);
//        dd($products);
        return view("search",
            [
                "categories"=>$categories,
                "products"=>$products
            ]);
    }

    public function category(Category $category){
//        $category = Category::find($id);
//        if($category==null)
//            return abort(404);
//        $category = Category::findOrFail($id);
        $products = Product::where("category_id",$category->id)->paginate(18);
        $categories = Category::limit(10)->get();
        return view("category",
            [
                "categories"=>$categories,
                "products"=>$products,
                "category"=>$category
            ]);
    }

    public function cart(){
        $products = session()->has("cart")?session()->get("cart"):[];
        $categories = Category::limit(10)->get();
        $total = 0;
        foreach ($products as $item){
            $total+= $item->price * $item->buy_qty;
        }
        return view("cart",[
            "products"=>$products,
            "categories"=>$categories,
            "total"=>$total
        ]);
    }

    public function addToCart(Product $product,Request $request){
        $cart = session()->has("cart")?session()->get("cart"):[];
        $qty = $request->has("qty")?$request->get("qty"):1;
        foreach ($cart as $item){
            if($item->id == $product->id){
                $item->buy_qty = $item->buy_qty+$qty;
                session(["cart"=>$cart]);
                return redirect()->to("/cart");
            }
        }
        $product->buy_qty = $qty;
        $cart[] = $product;
        session(["cart"=>$cart]);
        return redirect()->to("/cart");
    }

    public function checkout(){
        $products = session()->has("cart")?session()->get("cart"):[];
        $categories = Category::limit(10)->get();
        $total = 0;
        foreach ($products as $item){
            $total+= $item->price * $item->buy_qty;
        }
        return view("checkout",[
            "products"=>$products,
            "categories"=>$categories,
            "total"=>$total
        ]);
    }

    public function placeOrder(Request $request){
        $request->validate([
            "firstname"=>"required",
            "lastname"=>"required",
            "address"=>"required",
            "phone"=>"required|min:10|max:20",
            "email"=>"required",
            "payment_method"=>"required",
        ],[
            "required"=>"Vui lòng điền đầy đủ thông tin",
            "min"=>"Phải nhập tối thiểu :min",
            "max"=>"Nhập giá trị không vượt quá :max"
        ]);
        $products = session()->has("cart")?session()->get("cart"):[];
        $total = 0;
        foreach ($products as $item){
            $total+= $item->price * $item->buy_qty;
        }

        $order = Order::create([
            "firstname"=>$request->get("firstname"),
            "lastname"=>$request->get("lastname"),
            "country"=>$request->get("country"),
            "address"=>$request->get("address"),
            "city"=>$request->get("city"),
            "state"=>$request->get("state"),
            "postcode"=>$request->get("postcode"),
            "phone"=>$request->get("phone"),
            "email"=>$request->get("email"),
            "total"=>$total,
            "payment_method"=>$request->get("payment_method"),
            //  "is_paid"=>false,
            //   "status"=>0,
        ]);
        foreach ($products as $item){
            DB::table("order_products")->insert([
                "order_id"=>$order->id,
                "product_id"=>$item->id,
                "buy_qty"=>$item->buy_qty,
                "price"=>$item->price
            ]);
        }
        // xoa gio hang
        session()->forget("cart");
        // thanh toan bang paypal
        if($order->payment_method == "PAYPAL") {
            $provider = new PayPalClient;
            $provider->setApiCredentials(config('paypal'));
            $paypalToken = $provider->getAccessToken();

            $response = $provider->createOrder([
                "intent" => "CAPTURE",
                "application_context" => [
                    "return_url" => route('successTransaction', ["order" => $order->id]),
                    "cancel_url" => route('cancelTransaction', ["order" => $order->id]),
                ],
                "purchase_units" => [
                    0 => [
                        "amount" => [
                            "currency_code" => "USD",
                            "value" => number_format($total, 2,".","")
                        ]
                    ]
                ]
            ]);

            if (isset($response['id']) && $response['id'] != null) {

                // redirect to approve href
                foreach ($response['links'] as $links) {
                    if ($links['rel'] == 'approve') {
                        return redirect()->away($links['href']);
                    }
                }

            }
        }else if($order->payment_method == "VNPAY"){
            // thanh toan = vnpay
        }
        // end
        return redirect()->to("/thank-you/".$order->id);
    }

    public function thankYou(Order $order){
        $categories = Category::limit(10)->get();
        return view("thankyou",[
            'order'=>$order,
            "categories"=>$categories
        ]);
    }

    public function successTransaction(Order $order,Request $request){
        $order->update(["is_paid"=>true,"status"=>1]);// đã thanh toán, trạng thái về xác nhận
        return redirect()->to("/thank-you/".$order->id);
    }

    public function cancelTransaction(Order $order,Request $request){
        return redirect()->to("/thank-you/".$order->id);
    }
}
