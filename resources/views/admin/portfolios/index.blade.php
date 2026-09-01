@include('admin.includes.header')

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            @if(session('message'))
                                <div class="alert alert-success">{{session('message')}}</div>
                            @endif
                            <h4 class="card-title">Portfolio</h4>
                            <a href="{{route('portfolios.create')}}" class="btn btn-primary">+</a>
                            <br>
                            <br>
                            <div class="table-responsive">
                                <table class="table table-centered mb-0 align-middle table-hover table-nowrap">
                                    <thead>
                                        <tr>
                                            <th>№</th>
                                            <th>Şəkil</th>
                                            <th>Başlıq</th>
                                            <th>Xidmət</th>
                                            <th>Status</th>
                                            <th>Əməliyyat</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($portfolios as $portfolio)
                                        <tr>
                                            <th scope="row">{{$portfolio->id}}</th>
                                            <td><img src="{{asset('storage/'.$portfolio->image)}}" style="width: 80px; height: 50px; object-fit: cover; border-radius: 4px;" alt=""></td>
                                            <td>{{$portfolio->title}}</td>
                                            <td>{{$portfolio->service?->title ?? '-'}}</td>
                                            <td>{{$portfolio->is_active ? 'Active' : 'Deactive'}}</td>
                                            <td>
                                                <a href="{{route('portfolios.edit', $portfolio->id)}}" class="btn btn-primary" style="margin-right: 15px">Edit</a>
                                                <form action="{{route('portfolios.destroy', $portfolio->id)}}" method="post" style="display: inline-block">
                                                    {{ method_field('DELETE') }}
                                                    @csrf
                                                    <button onclick="return confirm('Məlumatın silinməyin təsdiqləyin')" type="submit" class="btn btn-danger">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                                <br>
                                {{ $portfolios->links('admin.vendor.pagination.bootstrap-5') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('admin.includes.footer')
