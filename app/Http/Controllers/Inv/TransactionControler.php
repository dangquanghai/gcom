<?php

namespace App\Http\Controllers\Inv;

use Illuminate\Http\Request;
use App\Http\Controllers\SysController;
use App\Models\INV\Transaction;
use App\Models\INV\TransactionDetails;
use App\Models\PRD\Product;

use DB;
class TransactionControler extends SysController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $Vendors ;

    public function __construct()
    {
      $this->middleware('auth');
      $sql = " select 0 as id , 'All' as name union
      select id,vendor_name as name from pu_vendors where is_active = 1 ";
      $this->Vendors =  DB::connection('mysql')->select($sql);
    }

    public function index(Request $request)
    {
        if($request->has('from_date'))
            $FromDate =   $request->input('from_date');
        else
           $FromDate = $this->GetFirstDateOfMonth(date("Y"),date("M"));

        if($request->has('to_date'))
           $ToDate =   $request->input('to_date');
        else
           $ToDate =  date("Y-m-d");

        if($request->has('no'))
          $No =   $request->input('no');
        else
          $No = '';

        if($request->has('vendor_id'))
          $VendorID =   $request->input('vendor_id');
        else
          $VendorID = 0;

        $sql = " select t.id, t.no,t.the_date,v.vendor_name,t.description, sum(td.amount) as amount
        from inv_transactions t inner join inv_transaction_dt td
        on t.id = td.transaction_id
        left join pu_vendors v on t.vendor_id = v.id
        where date(t.the_date) >= '$FromDate' and date(t.the_date) <= '$ToDate' ";

        $sqlGroupBy = " group by t.id,t.no,t.the_date,v.vendor_name,t.description ";

        if( $No<>''){ $sql = $sql . " and t.no like '%$No%'" ;}
        if( $VendorID<> 0 ){ $sql = $sql . " and v.id = $VendorID " ;}

        $sql = $sql  . $sqlGroupBy ;

        $Transactions =  DB::connection('mysql')->select($sql);

        $Vendors= $this->Vendors;

        return view('INV.Transaction.Index', compact([
        'Transactions','Vendors','No','VendorID','FromDate','ToDate']));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $TheDate = date('Y-m-d');
        $Vendors= $this->Vendors;
        return view('INV.Transaction.Create',compact(['Vendors','TheDate']));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try
        {
            $data = $request->data;
            $type = $data['type'];
            $vendor_id = $data['vendor_id'];
            $no = $data['no'];
            $date = $data['the_date'];
            $description = $data['description'];

            $details = $data['detail_input'];

            DB::beginTransaction();
            $tran = new Transaction();
            $tran->no = $no;
            $tran->the_date = $date;
            $tran->description = $description;
            $tran->vendor_id =$vendor_id;
            $tran->type_id = $type;
            $tran->save();

            if(count($details)>0 && $tran->id)
            {
                foreach($details as $item)
                {
                    $product_id = $item['product_id'];
                    $price = $item['price'];
                    $quantity = $item['quantity'];
                    $note_item = $item['note'];

                    $tran_details = new TransactionDetails();
                    $tran_details->product_id = $product_id;
                    $tran_details->transaction_id = $tran->id;
                    $tran_details->price = $price;
                    $tran_details->quantity = $quantity;
                    $tran_details->amount = $price * $quantity;
                    $tran_details->note = $note_item;
                    $tran_details->save();
                }
            }
            DB::commit();
        }
        catch(Exception $ex)
        {
            dd($ex.message());
            DB::rollback();
            return 0;
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $tran = Transaction::find($id);
        return view('INV.Transaction.Show',compact('tran'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $tran = Transaction::find($id);
        $Vendors= $this->Vendors;
        return view('INV.Transaction.Edit',compact(['tran','Vendors']));
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $sql = " delete from inv_transactions where id = $id  ";
        DB::connection('mysql')->select($sql);
        //return Redirect::back();

    }
    public function editValueChange(Request $request)
    {
        // $key = $request->key;
        $key = $_GET['term'];// $request->input->get('term');
        if($key)
        {
          $prs = Product::where('name','like',"%$key%")->orWhere('sku','like',"%$key%")->get();
          view('INV.Transaction.ajax_search_complete',compact('prs'));
        }
    }
     /***
     * search sản phẩm autocomplete
     */
    public function autocompleteProduct(Request $request)
    {
        // $key = $request->key;
        $key = $_GET['term'];// trong jquery-ui  đặt tên biến này
        if($key)
        {
          $sql = " select id, sku,url_img, name from prd_products where (sku like '%$key%' or name  like '%$key%' ) ";
          $prs = DB::connection('mysql')->select($sql);
         // $prs = Product::where('name','like',"%$key%")->orWhere('sku','like',"%$key%")->get();
          return json_encode($prs) ;
        }
    }
    /**
     * Select sp để show
     */
    public function selectProduct(Request $request)
    {
        $seq = $request->seq;
        $product_id = $request->id;
        $product = Product::find($product_id);
        return view('INV.Transaction.ajax-list-product-for-tran',compact('product','seq'));
    }
    /**
     * nhập SKU vào ô search va enter hoặc dùng máy đọc mã vạch
     */
    public function checkSku($sku)
    {
        if($sku)
        {
            $prs = Product::Where('sku',$sku)->first();
            if($prs)
                return json_encode($prs->id) ;
            else
                return 0;
        }
    }
}
