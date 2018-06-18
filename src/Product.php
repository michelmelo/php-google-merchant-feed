<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Gloudemans\Shoppingcart\Facades\Cart;
use App\produtos;
use App\classifications;
use App\types;
use App\grapelists;
use App\newsletterSubscribers;
use GuzzleHttp\Client;
use App\addresses;
use App\orders;
use App\orderProduct;
use App\User;
use App\Pagina;
use Mail;
use App\easypayNotifications;
use Illuminate\Support\Facades\Validator;

use Vitalybaev\GoogleMerchant\Feed;
use Vitalybaev\GoogleMerchant\Product;
use Vitalybaev\GoogleMerchant\Product\Availability\Availability;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
   // protected $easypay;

    public function __construct() {
    }


    public function GerarPagamentos($order_id,$user_id,$shipping_address_id) {
        //$order_id,$user_id,$shipping_address_id
        $orders = orders::where('id',$order_id)->first();
        // $user = orders::where('id',$order_id)->first();
        $shipping_address = addresses::where('id',$orders->shipping_address_id)->first();
        // dd($shipping_address);
            $client = new Client();
            // $url = "http://test.easypay.pt/_s/api_easypay_01BG.php";
             $url = "https://www.easypay.pt/_s/api_easypay_01BG.php";
                $i = $client->request('GET', $url, [
                    'query' => [
                            'ep_cin'=>'7149',
                            'ep_user'=>'MICHEL180518',
                            'ep_entity'=>'10611',
                            'ep_ref_type'=>'auto',
                            'ep_currency'=>'BRL',
                            // 'ep_currency'=>'',
                            'ep_country'=>'PT',
                            'ep_language'=>'PT',
                            't_value'=> $orders->total,
                            't_key'=>$order_id,
                            'o_name'=>$shipping_address->first_name . " " . $shipping_address->last_name,
                            'o_description'=>'Vinhos de Luxo',
                            'o_obs'=>' ',
                            'o_mobile'=>' ',
                            'o_email'=>'eu@michelmelo.pt',
                            'o_max_date'=>'2017-04-02',
                            'ep_type'=> 'boleto',
                            'ep_rec_url' => "https://br.vinhosdeluxo.com/done",
                            's_code' => '8ab439c684c9ac811c18f6997c9558b4',//vinhosdeluxo
                            // 's_code'=>'d2a9b53cabb7f6817240f0d4b6ff33a3',
                            // 's_code' => '282d992e6c7ae373a502b4fa4eec949c'
                        ]
                ]);
                $xmlString = $i->getBody();
                $xml = new \SimpleXMLElement($xmlString);
                // echo "<pre>";
                // print_r($xml);
                // echo "0000<a href='".$xml->ep_link."'>e</a>00";
                // exit;//dd($i);
                return $xml;
    }

    public function calcularQuantidade($quantidade) {
        $newFrete = 0;
        $frete = env('FRETE', 105);
        $newFrete = (($frete/3)*$quantidade);
        // if($quantidade >1 && $quantidade <3){
        //     $newFrete = (18 * $quantidade ) + ($frete/3); 
        // } elseif($quantidade >3 && $quantidade <7) {
        //     $newFrete = (10 * $quantidade ) + ($frete/3); 
        // } else {
        //     $newFrete = ($frete/2);
        // }
        return $newFrete;
    }

    public function rssgoogle() {
        // header("Content-Type: application/xml; charset=utf-8");
        $feed = new Feed("My awesome store", "https://example.com", "My awesome description");

         $produtos = produtos::where([['validForSale','=', 1],['classification','=',1]])
        ->limit(8)
        ->inRandomOrder()
        ->get();
        // dd($produtos);

        // Put products to the feed ($products - some data from database for example)
        foreach ($produtos as $product) {
            $item = new Product();
            
            // Set common product properties
            $item->setId($product->id);
            $item->setTitle($product->name);
            $item->setDescription($product->tagline);
            $item->setLink('http://pt.vinhosdeluxo.com/vinho/'.$product->id);
            $item->setImage($product->images_thumbnail);
            if ($product->quantity==1) {
                $item->setAvailability(Availability::IN_STOCK);
            } else {
                $item->setAvailability(Availability::OUT_OF_STOCK);
            }
            $item->setPrice("{$product->preco_real} BRL");
            // $item->setGoogleCategory($product->category_name);
            // $item->setBrand($product->brand->name);
            // $item->setGtin($product->barcode);
            $item->setCondition('new');
            
            // Some additional properties
            // $item->setColor($product->color);
            // $item->setSize($product->size);

            // Add this product to the feed
            $feed->addProduct($item);
        }

        // Here we get complete XML of the feed, that we could write to file or send directly
        $feedXml = $feed->build();
        echo $feedXml;
    }


    public function embreve (){
        return view('welcome');
    }
    public function index()
    {

        
        //        // https://www.easypay.pt/_s/api_easypay_01BG.php?ep_cin=9999&ep_user=USER010101&ep_entity=10611&ep_ref_type=auto&ep_type=boleto&ep_currency=BRL&ep_country=PT&ep_language=EN&t_value=15.25&t_key=3&o_name=John+Doe&o_description=&o_obs=&o_mobile=912+345+678&o_email=tec%40easypay.pt&o_max_date=2014-12-31&ep_partner=use+the+same+value+as+ep_user

        // $xmlString = $i->getBody();
        //        $xml = new \SimpleXMLElement($xmlString);
        //        echo "<pre>";
        //        print_r($xml);
        //        echo "0000<a href='".$xml->ep_link."'>e</a>00";
        //        exit;//dd($i);


        $produtos = produtos::where([['validForSale','=', 1],['classification','=',1]])
        ->limit(8)
        ->inRandomOrder()
        ->get();
        // dd($produtos->items);
        $kits = produtos::where([['validForSale','=', 1],['classification','=','2']])
        ->limit(8)
        ->inRandomOrder()
        ->get();
        return view('loja.home',['produtos'=>$produtos,'kits'=>$kits]);
    }

    public function paginas($paginas) {
        if ($paginas == 'condicoes-gerais-de-venda-online') {
            $pagina = Pagina::where('id', 1)->first();
            return view('loja.pagina', ['pagina' => $pagina]);

        } if ($paginas == 'politica-de-privacidade') {
            $pagina = Pagina::where('id', 2)->first();
            return view('loja.pagina', ['pagina' => $pagina]);

        } if ($paginas == 'informacoes-de-entregas') {
            $pagina = Pagina::where('id', 3)->first();
            return view('loja.pagina', ['pagina' => $pagina]);

        } if ($paginas == 'pagamentos-seguros') {
            $pagina = Pagina::where('id', 4)->first();
            return view('loja.pagina', ['pagina' => $pagina]);
            
        } else {
            $pagina = Pagina::where('id', 1)->first();
            return view('loja.pagina', ['pagina' => $pagina]);
        }
        
    }

    public function vinhos()
    {
        $produtos = produtos::where([['validForSale','=', 1],['classification','=',1]])->paginate(12);
        $castas = grapelists::get();
        $title = "Vinhos";
        return view('loja.listavinhos',['produtos'=>$produtos, 'title' => $title, 'castas' => $castas]);
    }
    public function vinhosfilter($id){
        $castas = grapelists::get();
        if(isset($id)){
            $produtos = produtos::where([
                                    ['validForSale','=', 1],
                                    ['classification','=',1],
                                    ['type','=',$id]
                                ])
                            ->paginate(9);
        } else {
             $produtos = produtos::where([['validForSale','=', 1],['classification','=',1]])
                            ->paginate(9);
        }
        $title = "Vinhos";
        return view('loja.listavinhos',['produtos'=>$produtos, 'title' => $title, 'castas' => $castas]);
    }

    public function vinho(Request $request, $id)
    {
        // $ip = \Request;
        // dd($ip);
        // $position = \Location::get($ip);
        // dd($position);
        $produtos = produtos::where('id',$id)->first();
        // dd($produtos);
        $title = $produtos->name;
        $vinho = types::where('id',$produtos->type)->first();
        $casta = grapelists::where('id',$produtos->grapeList)->first();
        // dd($casta);
        return view('loja.vinho',[
                        'produtos' => $produtos,
                        'title' => $title,
                        'vinho' => $vinho->name,
                        'casta' =>$casta->name,
                    ]);
    }

    public function kits()
    {
        $produtos = produtos::where([['validForSale','=', 1],['classification','=',2]])
        ->paginate(4);
        $title = "Kits";
        return view('loja.listavinhos',['produtos'=>$produtos, 'title' => $title]);
    }

    public function saveNewletter (Request $request) {
        // dd($request);
        $subscriber = newsletterSubscribers::FirstOrNew([
            'email'              => $request->email,
            'newsletter_list_id' => 1,
        ]);
        $subscriber->name = '';
        $subscriber->status = 'subscribed';
        $subscriber->save();
        // \Mail::to($subscriber->email, $subscriber->name)->queue(new SubscribeMail($subscriber));
        return redirect('/')->with('status', 'Obrigado, Vamos começar a enviar novidades em breve.');
    }

    

    public function carrinho(){
        $title = "Carrinho";
        $total = Cart::total();
        $quantidade = Cart::count();
        $frete = $this->calcularQuantidade($quantidade);
        $freteTotal = $total + $frete;
        // dd($freteTotal);
        return view('loja.carrinho', [
                            'produtos'=> array(),
                            'title' => $title,
                            'frete' => number_format($frete,2),
                            'freteTotal' =>$freteTotal]);
    }

    public function checkout(){
     $title = "Carrinho";
     // $addresses =addresse::where('user_id',)->first();
        return view('loja.checkout', ['produtos'=> array(), 'title' => $title]);    
    }

    public function checkoutSave(Request $request) {
       
       // dd($request);
    
        $email            = $request->user['email'];
        $nome             = $request->billing['first_name'];
        $apelido          = $request->billing['last_name'];
        $phone            = (isset($request->billing['phone'])) ? $request->billing['phone'] : "";
        $password         = $request->user['password'];
        $confirm_password = $request->user['confirm_password'];
        $address1         = $request->billing['address1'];
        $address2         = $request->billing['address2'];
        $postcode         = $request->billing['postcode'];
        $city             = $request->billing['city'];
        $state            = $request->billing['state'];
        $country_id       = $request->billing['country_id'];
        $cpf              = $request->billing['cpf'];
        $codigo           = ($request->billing['codigo'] == "jovempansjc") ? "jovempansjc" : null;
        // dd($codigo);

        $total = Cart::total();
        $quantidade = Cart::count();
        $frete = $this->calcularQuantidade($quantidade);
        $freteTotal = $total + $frete;

        $value = $request->session()->get('key');
        
        //enviar notificação
        $title = "encomenda";
        $content = "alguem fazendo encomenda";

        Mail::send('send', ['title' => $title, 'content' => $content], function ($message)
        {
            $message->subject("Encomenda");
            $message->from('wine@vinhosdeluxo.com', 'Vinhos de Luxo');
            $message->to('michel.melo@vinhosdeluxo.com');

        });
        
        //enviar


        // dd($request->user_id);
        if (isset($request->user_id)) {
            #utilizador ja existe e logado
            $user = User::where('id', $request->user_id)
                    ->update([
                                'name' => $nome,
                                'last_name' => $apelido,
                                'phone' => $phone
                            ]);
            #gravar endereço
            $addressesSave = addresses::updateOrCreate(['user_id' => $request->user_id, 'type'=> "SHIPPING"]);
            $addressesSave->first_name = $nome;
            $addressesSave->last_name = $apelido;
            $addressesSave->address1 = $address1;
            $addressesSave->address2 = $address2;
            $addressesSave->postcode = $postcode;
            $addressesSave->city = $city;
            $addressesSave->state = $state;
            $addressesSave->country_id = $country_id;
            $addressesSave->phone = $phone;
            $addressesSave->save();
            #se ja tiver update
            #criar ordem
            $shipping_address_id = $addressesSave->id;
            // $total = Cart::total();
            // dd($freteTotal);
            $orders = orders::create(
                                [
                                    'user_id' => $request->user_id,
                                    'cpf' => $cpf,
                                    'codigo' => $codigo,
                                    'shipping_address_id' => $shipping_address_id,
                                    'billing_address_id' => $shipping_address_id,
                                    'total' => $freteTotal
                                ]);
            $orders->save();
            // dd($orders);
            #gravar produtos
            $orders_id = $orders->id;
            foreach (Cart::content() as $key => $produto) {
                $orderProduct = orderProduct::updateOrCreate(['order_id'=> $orders_id, 'product_id'=> $produto->id]);
                $orderProduct->product_id = $produto->id;
                $orderProduct->quantity = $produto->qty;
                $orderProduct->price = $produto->price;
                $orderProduct->taxRate = $produto->taxRate;
                $orderProduct->save();
            }
            $session_orders_id = $request->session()->put('orders_id', $orders_id);
            $typepay = $request->session()->put('typepay', $request->typepay);
            $codigo = $request->session()->put('codigo', $codigo);

            // dd($session_orders_id);
            return redirect('/pay')->with(  
                                        'order_id', $orders_id);
        } else {
            $valid = User::where('email', $email)->first();
            if ($valid == null) {
                if ($password != $confirm_password) {
                    # msg de erro
                }
                $criar = User::create([
                            'name'          => $nome,
                            'last_name'     => $apelido,
                            'phone'         => $phone,
                            'email'         => $email,
                            'password'      => bcrypt($password),
                        ]);
                $user_id = $criar->id;
                $addressesSave = addresses::updateOrCreate(['user_id' => $user_id, 'type'=> "SHIPPING"]);
                $addressesSave->first_name = $nome;
                $addressesSave->last_name = $apelido;
                $addressesSave->address1 = $address1;
                $addressesSave->address2 = $address2;
                $addressesSave->postcode = $postcode;
                $addressesSave->city = $city;
                $addressesSave->state = $state;
                $addressesSave->country_id = $country_id;
                $addressesSave->phone = $phone;
                $addressesSave->save();
                $shipping_address_id = $addressesSave->id;
                // $total = Cart::total();
                $orders = orders::create(
                                    [
                                        'user_id' => $user_id,
                                        'cpf' => $cpf,
                                        'codigo' => $codigo,
                                        'shipping_address_id' => $shipping_address_id,
                                        'billing_address_id' => $shipping_address_id,
                                        'total' => $freteTotal
                                    ]);
                $orders->save();
                $orders_id = $orders->id;
                // dd(Cart::content());
                // dd($orders_id);
                foreach (Cart::content() as $key => $produto) {
                    // dd($produto);
                    $orderProduct = orderProduct::updateOrCreate(['order_id'=> $orders_id, 'product_id'=> $produto->id]);
                    $orderProduct->product_id = $produto->id;
                    $orderProduct->quantity = $produto->qty;
                    $orderProduct->price = $produto->price;
                    $orderProduct->taxRate = $produto->taxRate;
                    $orderProduct->save();
                }
                // dd($orderProduct);
                $session_orders_id = $request->session()->put('orders_id', $orders_id);
                $typepay = $request->session()->put('typepay', $request->typepay);
                $codigo = $request->session()->put('codigo', $codigo);

                // dd($session_orders_id);
                return redirect('/pay')->with(  
                                            'order_id', $orders_id);


            } else {
                # existe já o email. retornar mensagem de erro.
               return redirect('/login');
            }
            #criar login
            #salvar endereço
            #se ja tiver atualizar
            #criar ordem
            #gravar produtos
        }

        return redirect('/pay');
    }
    public function done(Request $request) {
        Cart::destroy();
         $pagamentos = easypayNotifications::where('ep_reference', '=', $_GET['r'])->update([
            'ep_status' => (isset($_GET['s'])) ? $_GET['s'] : 0,
            'ep_doc' => (isset($_GET['k'])) ? $_GET['k'] : 0,
            // 'ep_value' => $data['ep_value'],
            'ep_date' => date("Y-m-d H:i:s"),
            // 'ep_payment_type' => $data['ep_payment_type'],
            // 'ep_value_fixed' => $data['ep_value_fixed'],
            // 'ep_value_var' => $data['ep_value_var'],
            // 'ep_value_tax' => $data['ep_value_tax'],
            // 'ep_value_transf' => $data['ep_value_transf'],
            // 'ep_date_transf' => $data['ep_date_transf'],
            // 't_key' => $data['t_key']
        ]);
        return view('loja.done');   
    }
    public function pay(Request $request){
        $title = "Pagamentos";
        $order_id = $request->session()->get('orders_id');
        $typepay = $request->session()->get('typepay');
        $codigo = $request->session()->get('codigo');

        // dd($order_id);
        $orders = orders::where('id', $order_id)->first();
        // dd($orders);
        $user_id = $orders->user_id;
        $shipping_address_id = $orders->shipping_address_id;
        $easyPay = $this->GerarPagamentos($order_id,$user_id,$shipping_address_id);
        // dd($easyPay);
        if ($typepay == "boleto") {
            // newsletterSubscribers
            $pagamentos = easypayNotifications::insert([
                'ep_cin' => $easyPay->ep_cin,
                'ep_user' => $easyPay->ep_user,
                'ep_reference' => $easyPay->ep_reference,
                'ep_value' => $easyPay->ep_value,
                't_key' => $easyPay->t_key,
                'ep_type' =>'BOLETO'
            ]);
        } else {
            $pagamentos = easypayNotifications::insert([
                'ep_cin' => $easyPay->ep_cin,
                'ep_user' => $easyPay->ep_user,
                'ep_reference' => $easyPay->ep_reference,
                'ep_value' => $easyPay->ep_value,
                't_key' => $easyPay->t_key,
                'ep_type' =>'CARTAOCREDITO'
            ]);
        }
        // dd($codigo);
        if ($codigo != null) {
            Cart::destroy();
        }
        

        return view('loja.pagamentos', [
                            'produtos'=> array(),
                            'title' => $title,
                            'codigo' => $codigo,
                            'typepay' => $typepay,
                            'ep_link' => $easyPay->ep_link,
                            'ep_boleto' =>$easyPay->ep_boleto
                        ]);    
    }
    public function add(Request $request) {

        $test = Cart::add($request->produto_id, $request->produto_name, 1, $request->produto_preco);
        // dd($test);
        $carrinho = array();
        $i = 0;
        echo json_encode(
                    array(
                        'total'=> Cart::count(),
                        'produtos'=>$carrinho
                    )
                );
    }
    public function update(Request $request) {

        // $test = Cart::add($request->produto_id, $request->produto_name, 1, $request->produto_preco);
        Cart::update($request->produto_id, $request->quantidade);
        $carrinho = array();
        $i = 0;
        echo json_encode(
                    array(
                        'total'=> Cart::count(),
                        'produtos'=>$carrinho
                    )
                );
    }
    public function delete(Request $request) {

        // $test = Cart::add($request->produto_id, $request->produto_name, 1, $request->produto_preco);
        Cart::remove($request->produto_id);
        $carrinho = array();
        $i = 0;
        echo json_encode(
                    array(
                        'total'=> Cart::count(),
                        'produtos'=>$carrinho
                    )
                );
    }
}
