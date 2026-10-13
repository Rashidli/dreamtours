@include('admin.includes.header')<div class="main-content"><div class="page-content"><div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Request detail</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{route('contacts.index')}}">Contact requests</a></li>
                                <li class="breadcrumb-item active">Detal</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
<div class="card"><div class="card-body"><h4>Contact request</h4><dl class="row"><dt class="col-md-3">Name</dt><dd class="col-md-9">{{$contact->name}}</dd><dt class="col-md-3">Phone</dt><dd class="col-md-9">{{$contact->phone}}</dd><dt class="col-md-3">Message</dt><dd class="col-md-9">{!!nl2br(e($contact->message))!!}</dd><dt class="col-md-3">Received</dt><dd class="col-md-9">{{$contact->created_at}}</dd></dl><a class="btn btn-light" href="{{route('contacts.index')}}"><i class="ri-arrow-left-line align-middle me-1"></i> Back</a></div></div></div></div></div>@include('admin.includes.footer')
