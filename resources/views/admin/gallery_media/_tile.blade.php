{{-- One media item in the album thumbnail grid. Expects $album and $medium. --}}
@php
  $thumb = $medium->thumbnail ?: ($medium->type === 'image' ? $medium->path : null);
  $rawPath = $medium->getRawOriginal('path');
  $caption = $medium->caption ?: basename(parse_url((string) $rawPath, PHP_URL_PATH) ?: (string) $rawPath);
@endphp
<div class="cat-media-tile">
  <a href="{{ route('gallery-media.edit', [$album, $medium]) }}" class="cat-media-thumb" title="Edit {{ $caption }}">
    @if($thumb)<img src="{{ $thumb }}" alt="{{ $medium->alt_text }}" loading="lazy">@else<span class="cat-media-ph"><i class="{{ $medium->type === 'video' ? 'ri-play-circle-line' : 'ri-image-line' }}"></i></span>@endif
    @if($medium->type === 'video')<span class="cat-media-type"><i class="ri-video-line"></i> Video</span>@endif
  </a>
  <div class="cat-media-actions">
    <a class="btn btn-light" href="{{ route('gallery-media.edit', [$album, $medium]) }}" title="Edit" aria-label="Edit {{ $caption }}"><i class="ri-pencil-line"></i></a>
    <form method="post" action="{{ route('gallery-media.destroy', [$album, $medium]) }}">@csrf @method('DELETE')<button class="btn btn-light text-danger" title="Delete" aria-label="Delete {{ $caption }}" onclick="return confirm('Delete this media item? It is removed from the album.')"><i class="ri-delete-bin-line"></i></button></form>
  </div>
  <div class="cat-media-body">
    <span class="cat-media-caption {{ $medium->caption ? '' : 'cat-muted' }}" title="{{ $caption }}">{{ $caption }}</span>
    <span class="cat-media-order" title="Display order">#{{ $medium->sort_order ?? 0 }}</span>
  </div>
</div>
