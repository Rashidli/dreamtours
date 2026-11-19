@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-cms.css') }}?v={{ @filemtime(public_path('assets/css/admin-cms.css')) }}">
@php
  $filtered = request()->filled('q');
  $newAction = ['url' => route('abouts.create'), 'label' => 'New block'];
  $aboutKeys = ['main' => 'Our story', 'vision' => 'Vision', 'mission' => 'Mission', 'founder' => 'Founder'];
  $media = app(\App\Services\ImageUploadService::class);
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
@include('admin.cms_ui.list_header', ['title' => 'About content', 'count' => $abouts->total(), 'noun' => 'block', 'filtered' => $filtered, 'action' => $newAction,
  'intro' => 'Text blocks on the About page: <b>Our story</b>, <b>Vision</b>, <b>Mission</b> and the <b>Founder</b> card.'])

<div class="card tl-card"><div class="card-body p-0">
  @if(session('message'))<div class="alert alert-success alert-dismissible fade show m-3 mb-0" role="alert">{{ session('message') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>@endif
  @include('admin.cms_ui.filters', ['action' => route('abouts.index'), 'search' => 'Search key or title', 'clearUrl' => route('abouts.index')])

  <div class="table-responsive"><table class="table table-hover align-middle tl-table cms-table">
    <thead><tr><th style="width:88px">Image</th><th>Block</th><th class="d-none d-md-table-cell">Key</th><th class="text-end">Actions</th></tr></thead>
    <tbody>
    @forelse($abouts as $about)
      <tr>
        <td>@if($about->image)<img src="{{ $media->url($about->image) }}" alt="" class="cms-thumb" loading="lazy">@else<span class="cms-thumb-empty"><i class="ri-image-line"></i></span>@endif</td>
        <td class="cms-wrap"><a class="cms-title" href="{{ route('abouts.edit', $about) }}">{{ $about->title }}</a><span class="cms-sub">{{ $aboutKeys[$about->key] ?? 'Custom block' }}</span></td>
        <td class="d-none d-md-table-cell"><code class="cms-code">{{ $about->key }}</code></td>
        <td class="cms-actions">
          <button type="button" class="btn btn-sm btn-light btn-details" data-bs-toggle="modal" data-bs-target="#cms-details" data-details="#cms-d-{{ $about->id }}">Details</button>
          <a class="btn btn-sm btn-primary" title="Edit" aria-label="Edit {{ $about->title }}" href="{{ route('abouts.edit', $about) }}"><i class="ri-pencil-line"></i></a>
          <form method="post" action="{{ route('abouts.destroy', $about) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="Delete" aria-label="Delete {{ $about->title }}" onclick="return confirm('Delete this About block? It disappears from the About page.')"><i class="ri-delete-bin-line"></i></button></form>
          <template id="cms-d-{{ $about->id }}" data-title="{{ $about->title }}" data-edit="{{ route('abouts.edit', $about) }}">
            @if($about->image)<img src="{{ $media->url($about->image) }}" alt="" class="cms-details-img">@endif
            <dl><dt>Block</dt><dd>{{ $aboutKeys[$about->key] ?? 'Custom block' }} <code>{{ $about->key }}</code></dd></dl>
            <div class="cms-details-text">{!! $about->description !!}</div>
          </template>
        </td>
      </tr>
    @empty
      @include('admin.cms_ui.empty', ['colspan' => 4, 'icon' => 'ri-article-line', 'title' => 'No About blocks yet', 'text' => 'Start with “Our story” (key main), then add Vision, Mission and Founder.', 'filtered' => $filtered, 'clearUrl' => route('abouts.index'), 'action' => $newAction])
    @endforelse
    </tbody>
  </table></div>
  <div class="tl-pager">{{ $abouts->links('admin.vendor.pagination.bootstrap-5') }}</div>
</div></div>
</div></div></div>
@include('admin.cms_ui.details_modal')
@include('admin.includes.footer')
