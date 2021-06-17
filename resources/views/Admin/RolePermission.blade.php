@extends('layouts.admin')
@section('content')
  
    <div class="col-md-6" style="padding-right:0px">
      
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"> Role Permissions </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="module">Modules</label>
                            <select class="form-control" name ="module" id="module">
                                {!! getList($dsMdl,0)!!}
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="function"></label>
                            <select class="form-control" name ="function" id="function" size="10">
                            
                            </select>
                        </div>
                    </div>
                   
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="role">Roles</label>
                            <select class="form-control" name ="role" id="role">
                                {!! getList($dsRol,$RolID)!!}
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="member"></label>
                            <select class="form-control" name ="member" id="member" size="10">
                           
                            </select>
                        </div>
                    </div>
                </div>

                <div id ="deivActions" class="row">
                    <div class="col-md-12">
                        <table id="table_promotion_dt"
                                class="table table-head-fixed table-hover table-bordered table-striped text-nowrap">
                                <thead>
                                    <tr>
                                        <th style="width:10px;">ID</th>
                                        <th> Role ID</th>
                                        <th> Action ID</th>
                                        <th> Action name</th>
                                        <th > Is Active</th>                                
                                    </tr>
                                </thead>
                                <tbody class="actions" id="actions">
                            
                              
                                </tbody>
                        </table>
                    </div>
                </div>
                
            </div>
               
            </div>
    </div>

@endsection
@section('scripts')
<script>
$(document).ready(function(){
    "use strict";
    var $ModuleID = 0;
    var $RolID = 0;
    var $FncID = 0;
    
    var _token = $('input[name="_token"]').val();

    // -------------------------------------------------------------------------
    $(document).on('change','#module',function(){
        $("#function").empty();
        $ModuleID = $(this).val();
        //let url = "{{route('Promotion.edit', ':id') }}";
         let $url = '{{route("admin.role.permission.function.load",':id')}}'
         $url  =  $url.replace(':id', $ModuleID);
       
        $.ajax({
            url:$url,
            //dataType:"json",
            success:function(data)
            {
               // console.log(data);
                $('#function').append(data);
            }// end fucntion success
        });
    });
    // -------------------------------------------------------------------------
    $(document).on('change','#role',function(){
       
        $("#member").empty();

        $RolID = $(this).val();
        let $url = '{{route("admin.role.permission.member.load",':id')}}'
        $url  =  $url.replace(':id', $RolID);
        $.ajax({
            url:$url,
            success:function(data)
            {
                $('#member').append(data);
            }// end fucntion success
        });

        LoadPermissionAction($ModuleID,$RolID,$FncID);
    });

 // -------------------------------------------------------------------------
 document.getElementById('function').addEventListener('change', function(e) {
  if (e.target.name==='function') {
    $FncID = e.target.value;
    
    LoadPermissionAction($ModuleID,$RolID,$FncID);
  
  }
})

// -------------------------------------------------------------------------
function LoadPermissionAction($ModuleID,$RolID,$FncID)
{
    let $url = '{{route("admin.role.permission.action.load",[':ModuleID',':RolID',':FncID'])}}';
    $url  =  $url.replace(':ModuleID', $ModuleID);
    $url  =  $url.replace(':RolID', $RolID);
    $url  =  $url.replace(':FncID', $FncID);
    //console.log($url);

    $.ajax({
        url:$url,
        success:function(data)
        {
            $('tbody').html(data);
            //$('#member').append(data);
        }// end fucntion success 
    });
   
}
// -------------------------------------------------------------------------

$(document).on('blur','.column_name',function()
    {
        var $Active =0 ;
        var $id = $(this).attr("id");
        var column_name = $(this).data('column_name');
        var ChildObj;
      
        if(column_name == "is_active")
        {
            ChildObj = this.firstChild.nextElementSibling;
            
            if($(ChildObj).prop("checked") == true){
                $Active = 1 ;
            }
            else if($(ChildObj).prop("checked") == false){
                $Active = 0 ;
            }
               
            let $url = '{{route("admin.role.permission.update",[':ID',':Active'])}}';
            $url  =  $url.replace(':ID', $id);
            $url  =  $url.replace(':Active', $Active);
            
            $.ajax({
                url:$url,
                success:function(data)
                {
                    
                }// end fucntion success 
            });
        }
   
});
// -------------------------------------------------------------------------
});

</script>
@endsection
