@include('admin.includes.header')
<link rel="stylesheet" href="{{ asset('assets/css/admin-tour-form.css') }}?v={{ @filemtime(public_path('assets/css/admin-tour-form.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-cms.css') }}?v={{ @filemtime(public_path('assets/css/admin-cms.css')) }}">
@php
  $filtered = request()->filled('q') || request()->filled('tour_id') || request()->filled('rating') || request()->filled('status');
  $newAction = ['url' => route('reviews.create'), 'label' => 'New review', 'can' => 'create-reviews'];
  $statusTones = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'secondary'];
  $pendingBadge = $pendingCount ? '<a href="'.e(route('reviews.index', ['status' => 'pending'])).'" class="badge bg-warning cms-pending-link">'.$pendingCount.' waiting for approval</a>' : '';
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
@include('admin.cms_ui.list_header', ['title' => 'Reviews', 'count' => $reviews->total(), 'noun' => 'review', 'filtered' => $filtered, 'action' => $newAction, 'extra' => $pendingBadge,
  'intro' => 'Reviews sent from a tour page arrive as <b>Pending</b> and stay hidden until you approve them. Approved, published reviews show on their tour page.'])

<div class="card tl-card"><div class="card-body p-0">
  @if(session('message'))<div class="alert alert-success alert-dismissible fade show m-3 mb-0" role="alert">{{ session('message') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>@endif
  @include('admin.cms_ui.filters', ['action' => route('reviews.index'), 'search' => 'Search name or text', 'clearUrl' => route('reviews.index'),
    'tabs' => ['name' => 'status', 'label' => 'Review status', 'options' => ['' => 'All'] + \App\Models\Review::STATUSES],
    'selects' => [
      ['name' => 'tour_id', 'label' => 'Tour', 'all' => 'All tours', 'options' => $tours->pluck('name', 'id')->all()],
      ['name' => 'rating', 'label' => 'Rating', 'all' => 'All ratings', 'options' => collect(range(5, 1))->mapWithKeys(fn ($r) => [$r => $r.' '.Str::plural('star', $r)])->all()],
    ]])

  <div class="table-responsive"><table class="table table-hover align-middle tl-table cms-table">
    <thead><tr><th>Reviewer</th><th class="d-none d-lg-table-cell">Review</th><th class="d-none d-md-table-cell">Tour</th><th>Rating</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
    <tbody>
    @forelse($reviews as $review)
      <tr>
        <td>@can('edit-reviews')<a class="cms-title" href="{{ route('reviews.edit', $review) }}">{{ $review->name }}</a>@else<span class="cms-title">{{ $review->name }}</span>@endcan @if($review->position)<span class="cms-sub">{{ $review->position }}</span>@endif</td>
        <td class="d-none d-lg-table-cell"><span class="cms-clip text-muted" style="max-width:300px">{{ str($review->content)->limit(90) }}</span></td>
        <td class="d-none d-md-table-cell">{{ $review->tour?->name ?: 'General' }}@if($review->travel_date)<span class="cms-sub">Travelled {{ $review->travel_date->format('M Y') }}</span>@endif</td>
        <td class="cms-stars" aria-label="{{ $review->rating }} out of 5">{{ str_repeat('★', $review->rating) }}<span class="off">{{ str_repeat('★', 5 - $review->rating) }}</span></td>
        <td class="text-nowrap"><span class="badge bg-{{ $statusTones[$review->status] ?? 'secondary' }}">{{ \App\Models\Review::STATUSES[$review->status] ?? $review->status }}</span>@if($review->status === 'approved')<span class="cms-sub {{ $review->is_active ? 'text-success' : '' }}">{{ $review->is_active ? 'Published' : 'Unpublished' }}</span>@endif</td>
        <td class="cms-actions">
          @can('edit-reviews')
            @if($review->status !== 'approved' || !$review->is_active)<form method="post" action="{{ route('reviews.moderate', $review) }}">@csrf @method('PUT')<input type="hidden" name="action" value="approve"><button class="btn btn-sm btn-success" title="{{ $review->status === 'approved' ? 'Publish again' : 'Approve and publish' }}" aria-label="{{ $review->status === 'approved' ? 'Publish again' : 'Approve and publish' }}"><i class="ri-check-line"></i></button></form>@endif
            @if($review->status !== 'rejected')<form method="post" action="{{ route('reviews.moderate', $review) }}">@csrf @method('PUT')<input type="hidden" name="action" value="reject"><button class="btn btn-sm btn-outline-secondary" title="Reject" aria-label="Reject"><i class="ri-close-line"></i></button></form>@endif
            @if($review->status === 'approved' && $review->is_active)<form method="post" action="{{ route('reviews.moderate', $review) }}">@csrf @method('PUT')<input type="hidden" name="action" value="unpublish"><button class="btn btn-sm btn-outline-secondary" title="Unpublish (hide from the website)" aria-label="Unpublish"><i class="ri-eye-off-line"></i></button></form>@endif
          @endcan
          <button type="button" class="btn btn-sm btn-light btn-details" data-bs-toggle="modal" data-bs-target="#cms-details" data-details="#cms-d-{{ $review->id }}">Details</button>
          @can('edit-reviews')<a class="btn btn-sm btn-primary" title="Edit" aria-label="Edit the review by {{ $review->name }}" href="{{ route('reviews.edit', $review) }}"><i class="ri-pencil-line"></i></a>@endcan
          @can('delete-reviews')<form method="post" action="{{ route('reviews.destroy', $review) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="Delete" aria-label="Delete the review by {{ $review->name }}" onclick="return confirm('Delete this review? This cannot be undone.')"><i class="ri-delete-bin-line"></i></button></form>@endcan
          <template id="cms-d-{{ $review->id }}" data-title="Review by {{ $review->name }}" data-edit="{{ auth()->user()->can('edit-reviews') ? route('reviews.edit', $review) : '' }}">
            <dl>
              <dt>Rating</dt><dd><span class="cms-stars">{{ str_repeat('★', $review->rating) }}<span class="off">{{ str_repeat('★', 5 - $review->rating) }}</span></span> {{ $review->rating }}/5</dd>
              <dt>Status</dt><dd>{{ \App\Models\Review::STATUSES[$review->status] ?? $review->status }}@if($review->status === 'approved') · {{ $review->is_active ? 'Published' : 'Unpublished' }}@endif</dd>
              <dt>Tour</dt><dd>{{ $review->tour?->name ?: 'General (not linked to a tour)' }}</dd>
              @if($review->travel_date)<dt>Travelled</dt><dd>{{ $review->travel_date->format('F Y') }}</dd>@endif
              @if($review->position)<dt>Position / city</dt><dd>{{ $review->position }}</dd>@endif
              @if($review->email)<dt>Email</dt><dd><a href="mailto:{{ $review->email }}">{{ $review->email }}</a></dd>@endif
            </dl>
            <div class="cms-details-text">{!! nl2br(e($review->content)) !!}</div>
          </template>
        </td>
      </tr>
    @empty
      @include('admin.cms_ui.empty', ['colspan' => 6, 'icon' => 'ri-chat-quote-line', 'title' => 'No reviews yet', 'text' => 'Reviews sent from tour pages appear here for approval. You can also add one yourself.', 'filtered' => $filtered, 'clearUrl' => route('reviews.index'), 'action' => $newAction])
    @endforelse
    </tbody>
  </table></div>
  <div class="tl-pager">{{ $reviews->links('admin.vendor.pagination.bootstrap-5') }}</div>
</div></div>
</div></div></div>
@include('admin.cms_ui.details_modal')
@include('admin.includes.footer')
