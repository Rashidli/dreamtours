@include('admin.includes.header')<div class="main-content"><div class="page-content"><div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Edit FAQ</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{route('faqs.index')}}">FAQ</a></li>
                                <li class="breadcrumb-item active">Edit</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
<form method="post" action="{{$faq->exists?route('faqs.update',$faq):route('faqs.store')}}">@csrf @if($faq->exists)@method('PUT')@endif<div class="card"><div class="card-body"><h4>{{$faq->exists?'Edit FAQ':'Add FAQ'}}</h4>@include('admin.includes.validation_errors')<div class="mb-3"><label>Question</label><input class="form-control" name="en[question]" value="{{old('en.question',$faq->translate('en')?->question)}}" required></div><div class="mb-3"><label>Answer</label><textarea class="form-control" rows="6" name="en[answer]" required>{{old('en.answer',$faq->translate('en')?->answer)}}</textarea></div><div class="row"><div class="col-md-3 d-flex align-items-end"><label><input type="checkbox" name="is_active" value="1" @checked(old('is_active',$faq->exists?$faq->is_active:true))> Active</label></div></div><button class="btn btn-primary mt-3"><i class="ri-save-line align-middle me-1"></i> Save</button> <a class="btn btn-light mt-3" href="{{route('faqs.index')}}"><i class="ri-arrow-left-line align-middle me-1"></i> Back</a></div></div></form></div></div></div>@include('admin.includes.footer')
