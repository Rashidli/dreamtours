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
                                <h4 class="card-title">Addımlar</h4>
                                        <a href="{{route('steps.create')}}" class="btn btn-primary">+</a>
                                <br>
                                <br>
                                <div class="table-responsive">
                                    <table class="table table-centered mb-0 align-middle table-hover table-nowrap">

                                        <thead>
                                            <tr>
                                                <th>№</th>
                                                <th>Başlıq (AZ)</th>
                                                <th>White Icon</th>
                                                <th>Blue Icon</th>
                                                <th>Status</th>
                                                <th>Əməliyyat</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($steps as $step)

                                            <tr>
                                                <th scope="row">{{$step->id}}</th>
                                                <td>{{$step->translate('az')?->title}}</td>
                                                <td>
                                                    @if($step->white_icon)
                                                        <img src="{{ asset('storage/' . $step->white_icon) }}" alt="" style="height: 30px; background: #333; padding: 5px; border-radius: 4px;">
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($step->blue_icon)
                                                        <img src="{{ asset('storage/' . $step->blue_icon) }}" alt="" style="height: 30px;">
                                                    @endif
                                                </td>
                                                <td>{{$step->is_active ? 'Aktiv' : 'Deaktiv'}}</td>
                                                <td>
                                                    <a href="{{route('steps.edit',$step->id)}}" class="btn btn-primary" style="margin-right: 15px" >Edit</a>
                                                    <form action="{{route('steps.destroy', $step->id)}}" method="post" style="display: inline-block">
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
                                    {{ $steps->links('admin.vendor.pagination.bootstrap-5') }}
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

@include('admin.includes.footer')
