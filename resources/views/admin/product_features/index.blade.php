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
                            @if(session('error'))
                                <div class="alert alert-danger">{{session('error')}}</div>
                            @endif
                            <h4 class="card-title">Mehsul Xususiyyetleri (Filterlər)</h4>
                            <a href="{{route('product_features.create')}}" class="btn btn-primary">+</a>
                            <br>
                            <br>
                            <div class="table-responsive">
                                <table class="table table-centered mb-0 align-middle table-hover table-nowrap">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Basliq</th>
                                            <th>Mehsul Sayi</th>
                                            <th>Status</th>
                                            <th>Emeliyyat</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($features as $feature)
                                        <tr>
                                            <th scope="row">{{$feature->id}}</th>
                                            <td>{{$feature->title}}</td>
                                            <td><span class="badge bg-info">{{$feature->products_count ?? 0}}</span></td>
                                            <td>
                                                @if($feature->is_active)
                                                    <span class="badge bg-success">Aktiv</span>
                                                @else
                                                    <span class="badge bg-secondary">Deaktiv</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{route('product_features.edit', $feature->id)}}" class="btn btn-primary btn-sm" style="margin-right: 5px">Edit</a>
                                                <form action="{{route('product_features.destroy', $feature->id)}}" method="post" style="display: inline-block">
                                                    {{ method_field('DELETE') }}
                                                    @csrf
                                                    <button onclick="return confirm('Melumatın silinmeyini tesdiqleyin')" type="submit" class="btn btn-danger btn-sm">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                                <br>
                                {{ $features->links('admin.vendor.pagination.bootstrap-5') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('admin.includes.footer')
