@include('includes.headcss')
@include('includes.header')
@include('includes.sideNavigation')

<div id="page-wrapper">
    <div class="container-fluid">
        <div class="row bg-title">
            <div class="col-lg-3 col-md-4 col-sm-4 col-xs-12">
                <h4 class="page-title">Payout Report</h4>
            </div>
        </div>
        
        <div class="row">
            <div class="white-box">
                <div class="panel-body">
                    <div>
                        <!-- Nav tabs -->
                        <ul class="nav nav-tabs member-tab" role="tablist">
                            <li role="presentation" class="active"><a href="#all_members" aria-controls="all_members"
                                    role="tab" data-toggle="tab">Payout Report</a></li>
                        </ul>

                        <!-- Tab panes -->
                        <div class="tab-content">
                            <div role="tabpanel" class="tab-pane active" id="all_members">
                                <div class="d-flex flex-wrap search-form export-form">
                                    <form action="{{route('incomeOverviewFilter')}}" method="post" class="mb-0 col-xs-12 col-md-8">
                                        @csrf
                                        <div class="form-row">
                                            <div class="form-group">
                                                <input id="startDate" name="start_date" @if(isset($data['start_date'])) value="{{$data['start_date']}}" @endif type="text" class="form-control start-date" placeholder="From Date" autocomplete="off">
                                            </div>
                                            <div class="form-group">
                                                <input id="endDate" name="end_date" @if(isset($data['end_date'])) value="{{$data['end_date']}}" @endif type="text" class="form-control end-date" placeholder="To Date" autocomplete="off">
                                            </div>
                                            <div class="form-group">
                                                <input id="refferal_code" name="refferal_code" @if(isset($data['refferal_code'])) value="{{$data['refferal_code']}}" @endif type="text" class="form-control" placeholder="User Id" autocomplete="off">
                                            </div>
                                            <div class="form-group">
                                                <button type="submit" class="btn waves-effect waves-light btn-success">Search</button>
                                            </div>
                                        </div>
                                    </form>

                                        @if(isset($data['data']))
                                            <div class="export-section col-xs-12 col-md-4">
                                                <form action="{{route('incomeOverviewFilterExcel')}}" method="post" class="mb-0">
                                                        <input id="startDate" name="start_date" @if(isset($data['start_date'])) value="{{$data['start_date']}}" @endif type="hidden" class="form-control start-date" placeholder="From Date" autocomplete="off">
                                                    
                                                    
                                                        <input id="endDate" name="end_date" @if(isset($data['end_date'])) value="{{$data['end_date']}}" @endif type="hidden" class="form-control end-date" placeholder="To Date" autocomplete="off">
                                                    
                                                    
                                                        <input id="refferal_code" name="refferal_code" @if(isset($data['refferal_code'])) value="{{$data['refferal_code']}}" @endif type="hidden" class="form-control" placeholder="User Id" autocomplete="off">
                                                        <button type="submit" class="btn waves-effect waves-light btn-info">Export excel</button>
                                                </form>
                                            </div>
                                        @endif
                                </div>

                                @if(isset($data['data']))
                                    @php
                                        $abt = 0;
                                        $pbt = 0;
                                        $rbt = 0;
                                        $rpst = 0;
                                        $bbt = 0;
                                        $rpt = 0;
                                        $ibt = 0;
                                        $bit = 0;
                                        $ait = 0;
                                    @endphp
                                    @foreach($data['data'] as $key => $value)
                                        @php
                                        $abt = $abt + $value['referral_bonus'];
                                        $pbt = $pbt + $value['profit_sharing'];
                                        $rbt = $rbt + $value['rank_bonus'];
                                        $rpst = $rpst + $value['profit_sharing_level'];
                                        $bbt = $bbt + $value['brokerage'];
                                        $rpt = $bbt + $value['royalty_pool'];
                                        $ibt = $ibt + $value['ib_income'];
                                        $bit = $bit + $value['booster_income'];
                                        $ait = $ait + $value['award_income'];
                                        @endphp
                                    @endforeach
                                    <div class="m-t-30">
                                        <div class="card-group">
                                            
                                            <div class="card p-2 p-lg-3">
                                                <div class="p-lg-3 p-2">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <div class="d-flex align-items-center">
                                                            <button class="btn btn-circle btn-info text-white btn-lg" href="javascript:void(0)">
                                                                <i class="fas fa-dollar-sign"></i>
                                                            </button>
                                                            <h4 class="h5 fw-normal m-0 ms-4">Sponsor Income</h4>
                                                        </div>
                                                        <div class="ms-auto">
                                                            <h2 class="display-7 mb-0">${{number_format($abt, 2)}}</h2>
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
                                                            <div class="h5 fw-normal m-0 ms-4">Profit Sharing</div>
                                                        </div>
                                                        <div class="ms-auto">
                                                            <h2 class="display-7 mb-0">${{number_format($pbt, 2)}}</h2>
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
                                                            <h4 class="h5 fw-normal m-0 ms-4">Reward Income</h4>
                                                        </div>
                                                        <div class="ms-auto">
                                                            <h2 class="display-7 mb-0">${{number_format($rbt, 2)}}</h2>
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
                                                            <div class="h5 fw-normal m-0 ms-4">Level Income</div>
                                                        </div>
                                                        <div class="ms-auto">
                                                            <h2 class="display-7 mb-0">${{number_format($rpst, 2)}}</h2>
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
                                                            <h4 class="h5 fw-normal m-0 ms-4">Ib Income</h4>
                                                        </div>
                                                        <div class="ms-auto">
                                                            <h2 class="display-7 mb-0">${{number_format($ibt, 2)}}</h2>
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
                                                            <h4 class="h5 fw-normal m-0 ms-4">Booster Income</h4>
                                                        </div>
                                                        <div class="ms-auto">
                                                            <h2 class="display-7 mb-0">${{number_format($bit, 2)}}</h2>
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
                                                            <h4 class="h5 fw-normal m-0 ms-4">Award Income</h4>
                                                        </div>
                                                        <div class="ms-auto">
                                                            <h2 class="display-7 mb-0">${{number_format($ait, 2)}}</h2>
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
                                                               <!--  <th scope="col" class="border-0 fs-4 font-weight-medium ps-4">
                                                                    <div class="form-check border-start border-2 border-info ps-1">
                                                                        <input type="checkbox" class="form-check-input ms-2" id="inputSchedule" name="inputCheckboxesSchedule">
                                                                        <label for="inputSchedule" class="form-check-label ps-2 fw-normal"></label>
                                                                    </div>
                                                                </th> -->
                                                                <th scope="col" class="border-0 fs-4 font-weight-medium">Member Name</th>
                                                                <th scope="col" class="border-0 fs-4 font-weight-medium">Member Code</th>
                                                                <th scope="col" class="border-0 fs-4 font-weight-medium">Sponsor Code</th>
                                                                <th scope="col" class="border-0 fs-4 font-weight-medium">Sponsor Income</th>
                                                                <th scope="col" class="border-0 fs-4 font-weight-medium">Profit Sharing</th>
                                                                <th scope="col" class="border-0 fs-4 font-weight-medium">Reward Income</th>
                                                                <th scope="col" class="border-0 fs-4 font-weight-medium">Level Income</th>
                                                                <th scope="col" class="border-0 fs-4 font-weight-medium">Ib Income</th>
                                                                <th scope="col" class="border-0 fs-4 font-weight-medium">Booster Income</th>
                                                                <th scope="col" class="border-0 fs-4 font-weight-medium">Award Income</th>
                                                                <th scope="col" class="border-0 fs-4 font-weight-medium">Date</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                                <!-- <td class="ps-4">
                                                                    <div class="form-check border-start border-2 border-info ps-1">
                                                                        <input type="checkbox" class="form-check-input ms-2" id="inputSchedule1" name="inputCheckboxesSchedule">
                                                                        <label for="inputSchedule1" class="form-check-label ps-2 fw-normal"></label>
                                                                    </div>
                                                                </td> -->
                                                            @foreach($data['data'] as $key => $value)
                                                                <tr>
                                                                    <td>
                                                                        <h5 class="font-weight-medium mb-1">{{$value['name']}}</h5>
                                                                        <!-- <a href="javascript:void(0);" class="font-14 text-muted"></a> -->
                                                                    </td>
                                                                    <td><span class="badge badge-inverse fs-4">{{$value['refferal_code']}}</span></td>
                                                                    <td><span class="badge badge-inverse fs-4">{{$value['sponser_code']}}</span></td>
                                                                    <td><span>${{number_format($value['referral_bonus'], 2)}}</span></td>
                                                                    <td><span>${{number_format($value['profit_sharing'], 2)}}</span></td>
                                                                    <td><span>${{number_format($value['rank_bonus'], 2)}}</span></td>
                                                                    <td><span>${{number_format($value['profit_sharing_level'], 2)}}</span></td>
                                                                    <td><span>${{number_format($value['ib_income'], 2)}}</span></td>
                                                                    <td><span>${{number_format($value['booster_income'], 2)}}</span></td>
                                                                    <td><span>${{number_format($value['award_income'], 2)}}</span></td>
                                                                    <td><span>{{date('d-m-Y', strtotime($value['created_on']))}}</span></td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
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