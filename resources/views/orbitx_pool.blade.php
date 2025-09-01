@include('includes.headcss')
@include('includes.header')
@include('includes.sideNavigation')

<div id="page-wrapper">
    <div class="container-fluid">
        <div class="row bg-title">
            <div class="col-lg-3 col-md-4 col-sm-4 col-xs-12">
                <h4 class="page-title">Orbitx Pool Report</h4>
            </div>
        </div>
        
        <div class="row">
            <div class="white-box">
                <div class="panel-body">
                    <div>
                        <ul class="nav nav-tabs member-tab" role="tablist">
                            <li role="presentation" class="active"><a href="#all_members" aria-controls="all_members"
                                    role="tab" data-toggle="tab">Orbitx Pool Report</a></li>
                        </ul>

                        <div class="tab-content">
                            <div role="tabpanel" class="tab-pane active" id="all_members">
                                <div class="d-flex flex-wrap search-form export-form">
                                    <form action="{{route('orbitx_pool_report')}}" method="post" class="mb-0 col-xs-12 col-md-8">
                                        @csrf
                                        <div class="form-row">
                                            <div class="form-group">
                                                <input id="startDate" name="start_date" @if(isset($data['start_date'])) value="{{$data['start_date']}}" @endif type="text" class="form-control start-date" placeholder="From Date" autocomplete="off">
                                            </div>
                                            <div class="form-group">
                                                <input id="endDate" name="end_date" @if(isset($data['end_date'])) value="{{$data['end_date']}}" @endif type="text" class="form-control end-date" placeholder="To Date" autocomplete="off">
                                            </div>
                                            <div class="form-group">
                                                <input id="hash" name="hash" @if(isset($data['hash'])) value="{{$data['hash']}}" @endif type="text" class="form-control" placeholder="Transaction Hash Or Wallet Address" autocomplete="off">
                                            </div>
                                            <div class="form-group">
                                                <select name="type" class="form-control">
                                                    <option value="">Select Type</option>
                                                    <option value="FOUNDER" @if(isset($data['type'])) @if($data['type'] == 'FOUNDER') selected @endif @endif>FOUNDER</option>
                                                    <option value="GIC" @if(isset($data['type'])) @if($data['type'] == 'GIC') selected @endif @endif>GIC</option>
                                                    <option value="LIC" @if(isset($data['type'])) @if($data['type'] == 'LIC') selected @endif @endif>LIC</option>
                                                    <option value="MARKETING" @if(isset($data['type'])) @if($data['type'] == 'MARKETING') selected @endif @endif>MARKETING</option>
                                                    <option value="PROMOTER" @if(isset($data['type'])) @if($data['type'] == 'PROMOTER') selected @endif @endif>PROMOTER</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <button type="submit" class="btn waves-effect waves-light btn-success">Search</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                @if(isset($data['data']))
                                    <div class="m-t-30">
                                        <div class="card-group">
                                

                                            @foreach($data['total_pool_amount'] as $key => $value)
                                                <div class="card p-2 p-lg-3">
                                                    <div class="p-lg-3 p-2">
                                                        <div class="d-flex align-items-center justify-content-between">
                                                            <div class="d-flex align-items-center">
                                                                <button class="btn btn-circle btn-info text-white btn-lg" href="javascript:void(0)">
                                                                    <i class="fas fa-dollar-sign"></i>
                                                                </button>
                                                                <h4 class="h5 fw-normal m-0 ms-4">Total {{$value->pool}} Pool Amount</h4>
                                                            </div>
                                                            <div class="ms-auto">
                                                                <h2 class="display-7 mb-0">{{number_format($value->total_amount, 2)}} RTX</h2>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                        <br>
                                        <br>
                                        <div class="card-group">
                                            <br>                                           
                                            <div class="card p-2 p-lg-3">
                                                <div class="p-lg-3 p-2">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <div class="d-flex align-items-center">
                                                            <button class="btn btn-circle btn-info text-white btn-lg" href="javascript:void(0)">
                                                                <i class="fas fa-dollar-sign"></i>
                                                            </button>
                                                            <h4 class="h5 fw-normal m-0 ms-4">Today's Founder Pool</h4>
                                                        </div>
                                                        <div class="ms-auto">
                                                            <h2 class="display-7 mb-0">{{number_format($data['founder_pool_amt'], 2)}} RTX</h2>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card p-2 p-lg-3">
                                                <div class="p-lg-3 p-2">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <div class="d-flex align-items-center">
                                                            <button class="btn btn-circle btn-warning text-white btn-lg" href="javascript:void(0)">
                                                                <i class="fas fa-dollar-sign"></i>
                                                            </button>
                                                            <div class="h5 fw-normal m-0 ms-4">Today's GLC Pool</div>
                                                        </div>
                                                        <div class="ms-auto">
                                                            <h2 class="display-7 mb-0">{{number_format($data['gic_pool_amt'], 2)}} RTX</h2>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="card p-2 p-lg-3">
                                                <div class="p-lg-3 p-2">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <div class="d-flex align-items-center">
                                                            <button class="btn btn-circle btn-primary text-white btn-lg" href="javascript:void(0)">
                                                                <i class="fas fa-dollar-sign"></i>
                                                            </button>
                                                            <h4 class="h5 fw-normal m-0 ms-4">Today's LIC Pool</h4>
                                                        </div>
                                                        <div class="ms-auto">
                                                            <h2 class="display-7 mb-0">{{number_format($data['lic_pool_amt'], 2)}} RTX</h2>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card p-2 p-lg-3">
                                                <div class="p-lg-3 p-2">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <div class="d-flex align-items-center">
                                                            <button class="btn btn-circle btn-success text-white btn-lg" href="javascript:void(0)">
                                                                <i class="fas fa-dollar-sign"></i>
                                                            </button>
                                                            <h4 class="h5 fw-normal m-0 ms-4">Today's Marketting Pool</h4>
                                                        </div>
                                                        <div class="ms-auto">
                                                            <h2 class="display-7 mb-0">{{number_format($data['marketting_pool_amt'], 2)}} RTX</h2>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card p-2 p-lg-3">
                                                <div class="p-lg-3 p-2">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <div class="d-flex align-items-center">
                                                            <button class="btn btn-circle btn-danger text-white btn-lg" href="javascript:void(0)">
                                                                <i class="fas fa-dollar-sign"></i>
                                                            </button>
                                                            <h4 class="h5 fw-normal m-0 ms-4">Today's Promoter Pool</h4>
                                                        </div>
                                                        <div class="ms-auto">
                                                            <h2 class="display-7 mb-0">${{number_format($data['promoter_pool_amt'], 2)}}</h2>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                @if(isset($data['data']))
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="card">
                                                <h5 class="card-title mb-4">All Members</h5>
                                                <div class="table-responsive">
                                                    <table class="table no-wrap user-table mb-0">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th scope="col" class="border-0 fs-4 font-weight-medium">Wallet Address</th>
                                                                <th scope="col" class="border-0 fs-4 font-weight-medium">Amount</th>
                                                                <th scope="col" class="border-0 fs-4 font-weight-medium">Transaction Hash</th>
                                                                <th scope="col" class="border-0 fs-4 font-weight-medium">Pool</th>
                                                                <th scope="col" class="border-0 fs-4 font-weight-medium">Date</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($data['data']['data'] as $key => $value)
                                                                <tr>
                                                                    <td><h5 class="font-weight-medium mb-1">{{ substr($value->wallet_address, 0, 6) . '...' . substr($value->wallet_address, -6) }}</h5></td>
                                                                    <td><span class="badge badge-inverse fs-4">{{$value->amount}}</span></td>
                                                                    <td><h5 class="font-weight-medium mb-1">{{ substr($value->transaction_hash, 0, 6) . '...' . substr($value->transaction_hash, -6) }}</h5></td>
                                                                    <td><span class="badge badge-inverse fs-4">{{$value->pool}}</span></td>
                                                                    <td><span>{{date('d-m-Y', strtotime($value->created_on))}}</span></td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>

                                                    <div class="col-sm-12 col-md-7">
                                                        <div class="dataTables_paginate paging_simple_numbers" id="example_paginate">
                                                            <ul class="pagination">
                                                                @foreach($data['data']['links'] as $key => $value)
                                                                    @if($value['url'] != null)
                                                                        <li class="paginate_button page-item @if($value['active'] == "true") active @endif"><a href="{{$value['url']}}&start_date={{$data['start_date']}}&end_date={{$data['end_date']}}&type={{$data['type']}}&hash={{$data['hash']}}" aria-controls="example" data-dt-idx="1" tabindex="0" class="page-link"><?php echo $value['label']; ?></a></li>
                                                                    @endif
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('includes.footerJs')
    <script>
        $(document).ready(function () {
            $('#example').DataTable();
        });
    </script>
    <script>
        $(function() {
            $('.start-date').datepicker({ dateFormat: 'dd-mm-yy' });
            $('.end-date').datepicker({ dateFormat: 'dd-mm-yy' });
        });
    </script>
    @include('includes.footer')