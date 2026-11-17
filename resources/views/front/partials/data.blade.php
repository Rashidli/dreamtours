{{-- Page data for the Alpine components. The gallery albums are only needed by the Gallery
     page and the flat photo list only by server-rendered markup, so they are not sent with
     every page (they were ~95 KB of each page's HTML). --}}
<script>window.DreamTourData = @json(\Illuminate\Support\Arr::except($frontendData, (($cmsPage->template ?? null) === 'gallery') ? ['gallery'] : ['gallery', 'galleryAlbums']), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);</script>
