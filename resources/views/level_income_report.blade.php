@include('includes.headcss')
@include('includes.header')
@include('includes.sideNavigation')

<div id="page-wrapper">
    <div class="container-fluid">
        <div class="row bg-title">
            <div class="col-lg-3 col-md-4 col-sm-4 col-xs-12">
                <h4 class="page-title">Users Report</h4>
            </div>
        </div>
        
        <div class="row">
            <div class="white-box">
                <div class="panel-body">
                    <div>
                        <!-- Nav tabs -->
                        <ul class="nav nav-tabs member-tab" role="tablist">
                            <li role="presentation" class="active"><a href="#all_members" aria-controls="all_members"
                                    role="tab" data-toggle="tab">Users Report</a></li>
                        </ul>

                        <!-- Tab panes -->
                        <div class="tab-content">
                            <div role="tabpanel" class="tab-pane active" id="all_members">
                                <div class="search-form export-form">
                                    <form action="{{route('level_income_report')}}" method="post" class="mb-0">
                                        @csrf
                                        <div class="form-row">
                                            <div class="form-group">
                                                <input id="refferal_code" name="refferal_code" @if(isset($data['refferal_code'])) value="{{$data['refferal_code']}}" @endif type="text" class="form-control" placeholder="Enter Wallet Address">
                                            </div>
                                            <div class="form-group">
                                                <select name="tag" class="form-control">
                                                    <option value="">Select Type</option>
                                                    <option value="DAILY-POOL" @if(isset($data['tag'])) @if($data['tag'] == 'DAILY-POOL') selected @endif @endif>DAILY POOL</option>
                                                    <option value="MONTHLY-POOL" @if(isset($data['tag'])) @if($data['tag'] == 'MONTHLY-POOL') selected @endif @endif>MONTHLY POOL</option>
                                                    <option value="REWARD-BONUS" @if(isset($data['tag'])) @if($data['tag'] == 'REWARD-BONUS') selected @endif @endif>REWARD Bonus</option>
                                                    <option value="ROI" @if(isset($data['tag'])) @if($data['tag'] == 'ROI') selected @endif @endif>ROI</option>
                                                    <option value="STAR-BONUS" @if(isset($data['tag'])) @if($data['tag'] == 'STAR-BONUS') selected @endif @endif>Star Bonus</option>
                                                    <option value="Level" @if(isset($data['tag'])) @if($data['tag'] == 'Level') selected @endif @endif>Level</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <select name="rank" class="form-control">
                                                    <option value="">Select Rank</option>
                                                    <option value="1" @if(isset($data['rank'])) @if($data['rank'] == '1') selected @endif @endif>Rank 1</option>
                                                    <option value="2" @if(isset($data['rank'])) @if($data['rank'] == '2') selected @endif @endif>Rank 2</option>
                                                    <option value="3" @if(isset($data['rank'])) @if($data['rank'] == '3') selected @endif @endif>Rank 3</option>
                                                    <option value="4" @if(isset($data['rank'])) @if($data['rank'] == '4') selected @endif @endif>Rank 4</option>
                                                    <option value="5" @if(isset($data['rank'])) @if($data['rank'] == '5') selected @endif @endif>Rank 5</option>
                                                    <option value="6" @if(isset($data['rank'])) @if($data['rank'] == '6') selected @endif @endif>Rank 6</option>
                                                    <option value="7" @if(isset($data['rank'])) @if($data['rank'] == '7') selected @endif @endif>Rank 7</option>
                                                    <option value="8" @if(isset($data['rank'])) @if($data['rank'] == '8') selected @endif @endif>Rank 8</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <input id="startDate" name="startDate" @if(isset($data['startDate'])) value="{{$data['startDate']}}" @endif type="date" class="form-control start-date" placeholder="From Date" autocomplete="off">
                                            </div>
                                            <div class="form-group">
                                                <input id="endDate" name="endDate" @if(isset($data['endDate'])) value="{{$data['endDate']}}" @endif type="date" class="form-control end-date" placeholder="To Date" autocomplete="off">
                                            </div>
                                            <div class="form-group">
                                                <button type="submit" class="btn waves-effect waves-light btn-success">Search</button>
                                            </div>
                                        </div>
                                        <!-- @if(isset($data['data']))
                                            <div class="export-section">
                                                <a href="https://finlyai.com/exports/userdataExport.csv" download="download"><button type="button" class="btn waves-effect waves-light btn-info">Export excel</button></a>
                                            </div>
                                        @endif -->
                                    </form>
                                </div>
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
                                                                <th scope="col" class="border-0 fs-4 font-weight-medium">Sr</th>
                                                                <!-- <th scope="col" class="border-0 fs-4 font-weight-medium">Member Code</th> -->
                                                                <th scope="col" class="border-0 fs-4 font-weight-medium">Wallet Address</th>
                                                                <th scope="col" class="border-0 fs-4 font-weight-medium">Tag</th>
                                                                <th scope="col" class="border-0 fs-4 font-weight-medium">Rank</th>
                                                                <th scope="col" class="border-0 fs-4 font-weight-medium">Amount</th>
                                                                <!-- <th scope="col" class="border-0 fs-4 font-weight-medium">Total</th> -->
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
                                                            @if(isset($data['data']['data']))
                                                            @foreach($data['data']['data'] as $key => $value)
                                                                <tr>
                                                                    <td><h5 class="font-weight-medium mb-1">{{ ($data['data']['current_page'] - 1) * 20 + $key + 1 }}</h5></td>
                                                                    <!-- <td><span class="badge badge-inverse fs-4">{{$value['refferal_code']}}</span></td> -->
                                                                    <td><h5 class="font-weight-medium mb-1">{{ substr($value['wallet_address'], 0, 6) . '...' . substr($value['wallet_address'], -6) }}</h5></td>
                                                                    <td><h5 class="font-weight-medium mb-1">{{$value['tag']}}</h5></td>
                                                                    <td>
                                                                        @if($value['tag']=='REWARD-BONUS')
                                                                            <h5 class="font-weight-medium mb-1">{{$value['refrence_id']}}</h5>
                                                                        @else
                                                                            <h5 class="font-weight-medium mb-1">-</h5>
                                                                        @endif
                                                                    </td>
                                                                    <!-- <td><span>{{$value['amount']}}</span></td> -->
                                                                    <td><span>{{number_format($value['amount'],4)}}</span></td>
                                                                    <!-- <td><span>{{$data['amt']}}</span></td> -->
                                                                    <td><span>{{ date('d-m-Y H:i', strtotime($value['created_on'])) }}</span></td>
                                                                </tr>
                                                            @endforeach
                                                            @endif
                                                        </tbody>
                                                    </table>
                                                    <br>
                                                    <hr>
                                                    <h3>Total : {{$data['amt']}}</h3>
                                                    <div class="col-sm-12 col-md-7">
                                                        <div class="dataTables_paginate paging_simple_numbers" id="example_paginate">
                                                            <ul class="pagination">
                                                            @if(isset($data['data']['data']))
                                                                @foreach($data['data']['links'] as $key => $value)
                                                                    @if($value['url'] != null)
                                                                        <li class="paginate_button page-item @if($value['active'] == "true") active @endif"><a href="{{$value['url']}}&refferal_code={{$data['refferal_code']}}&tag={{$data['tag']}}&endDate={{$data['endDate']}}&startDate={{$data['startDate']}}&rank={{$data['rank']}}" aria-controls="example" data-dt-idx="1" tabindex="0" class="page-link"><?php echo $value['label']; ?></a></li>
                                                                    @endif
                                                                @endforeach
                                                            @endif
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
    @include('includes.footer')