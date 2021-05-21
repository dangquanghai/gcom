<?php
namespace App\Http\Controllers\Prd;
use App\Models\PRD\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\SysController;
use App\Models\PRD\ProductLifeCircle;
use App\Models\PRD\ProductGroup;
use App\Http\Requests\PRD\ProductRequest;
use DB;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use Validator;
use DateTime;
use GuzzleHttp\Client;

class ProductController extends SysController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    // -------------------------------------------------
    public function index(Request $request)
    {
      if($request->has('sku'))
        $Sku = $request->input('sku');
      else
        $Sku='';

      if($request->has('product_name'))
        $ProductName  = $request->input('product_name');
      else
        $ProductName ='';

      if($request->has('product_group'))
        $ProductGroup  = $request->input('product_group');
      else
        $ProductGroup = '';

      if($request->has('life_circle'))
        $LifeCircle  = $request->input('life_circle');
      else
        $LifeCircle = '';

        $sql = " select p.id, p.sku,p.url_img, p.name as ProductName,
        pl.name as LifeCircle,  p.width, p.height, p.length
        from prd_products p inner join prd_product_lifecircle pl on p.life_circle = pl.id
        left join prd_product_groups prg on p.group_id =  prg.id
        where 1 = 1 ";

        if($Sku <> ''){$sql = $sql. " and p.sku = '$Sku' " ;}
        if($ProductName <> ''){$sql = $sql. " and p.name like  '%$ProductName%' " ;}
        if($ProductGroup <> 0){$sql = $sql. " and prg.id = $ProductGroup " ;}
        if($LifeCircle <>  0){$sql = $sql. " and pl.id  = $LifeCircle  " ;}

        $Products =  DB::connection('mysql')->select($sql);

        $sql = " select 0 as id, 'All' as name union select id, name from  prd_product_lifecircle ";
        $ProductLifeCircles = DB::connection('mysql')->select($sql);

        $sql = " select 0 as id, 'All' as name  union select id, name from  prd_product_groups ";
        $ProductGroups = DB::connection('mysql')->select($sql);

        $Sku = $request->input('sku');
        $ProductName  = $request->input('product_name');
        $ProductGroup  = $request->input('product_group');
        $LifeCircle  = $request->input('life_circle');
        return view('PRD.Product.Index',
        compact(['Products','ProductLifeCircles','ProductGroups','Sku','ProductName','ProductGroup','LifeCircle']));
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
     // ini_set('memory_limit','2548M');
     // set_time_limit(15000);

      $ProductLifeCircles = ProductLifeCircle::all();
      /*
            $strSKU = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $Sku ='';
            $Continue = true;
            while($Continue)
            {
              $Sku = substr(str_shuffle($strSKU),0,4);
              $sql = " select count(sku) as MyCount from prd_products where sku = '$Sku' ";
              if($this->IsExist('mysql',$sql))
                {$Continue = false; }

            }
      */
      return view('prd.product.create',compact(['ProductLifeCircles']));

      //return view('prd.product.createNew',compact(['ProductLifeCircles','Sku']));
      //return view('PRD.Product.Create');
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ProductRequest $request)
    {
      if(Product::create($request->all()))
      return redirect()->route('Product.index')->with(['success'=>trans('product.created')]);
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
       // return view('product.show',compact('id'));
        //return ('AAAAAAAAAAA');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {

      $ProductLifeCircles = ProductLifeCircle::all();
      $Product = Product::find($id);
      return view('PRD.Product.Edit',compact(['Product','ProductLifeCircles']));
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(ProductRequest $request,$id)
    {
     $Product = Product::find($id);
     if($Product)
     {
      $Product->update($request->all());
      //return 'Thành công';
      return redirect()->route('PRD.Product.Index')->with(['success'=>'Update sản phẩm thành công']);
     }
     else
     {
      return redirect()->route('PRD.Product.Index')->with(['error'=>'Update sản phẩm không thành công']);
      //return 'Thất bại';
     }
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
      dd($id);
    }
}
