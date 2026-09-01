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
                            <h4 class="card-title">Mehsullar</h4>
                            <a href="{{route('products.create')}}" class="btn btn-primary">+</a>
                            <br>
                            <br>
                            <div class="table-responsive">
                                <table class="table table-centered mb-0 align-middle table-hover table-nowrap">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Sekil</th>
                                            <th>Basliq</th>
                                            <th>Kateqoriya</th>
                                            <th>Qiymet</th>
                                            <th>Status</th>
                                            <th>Emeliyyat</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($products as $product)
                                        <tr>
                                            <th scope="row">{{$product->id}}</th>
                                            <td>
                                                @php
                                                    $img = $product->image ?: ($product->images->first()?->image ?? null);
                                                @endphp
                                                @if($img)
                                                    <img src="{{asset('storage/'.$img)}}" style="width: 80px; height: 50px; object-fit: cover; border-radius: 4px;" alt="">
                                                @else
                                                    <span class="text-muted">Şəkil yoxdur</span>
                                                @endif
                                            </td>
                                            <td>{{$product->title}}</td>
                                            <td>{{$product->category?->title ?? '-'}}</td>
                                            <td>
                                                @if($product->old_price)
                                                    <del class="text-muted">{{$product->old_price}} AZN</del><br>
                                                @endif
                                                <strong>{{$product->price}} AZN</strong>
                                            </td>
                                            <td>
                                                @if($product->is_active)
                                                    <span class="badge bg-success">Aktiv</span>
                                                @else
                                                    <span class="badge bg-secondary">Deaktiv</span>
                                                @endif
                                                @if($product->is_featured)
                                                    <span class="badge bg-warning">One cixan</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{route('products.edit', $product->id)}}" class="btn btn-primary btn-sm" style="margin-right: 5px">Edit</a>
                                                <form action="{{route('products.destroy', $product->id)}}" method="post" style="display: inline-block">
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
                                {{ $products->links('admin.vendor.pagination.bootstrap-5') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('admin.includes.footer')
