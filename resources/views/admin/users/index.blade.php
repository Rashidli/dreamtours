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
                                <h4 class="card-title">İstifadəçilər</h4>
                                <a href="{{route('users.create')}}" class="btn btn-primary">+</a>
                                <br>
                                <br>
                                <div class="table-responsive">
                                    <table class="table table-bordered mb-0">

                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Ad</th>
                                                <th>Email</th>
                                                <th>Əməliyyat</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($users as $key => $user)
                                            <tr>
                                                <th scope="row">{{$key+1}}</th>
                                                <td>{{$user->name}}</td>
                                                <td>{{$user->email}}</td>
                                                <td><a href="{{route('users.edit',$user->id)}}" class="btn btn-primary" style="margin-right: 15px" >Edit</a>
                                                    @if($user->id !== 3)
                                                        <form action="{{route('users.destroy', $user->id)}}" method="post" style="display: inline-block">
                                                            {{ method_field('DELETE') }}
                                                            @csrf
                                                            <button  type="submit" class="btn btn-danger">Delete</button>
                                                        </form>
                                                    @endif
                                                </td>

                                            </tr>

                                        @endforeach

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



@include('admin.includes.footer')
