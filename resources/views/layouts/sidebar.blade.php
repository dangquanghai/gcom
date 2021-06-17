<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="index3.html" class="brand-link">
      <img src="{{asset('dist/img/AdminLTELogo.png')}} " alt="AdminLTE Logo" class="brand-image img-circle elevation-3"
           style="opacity: .8">
      <span class="brand-text font-weight-light">GCOM</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
      <!-- Sidebar user panel (optional) -->

      <!-- Sidebar Menu -->
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
          <!-- Add icons to the links using the .nav-icon class
               with font-awesome or any other icon font library -->

          <li class="nav-item ">
            <a href="{{route('home')}}" class="nav-link active">

              <i class="nav-icon fas fa-tachometer-alt"></i>
                <p>
                  Dashboard
                </p>
            </a>
          </li>

          <li class="nav-item has-treeview">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-box"></i>
              <p>
                PRODUCT
                <i class="fas fa-angle-left right"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
              <a href="{{route('Product.index')}}" class="nav-link">
                  <i class="nav-icon fas fa-list"></i>
                  <p>Product List </p>
                </a>
              </li>
            </ul>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="" class="nav-link">
                  <i class="nav-icon fas fa-boxes"></i>
                  <p>Product Group </p>
                </a>
              </li>
            </ul>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="" class="nav-link">
                  <i class="nav-icon fas fa-layer-group"></i>
                  <p>Product Combo </p>
                </a>
              </li>
            </ul>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="" class="nav-link">
                  <i class="nav-icon fas fa-street-view"></i>
                  <p>Product Assign </p>
                </a>
              </li>
            </ul>
          </li>

          <li class="nav-item has-treeview">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-cart-plus"></i>
              <p>
                PU
                <i class="fas fa-angle-left right"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="{{route('fa.CashFlow.ImportFile')}}" class="nav-link">
                  <i class="nav-icon fas fa-people-arrows"></i>
                  <p>Partner List </p>
                </a>
              </li>
            </ul>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="nav-icon fas fa-tasks"></i>
                  <p>PU Planning</p>
                </a>
              </li>
            </ul>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="nav-icon fas fa-shopping-cart"></i>
                  <p>Purchasing Order</p>
                </a>
              </li>
            </ul>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="nav-icon fas fa-ship"></i>
                  <p>Shipment</p>
                </a>
              </li>
            </ul>

            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="nav-icon fas fa-file-alt"></i>
                  <p>PU Report</p>
                </a>
              </li>
            </ul>
          </li>

          <li class="nav-item has-treeview">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-warehouse"></i>
              <p>
                INVENTORY
                <i class="fas fa-angle-left right"></i>
              </p>
            </a>

            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="{{route('fa.CashFlow.ImportFile')}}" class="nav-link">
                  <i class="nav-icon fas fa-sitemap"></i>
                  <p>Setup Location </p>
                </a>
              </li>
            </ul>

            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="{{route('Transaction.index')}}" class="nav-link">
                  <i class="nav-icon fas fa-download"></i>
                  <p>Import </p>
                </a>
              </li>
            </ul>

            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="{{route('fa.CashFlow.ImportFile')}}" class="nav-link">
                  <i class="nav-icon fas fa-print"></i>
                  <p>Pickup & Label</p>
                </a>
              </li>
            </ul>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="{{route('fa.CashFlow.ImportFile')}}" class="nav-link">
                  <i class="nav-icon fas fa-upload"></i>
                  <p>Export </p>
                </a>
              </li>
            </ul>

            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="{{route('fa.CashFlow.ImportFile')}}" class="nav-link">
                  <i class="nav-icon fas fa-balance-scale"></i>
                  <p>Balance Report </p>
                </a>
              </li>
            </ul>

            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="{{route('fa.CashFlow.ImportFile')}}" class="nav-link">
                  <i class="nav-icon fas fa-cog"></i>
                  <p>Make New Year Balance </p>
                </a>
              </li>
            </ul>
          </li>

          <li id ="menuSales" class="nav-item has-treeview">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-chart-bar"></i>
              <p>
                SALES
                <i class="fas fa-angle-left right"></i>
              </p>
            </a>

            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="{{route('sal.product.infor.import')}}" class="nav-link">
                  <i class="nav-icon fas  fa-chart-pie"></i>
                  <p>Import Sales Product Infor</p>
                </a>
              </li>
            </ul>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="{{route('SalesProductInforController.index')}}" class="nav-link">
                  <i class="nav-icon fas  fa-chart-pie"></i>
                  <p>Sales Product Infor</p>
                </a>
              </li>
            </ul>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="{{route('Promotion.index')}}" class="nav-link">
                  <i class="nav-icon fas  fa-chart-pie"></i>
                  <p>Promotion Management</p>
                </a>
              </li>
            </ul>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="sal.wm.item_mng.import" class="nav-link">
                  <i class="nav-icon fas  fa-chart-pie"></i>
                  <p>Import WM Item MNG</p>
                </a>
              </li>
            </ul>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="sal.wm.item_mng" class="nav-link">
                  <i class="nav-icon fas  fa-chart-pie"></i>
                  <p>WM Product Management</p>
                </a>
              </li>
            </ul>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="{{route('sal.wm.actions')}}" class="nav-link">
                  <i class="nav-icon fas  fa-chart-pie"></i>
                  <p>WM-Actions </p>
                </a>
              </li>
            </ul>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="nav-icon fas  fa-chart-pie"></i>
                  <p>ForeCast </p>
                </a>
              </li>
            </ul>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="{{route('sal.selling.daily')}}" class="nav-link">
                  <i class="nav-icon fas fa-chart-line"></i>
                  <p>Selling Daily </p>
                </a>
              </li>
            </ul>
          </li>

          <li class="nav-item has-treeview">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-dollar-sign"></i>
              <p>
                FA
                <i class="fas fa-angle-left right"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="disabled" >
                <a href="#" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Import Data For PL Report</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="{{route('fa.PLReportAnnually')}}" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>PL Report Annually</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="{{route('fa.plReport.monthly')}}" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>PL Report Monthly</p>
                </a>
              </li>

              <li class="nav-item">
                <a href="{{route('fa.plReport.detail')}}" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>PL Report Detail</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="{{route('fa.CashFlow.Chart')}}" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Cash Flow</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="{{route('fa.loadMKTBudget')}}" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>MKT Budget</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="{{route('fa.avc.po')}}" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>AVC-PO</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="pages/UI/timeline.html" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Timeline</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="pages/UI/ribbons.html" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Ribbons</p>
                </a>
              </li>
            </ul>
          </li>

          <li class="nav-item has-treeview">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa fa-phone"></i>
              <p>
                CS
                <i class="fas fa-angle-left right"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="nav-icon fas fa-user-edit"></i>
                  <p>Shipping Note </p>
                </a>
              </li>
            </ul>

            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="nav-icon fas fa-thumbs-down"></i>
                  <p>Bad Review Process </p>
                </a>
              </li>
            </ul>

            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="nav-icon fas fa-heart"></i>
                  <p>Account Health</p>
                </a>
              </li>
            </ul>
          </li>

          <li class="nav-item has-treeview">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-box"></i>
              <p>
                ADMIN
                <i class="fas fa-angle-left right"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
              <a href="{{route('admin.role.permission.load')}}" class="nav-link">
                  <i class="nav-icon fas fa-list"></i>
                  <p>Role Permission</p>
                </a>
              </li>
            </ul>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="" class="nav-link">
                  <i class="nav-icon fas fa-boxes"></i>
                  <p>XX </p>
                </a>
              </li>
            </ul>
          </li>


          <li class="nav-item has-treeview">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa fa-cog"></i>
              <p>
                SYSTEM
                <i class="fas fa-angle-left right"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="{{ route('logout')}}"
                  onclick="event.preventDefault();
                  document.getElementById('logout-form').submit();">
                  <i class="nav-icon fas fa sign-out-alt"></i>
                  {{ __('Logout') }}
                  <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                  </form>
                </a>
              </li>
            </ul>
          </li>


        </ul>
      </nav>
      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
  </aside>