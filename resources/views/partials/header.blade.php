
<style>
    /* ==== SIDENAV (LEFT MENU) ==== */
    .sidenav {
        height: 100%;
        width: 220px;
        position: fixed;
        top: 0;
        left: 0;
        background-color: #343a40;
        padding-top: 60px;
        z-index: 1000;
        transition: all 0.3s ease;
    }

    .sidenav a {
        padding: 12px 20px;
        text-decoration: none;
        font-size: 16px;
        color: #f8f9fa;
        display: block;
        transition: 0.2s;
    }

    .sidenav a:hover {
        background-color: #495057;
        color: #fff;
    }

    /* Hide the close button since menu is fixed */
    .sidenav .closebtn {
        display: none;
    }

    /* Main content pushed right */
    .main-content {
        margin-left: 240px;
        padding: 20px;
    }

    /* ==== HEADER ==== */
    .header_section {
        background-color: #f8f9fa;
        padding: 10px 15px;
        position: fixed;
        top: 0;
        left: 240px;
        right: 0;
        z-index: 999;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #ddd;
    }

    .header_section .logo img {
        height: 40px;
    }

    .cart-count {
        background-color: red;
        color: white;
        font-size: 12px;
        font-weight: bold;
        padding: 2px 6px;
        border-radius: 50%;
        position: absolute;
        top: 33px;
        right: 42px;
        min-width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* ==== TABLE CARD STYLING ==== */
    .table-card {
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        text-align: center;
        margin-bottom: 15px;
        transition: all 0.3s ease;
    }

    .table-card.occupied {
        background-color: #f8f0ff;
        color: #666;
    }

    .table-card.available {
        background-color: #ffffff;
        color: #343a40;
    }

    .table-card h5 {
        margin-bottom: 5px;
        font-weight: bold;
    }

    .add-table-btn {
        position: fixed;
        right: 20px;
        bottom: 20px;
        border-radius: 50px;
        padding: 12px 25px;
        font-size: 16px;
    }

    body {
        background-color: #f5f5f5;
    }

    .navbar {
        display: none !important;
    }
</style>

<?php $table_id = !empty($table_id) ? $table_id : ""; ?>

<!-- FIXED LEFT MENU -->
<div class="sidenav">
    <a href="{{url('/dashboard')}}">🪑 Tables</a>
    <a href="{{url('/restaurants/1/1')}}">🍕 Restaurant</a>
    
    <a href="{{url('/inventory/1/1')}}">🧾 Counter</a>
    <a href="{{url('/stocks/0/2')}}">🍾 Liquor Godaun</a>
    <a href="{{url('/sale_report')}}">📈 Sale Report</a>
    <a href="{{url('/stock_report')}}">📈 Stock Report</a>
    <!-- <a href="{{url('/stock_report')}}">Stock Report</a> -->
</div>

<!-- FIXED HEADER -->
<div class="header_section">
    <a class="logo" href="{{url('/dashboard')}}">
       <h1>Koltegroup</h1>
        <!-- <img src="{{url('asset/images/logo.png')}}" alt="Logo"> -->
    </a>
    <div class="login_text">
        <ul style="list-style: none; display: flex; gap: 15px; margin: 0; padding: 0;">
            <li><a href="#"><img src="{{url('asset/images/user-icon.png')}}"></a></li>
            <li>
                <a href="{{ url('/logout') }}" style="color: #000;">
                    <strong>Logout</strong>
                </a>
            </li>
        </ul>
    </div>
</div>
