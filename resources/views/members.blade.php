@include('includes.headcss')
@include('includes.header')
@include('includes.sideNavigation')

<div id="page-wrapper">
    <div class="container-fluid">
        <div class="row bg-title">
            <div class="col-lg-3 col-md-4 col-sm-4 col-xs-12">
                <h4 class="page-title">Members</h4>
            </div>
        </div>
        <div class="card-group">
            <div class="card p-2 p-lg-3">
                <div class="p-lg-3 p-2">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center justify-content-between">
                            <button class="btn btn-circle btn-danger text-white btn-lg" href="javascript:void(0)">
                                <i class="ti-clipboard"></i>
                            </button>
                            <div class="h5 fw-normal m-0 ms-4">Total Joining</div>
                        </div>
                        <div class="ms-auto">
                            <h2 class="display-7 mb-0">23</h2>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card p-2 p-lg-3">
                <div class="p-lg-3 p-2">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <button class="btn btn-circle btn-warning text-white btn-lg" href="javascript:void(0)">
                                <i class="ti-wallet"></i>
                            </button>
                            <div class="h5 fw-normal m-0 ms-4">Today Joinning</div>
                        </div>
                        <div class="ms-auto">
                            <h2 class="display-7 mb-0">76</h2>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card p-2 p-lg-3">
                <div class="p-lg-3 p-2">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <button class="btn btn-circle btn-info text-white btn-lg" href="javascript:void(0)">
                                <i class="fas fa-dollar-sign"></i>
                            </button>
                            <div class="h5 fw-normal m-0 ms-4">Total Blocked</div>
                        </div>
                        <div class="ms-auto">
                            <h2 class="display-7 mb-0">83</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="white-box">
                <div class="panel-body">
                    <div>
                        <!-- Nav tabs -->
                        <ul class="nav nav-tabs member-tab" role="tablist">
                            <li role="presentation" class="active"><a href="#all_members" aria-controls="all_members"
                                    role="tab" data-toggle="tab">All Members</a></li>
                            <li role="presentation"><a href="#blocked_members" aria-controls="blocked_members"
                                    role="tab" data-toggle="tab">Blocked Members</a></li>
                        </ul>

                        <!-- Tab panes -->
                        <div class="tab-content">
                            <div role="tabpanel" class="tab-pane active" id="all_members">
                                <div class="search-form">
                                    <form action="" class="mb-0">
                                        <div class="form-row">
                                            <div class="form-group">
                                                <input type="text" class="form-control"
                                                    placeholder="Username, Name, E-mail" name="keyword" id="keyword"
                                                    autocomplete="off" value="">
                                            </div>
                                            <div class="form-group">
                                                <select name="status" id="status" class="form-control">
                                                    <option value="yes" selected="">Block Now</option>
                                                    <option value="no">Unblock Now</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <input type="text" class="form-control" id="date-range"
                                                    placeholder="Default Pickadate" />
                                            </div>
                                            <div class="form-group">
                                                <button type="submit"
                                                    class="btn waves-effect waves-light btn-success">Search</button>
                                            </div>
                                            <div class="form-group">
                                                <input type="reset" class="btn waves-effect waves-light btn-warning p-0"
                                                    value="Reset" />
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="card">
                                            <h5 class="card-title mb-4">All Members</h5>
                                            <div class="table-responsive">
                                                <table class="table no-wrap user-table mb-0">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th scope="col" class="border-0 fs-4 font-weight-medium ps-4">
                                                                <div class="form-check border-start border-2 border-info ps-1">
                                                                    <input type="checkbox" class="form-check-input ms-2" id="inputSchedule" name="inputCheckboxesSchedule">
                                                                    <label for="inputSchedule" class="form-check-label ps-2 fw-normal"></label>
                                                                </div>
                                                            </th>
                                                            <th scope="col" class="border-0 fs-4 font-weight-medium">
                                                                Member Name</th>
                                                            <th scope="col" class="border-0 fs-4 font-weight-medium">
                                                                Sponsor Code</th>
                                                            <th scope="col" class="border-0 fs-4 font-weight-medium">
                                                                Email</th>
                                                            <th scope="col" class="border-0 fs-4 font-weight-medium">
                                                            Joining Date</th>
                                                            <th scope="col" class="border-0 fs-4 font-weight-medium">
                                                            Activation Date</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td class="ps-4">
                                                                <div class="form-check border-start border-2 border-info ps-1">
                                                                    <input type="checkbox" class="form-check-input ms-2" id="inputSchedule1" name="inputCheckboxesSchedule">
                                                                    <label for="inputSchedule1" class="form-check-label ps-2 fw-normal"></label>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <h5 class="font-weight-medium mb-1">Daniel Kristeen</h5>
                                                                <a href="javascript:void(0);" class="font-14 text-muted">DEMO2AV</a>
                                                            </td>
                                                            <td>
                                                                <span class="badge badge-inverse fs-4">1234s56d4sdfdf</span>
                                                            </td>
                                                            <td>
                                                                <span>aanielkristeen@gmail.com</span>
                                                            </td>
                                                            <td>
                                                                <span>12/05/2022</span>
                                                            </td>
                                                            <td><span>12/05/2022</span></td>
                                                        </tr>
                                                        <tr>
                                                            <td class="ps-4">
                                                                <div class="form-check border-start border-2 border-info ps-1">
                                                                    <input type="checkbox" class="form-check-input ms-2" id="inputSchedule2" name="inputCheckboxesSchedule">
                                                                    <label for="inputSchedule2" class="form-check-label ps-2 fw-normal"></label>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <h5 class="font-weight-medium mb-1">Daniel Kristeen</h5>
                                                                <a href="javascript:void(0);" class="font-14 text-muted">DEMO2AV</a>
                                                            </td>
                                                            <td>
                                                                <span class="badge badge-inverse fs-4">1234s56d4sdfdf</span>
                                                            </td>
                                                            <td>
                                                                <span>aanielkristeen@gmail.com</span>
                                                            </td>
                                                            <td>
                                                                <span>12/05/2022</span>
                                                            </td>
                                                            <td><span>12/05/2022</span></td>
                                                        </tr>
                                                        <tr>
                                                            <td class="ps-4">
                                                                <div class="form-check border-start border-2 border-info ps-1">
                                                                    <input type="checkbox" class="form-check-input ms-2" id="inputSchedule3" name="inputCheckboxesSchedule">
                                                                    <label for="inputSchedule3" class="form-check-label ps-2 fw-normal"></label>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <h5 class="font-weight-medium mb-1">Daniel Kristeen</h5>
                                                                <a href="javascript:void(0);" class="font-14 text-muted">DEMO2AV</a>
                                                            </td>
                                                            <td>
                                                                <span class="badge badge-inverse fs-4">1234s56d4sdfdf</span>
                                                            </td>
                                                            <td>
                                                                <span>aanielkristeen@gmail.com</span>
                                                            </td>
                                                            <td>
                                                                <span>12/05/2022</span>
                                                            </td>
                                                            <td><span>12/05/2022</span></td>
                                                        </tr>
                                                        <tr>
                                                            <td class="ps-4">
                                                                <div class="form-check border-start border-2 border-info ps-1">
                                                                    <input type="checkbox" class="form-check-input ms-2" id="inputSchedule4" name="inputCheckboxesSchedule">
                                                                    <label for="inputSchedule4" class="form-check-label ps-2 fw-normal"></label>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <h5 class="font-weight-medium mb-1">Daniel Kristeen</h5>
                                                                <a href="javascript:void(0);" class="font-14 text-muted">DEMO2AV</a>
                                                            </td>
                                                            <td>
                                                                <span class="badge badge-inverse fs-4">1234s56d4sdfdf</span>
                                                            </td>
                                                            <td>
                                                                <span>aanielkristeen@gmail.com</span>
                                                            </td>
                                                            <td>
                                                                <span>12/05/2022</span>
                                                            </td>
                                                            <td><span>12/05/2022</span></td>
                                                        </tr>
                                                        <tr>
                                                            <td class="ps-4">
                                                                <div class="form-check border-start border-2 border-info ps-1">
                                                                    <input type="checkbox" class="form-check-input ms-2" id="inputSchedule5" name="inputCheckboxesSchedule">
                                                                    <label for="inputSchedule5" class="form-check-label ps-2 fw-normal"></label>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <h5 class="font-weight-medium mb-1">Daniel Kristeen</h5>
                                                                <a href="javascript:void(0);" class="font-14 text-muted">DEMO2AV</a>
                                                            </td>
                                                            <td>
                                                                <span class="badge badge-inverse fs-4">1234s56d4sdfdf</span>
                                                            </td>
                                                            <td>
                                                                <span>aanielkristeen@gmail.com</span>
                                                            </td>
                                                            <td>
                                                                <span>12/05/2022</span>
                                                            </td>
                                                            <td><span>12/05/2022</span></td>
                                                        </tr>
                                                        <tr>
                                                            <td class="ps-4">
                                                                <div class="form-check border-start border-2 border-info ps-1">
                                                                    <input type="checkbox" class="form-check-input ms-2" id="inputSchedule6" name="inputCheckboxesSchedule">
                                                                    <label for="inputSchedule6" class="form-check-label ps-2 fw-normal"></label>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <h5 class="font-weight-medium mb-1">Daniel Kristeen</h5>
                                                                <a href="javascript:void(0);" class="font-14 text-muted">DEMO2AV</a>
                                                            </td>
                                                            <td>
                                                                <span class="badge badge-inverse fs-4">1234s56d4sdfdf</span>
                                                            </td>
                                                            <td>
                                                                <span>aanielkristeen@gmail.com</span>
                                                            </td>
                                                            <td>
                                                                <span>12/05/2022</span>
                                                            </td>
                                                            <td><span>12/05/2022</span></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div role="tabpanel" class="tab-pane" id="blocked_members">
                            <div class="search-form">
                                    <form action="" class="mb-0">
                                        <div class="form-row">
                                            <div class="form-group">
                                                <input type="text" class="form-control"
                                                    placeholder="Username, Name, E-mail" name="keyword" id="keyword"
                                                    autocomplete="off" value="">
                                            </div>
                                            <div class="form-group">
                                                <select name="status" id="status" class="form-control">
                                                    <option value="yes" selected="">Block Now</option>
                                                    <option value="no">Unblock Now</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <input type="text" class="form-control" id="date-range"
                                                    placeholder="Default Pickadate" />
                                            </div>
                                            <div class="form-group">
                                                <button type="submit"
                                                    class="btn waves-effect waves-light btn-success">Search</button>
                                            </div>
                                            <div class="form-group">
                                                <input type="reset" class="btn waves-effect waves-light btn-warning p-0"
                                                    value="Reset" />
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="card">
                                            <h5 class="card-title mb-4">Blocked Members</h5>
                                            <div class="table-responsive">
                                                <table class="table no-wrap user-table mb-0">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th scope="col" class="border-0 fs-4 font-weight-medium ps-4">
                                                                <div class="form-check border-start border-2 border-info ps-1">
                                                                    <input type="checkbox" class="form-check-input ms-2" id="inputSchedule" name="inputCheckboxesSchedule">
                                                                    <label for="inputSchedule" class="form-check-label ps-2 fw-normal"></label>
                                                                </div>
                                                            </th>
                                                            <th scope="col" class="border-0 fs-4 font-weight-medium">
                                                                Member Name</th>
                                                            <th scope="col" class="border-0 fs-4 font-weight-medium">
                                                                Sponsor Code</th>
                                                            <th scope="col" class="border-0 fs-4 font-weight-medium">
                                                                Email</th>
                                                            <th scope="col" class="border-0 fs-4 font-weight-medium">
                                                            Joining Date</th>
                                                            <th scope="col" class="border-0 fs-4 font-weight-medium">
                                                            Activation Date</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td class="ps-4">
                                                                <div class="form-check border-start border-2 border-info ps-1">
                                                                    <input type="checkbox" class="form-check-input ms-2" id="inputSchedule1" name="inputCheckboxesSchedule">
                                                                    <label for="inputSchedule1" class="form-check-label ps-2 fw-normal"></label>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <h5 class="font-weight-medium mb-1">Daniel Kristeen</h5>
                                                                <a href="javascript:void(0);" class="font-14 text-muted">DEMO2AV</a>
                                                            </td>
                                                            <td>
                                                                <span>1234s56d4sdfdf</span>
                                                            </td>
                                                            <td>
                                                                <span>aanielkristeen@gmail.com</span>
                                                            </td>
                                                            <td>
                                                                <span>12/05/2022</span>
                                                            </td>
                                                            <td><span>12/05/2022</span></td>
                                                        </tr>
                                                        <tr>
                                                            <td class="ps-4">
                                                                <div class="form-check border-start border-2 border-info ps-1">
                                                                    <input type="checkbox" class="form-check-input ms-2" id="inputSchedule2" name="inputCheckboxesSchedule">
                                                                    <label for="inputSchedule2" class="form-check-label ps-2 fw-normal"></label>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <h5 class="font-weight-medium mb-1">Daniel Kristeen</h5>
                                                                <a href="javascript:void(0);" class="font-14 text-muted">DEMO2AV</a>
                                                            </td>
                                                            <td>
                                                                <span>1234s56d4sdfdf</span>
                                                            </td>
                                                            <td>
                                                                <span>aanielkristeen@gmail.com</span>
                                                            </td>
                                                            <td>
                                                                <span>12/05/2022</span>
                                                            </td>
                                                            <td><span>12/05/2022</span></td>
                                                        </tr>
                                                        <tr>
                                                            <td class="ps-4">
                                                                <div class="form-check border-start border-2 border-info ps-1">
                                                                    <input type="checkbox" class="form-check-input ms-2" id="inputSchedule3" name="inputCheckboxesSchedule">
                                                                    <label for="inputSchedule3" class="form-check-label ps-2 fw-normal"></label>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <h5 class="font-weight-medium mb-1">Daniel Kristeen</h5>
                                                                <a href="javascript:void(0);" class="font-14 text-muted">DEMO2AV</a>
                                                            </td>
                                                            <td>
                                                                <span>1234s56d4sdfdf</span>
                                                            </td>
                                                            <td>
                                                                <span>aanielkristeen@gmail.com</span>
                                                            </td>
                                                            <td>
                                                                <span>12/05/2022</span>
                                                            </td>
                                                            <td><span>12/05/2022</span></td>
                                                        </tr>
                                                        <tr>
                                                            <td class="ps-4">
                                                                <div class="form-check border-start border-2 border-info ps-1">
                                                                    <input type="checkbox" class="form-check-input ms-2" id="inputSchedule4" name="inputCheckboxesSchedule">
                                                                    <label for="inputSchedule4" class="form-check-label ps-2 fw-normal"></label>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <h5 class="font-weight-medium mb-1">Daniel Kristeen</h5>
                                                                <a href="javascript:void(0);" class="font-14 text-muted">DEMO2AV</a>
                                                            </td>
                                                            <td>
                                                                <span>1234s56d4sdfdf</span>
                                                            </td>
                                                            <td>
                                                                <span>aanielkristeen@gmail.com</span>
                                                            </td>
                                                            <td>
                                                                <span>12/05/2022</span>
                                                            </td>
                                                            <td><span>12/05/2022</span></td>
                                                        </tr>
                                                        <tr>
                                                            <td class="ps-4">
                                                                <div class="form-check border-start border-2 border-info ps-1">
                                                                    <input type="checkbox" class="form-check-input ms-2" id="inputSchedule5" name="inputCheckboxesSchedule">
                                                                    <label for="inputSchedule5" class="form-check-label ps-2 fw-normal"></label>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <h5 class="font-weight-medium mb-1">Daniel Kristeen</h5>
                                                                <a href="javascript:void(0);" class="font-14 text-muted">DEMO2AV</a>
                                                            </td>
                                                            <td>
                                                                <span>1234s56d4sdfdf</span>
                                                            </td>
                                                            <td>
                                                                <span>aanielkristeen@gmail.com</span>
                                                            </td>
                                                            <td>
                                                                <span>12/05/2022</span>
                                                            </td>
                                                            <td><span>12/05/2022</span></td>
                                                        </tr>
                                                        <tr>
                                                            <td class="ps-4">
                                                                <div class="form-check border-start border-2 border-info ps-1">
                                                                    <input type="checkbox" class="form-check-input ms-2" id="inputSchedule6" name="inputCheckboxesSchedule">
                                                                    <label for="inputSchedule6" class="form-check-label ps-2 fw-normal"></label>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <h5 class="font-weight-medium mb-1">Daniel Kristeen</h5>
                                                                <a href="javascript:void(0);" class="font-14 text-muted">DEMO2AV</a>
                                                            </td>
                                                            <td>
                                                                <span>1234s56d4sdfdf</span>
                                                            </td>
                                                            <td>
                                                                <span>aanielkristeen@gmail.com</span>
                                                            </td>
                                                            <td>
                                                                <span>12/05/2022</span>
                                                            </td>
                                                            <td><span>12/05/2022</span></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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