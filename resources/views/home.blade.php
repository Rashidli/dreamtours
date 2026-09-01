@include('admin.includes.header')
<div class="main-content"><div class="page-content"><div class="container-fluid">
<div class="d-flex justify-content-between align-items-center mb-4"><div><h4>DreamTour dashboard</h4><p class="text-muted mb-0">Tours, enquiries and website content overview</p></div><a class="btn btn-primary" href="{{route('front.home')}}" target="_blank">Open website</a></div>
@php($cards=[
 ['Tours',$stats['tours'],$stats['published_tours'].' published','tours.index','ri-suitcase-line','primary'],
 ['Bookings & enquiries',$stats['bookings'],$stats['pending_bookings'].' pending','bookings.index','ri-calendar-check-line','success'],
 ['CMS pages',$stats['pages'],'Pages and sections','cms-pages.index','ri-pages-line','info'],
 ['Gallery albums',$stats['gallery_albums'],'Albums and media','domain-content.index','ri-gallery-line','warning'],
 ['Visa services',$stats['visa_services'],'Countries and requirements','domain-content.index','ri-passport-line','secondary'],
 ['Legacy contacts',$stats['contacts'],$stats['unread_contacts'].' unread','contacts.index','ri-mail-line','danger'],
 ['Users',$stats['users'],'Roles and permissions','users.index','ri-user-line','dark'],
])
<div class="row">@foreach($cards as [$title,$count,$note,$routeName,$icon,$color])<div class="col-xl-3 col-md-6 mb-4"><div class="card h-100 shadow-sm"><div class="card-body"><div class="d-flex align-items-center"><div class="rounded bg-{{$color}} bg-opacity-10 text-{{$color}} p-3 me-3"><i class="{{$icon}} font-size-24"></i></div><div><p class="text-muted mb-1">{{$title}}</p><h3 class="mb-0">{{$count}}</h3></div></div><div class="d-flex justify-content-between align-items-center mt-3"><small class="text-muted">{{$note}}</small>@if($routeName==='domain-content.index')<a class="btn btn-sm btn-outline-{{$color}}" href="{{route($routeName,$title==='Visa services'?'visa-services':'gallery-albums')}}">Manage</a>@else<a class="btn btn-sm btn-outline-{{$color}}" href="{{route($routeName)}}">Manage</a>@endif</div></div></div></div>@endforeach</div>
<div class="card"><div class="card-body"><h5>Quick content access</h5><div class="d-flex flex-wrap gap-2"><a class="btn btn-outline-primary" href="{{route('page-sections.index')}}">Page sections</a><a class="btn btn-outline-primary" href="{{route('navigation-items.index')}}">Navigation & footer</a><a class="btn btn-outline-primary" href="{{route('company-content.index')}}">Team, partners & stats</a><a class="btn btn-outline-primary" href="{{route('reviews.index')}}">Reviews</a><a class="btn btn-outline-primary" href="{{route('faqs.index')}}">FAQs</a><a class="btn btn-outline-primary" href="{{route('words.index')}}">UI copy</a><a class="btn btn-outline-primary" href="{{route('settings.index')}}">Settings</a></div></div></div>
</div></div></div>@include('admin.includes.footer')
