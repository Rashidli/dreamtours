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
                            <h4 class="card-title">Mehsul Kateqoriyalari</h4>
                            <a href="{{route('product_categories.create')}}" class="btn btn-primary">+</a>
                            <br>
                            <br>
                            <div class="table-responsive">
                                <table class="table table-centered mb-0 align-middle table-hover table-nowrap">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Sekil</th>
                                            <th>Basliq</th>
                                            <th>Mehsul Sayi</th>
                                            <th>Status</th>
                                            <th>Emeliyyat</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($categories as $category)
                                        <tr>
                                            <th scope="row">{{$category->id}}</th>
                                            <td>
                                                @if($category->image)
                                                    <img src="{{asset('storage/'.$category->image)}}" style="width: 60px; height: 40px; object-fit: cover; border-radius: 4px;" alt="">
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>{{$category->title}}</td>
                                            <td><span class="badge bg-info">{{$category->products_count ?? 0}}</span></td>
                                            <td>
                                                @if($category->is_active)
                                                    <span class="badge bg-success">Aktiv</span>
                                                @else
                                                    <span class="badge bg-secondary">Deaktiv</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{route('product_categories.edit', $category->id)}}" class="btn btn-primary btn-sm" style="margin-right: 5px">Edit</a>
                                                <form action="{{route('product_categories.destroy', $category->id)}}" method="post" style="display: inline-block">
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
                                {{ $categories->links('admin.vendor.pagination.bootstrap-5') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('admin.includes.footer')
